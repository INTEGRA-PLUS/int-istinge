<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

// Include the local RouterOS API class
include_once(app_path() . '/../public/routeros_api.class.php');
use RouterosAPI;

class MikrotikService
{
    protected $client;
    protected $config;

    public function __construct()
    {
        $this->config = [
            'host' => env('MIKROTIK_HOST'),
            'user' => env('MIKROTIK_USER'),
            'pass' => env('MIKROTIK_PASS'),
            'port' => (int) env('MIKROTIK_PORT', 8728),
        ];
    }

    /**
     * Connect to Mikrotik
     * 
     * @return void
     * @throws Exception
     */
    protected function connect()
    {
        try {
            $this->client = new RouterosAPI();
            $this->client->port = $this->config['port'];
            
            // Optional: Enable debug if needed, or based on app environment
            // $this->client->debug = config('app.debug');

            if (!$this->client->connect($this->config['host'], $this->config['user'], $this->config['pass'])) {
                 throw new Exception('No se pudo conectar al Mikrotik: ' . $this->config['host']);
            }

        } catch (Exception $e) {
            Log::error('Mikrotik Connection Error: ' . $e->getMessage());
            throw new Exception('Error conectando con MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Get morosos list from Mikrotik
     * 
     * @return array
     */
    public function getMorosos()
    {
        try {
            if (!$this->client) {
                $this->connect();
            }

            $listName = env('MIKROTIK_LIST_NAME', 'morosos');

            // Use the comm method from RouterosAPI
            // Filtering is done by passing an array with the filter criteria
            // ?attribute=value syntax is used for queries in this library
            $response = $this->client->comm('/ip/firewall/address-list/print', [
                "?list" => $listName
            ]);
            
            $this->client->disconnect();

            return $this->formatResponse($response);

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
     * @return array
     */
    protected function formatResponse($data)
    {
        if (empty($data) || isset($data['!trap'])) {
             // Handle errors returned by the API (indicated by !trap)
             if (isset($data['!trap'])) {
                 Log::error('Mikrotik API Error: ' . json_encode($data));
             }
            return [
                'success' => true, // Return success true with empty data to avoid breaking UI
                'total' => 0,
                'data' => []
            ];
        }

        $formatted = [];
        foreach ($data as $item) {
            // Check if matches the list name again just in case, though the API query should handle it
            // The API response keys don't strictly follow a specific format like .id but usually match the printed columns
            if (isset($item['list']) && $item['list'] == env('MIKROTIK_LIST_NAME', 'morosos')) {
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
