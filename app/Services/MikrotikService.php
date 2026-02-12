<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Mikrotik;

// Include the local RouterOS API class
include_once(app_path() . '/../public/routeros_api.class.php');
use RouterosAPI;

class MikrotikService
{
    protected $client;

    public function __construct()
    {
        // Constructor no longer needs to setup default config from .env
    }

    /**
     * Connect to Mikrotik
     * 
     * @param int $mikrotikId
     * @return void
     * @throws Exception
     */
    protected function connect($mikrotikId)
    {
        try {
            $mikrotik = Mikrotik::find($mikrotikId);

            if (!$mikrotik) {
                throw new Exception('Mikrotik no encontrado');
            }

            $this->client = new RouterosAPI();
            $this->client->port = (int) $mikrotik->puerto_api;
            
            // Optional: Enable debug if needed
            // $this->client->debug = config('app.debug');

            if (!$this->client->connect($mikrotik->ip, $mikrotik->usuario, $mikrotik->clave)) {
                 throw new Exception('No se pudo conectar al Mikrotik: ' . $mikrotik->ip);
            }

        } catch (Exception $e) {
            Log::error('Mikrotik Connection Error: ' . $e->getMessage());
            throw new Exception('Error conectando con MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Get morosos list from Mikrotik
     * 
     * @param int $mikrotikId
     * @return array
     */
    public function getMorosos($mikrotikId)
    {
        try {
            $this->connect($mikrotikId);

            // Default list name, could also be made dynamic if needed in future
            $listName = env('MIKROTIK_LIST_NAME', 'morosos');

            // Use the comm method from RouterosAPI
            $response = $this->client->comm('/ip/firewall/address-list/print', [
                "?list" => $listName
            ]);
            
            $this->client->disconnect();

            return $this->formatResponse($response, $listName);

        } catch (Exception $e) {
            Log::error('Mikrotik Query Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Format the response from Mikrotik
     * 
     * @param array $data
     * @param string $listName
     * @return array
     */
    protected function formatResponse($data, $listName)
    {
        if (empty($data) || isset($data['!trap'])) {
             // Handle errors returned by the API
             if (isset($data['!trap'])) {
                 Log::error('Mikrotik API Error: ' . json_encode($data));
             }
            return [
                'success' => true,
                'total' => 0,
                'data' => []
            ];
        }

        $formatted = [];
        foreach ($data as $item) {
            if (isset($item['list']) && $item['list'] == $listName) {
                 $formatted[] = [
                    'id' => $item['.id'] ?? null,
                    'ip' => $item['address'] ?? null,
                    'comentario' => $item['comment'] ?? '',
                    'fecha_creacion' => $item['creation-time'] ?? null,
                ];
            }
        }

        return [
            'success' => true,
            'total' => count($formatted),
            'data' => $formatted
        ];
    }
}
