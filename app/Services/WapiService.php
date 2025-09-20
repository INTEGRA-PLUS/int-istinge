<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Support\Facades\Log;

class WapiService
{
    use ConsumesExternalServices;

    protected $baseUri;

    protected $secretToken;

    protected $headers;

    public function __construct()
    {
        $this->baseUri = env('WAPI_URL', 'https://api.vibiocrm.com');
        $this->secretToken = strval(env('WAPI_TOKEN'));
        $this->headers = [
            'cache-control' => 'no-cache',
            'content-type' => 'application/json',
            'Authorization' => 'Bearer ' . env('WAPI_TOKEN'),
        ];
    }

    public function resolveAuthorization(&$queryParams, &$formParams, &$headers)
    {
        // En lugar de meterlo en query param, lo ponemos en el header
        $headers['Authorization'] = 'Bearer ' . $this->secretToken;
    }


    public function getInstance(string $uuid)
    {
        try {
            $url = $this->baseUri . "/api/v1/channel/wbot/" . $uuid;

            Log::info('Llamando a WAPI', [
                'url' => $url,
                'headers' => $this->headers,
            ]);

            $response = $this->makeRequest(
                "GET",
                $url,
                [],
                [],
                $this->headers,
                true
            );

            if (!$response) {
                throw new \Exception("makeRequest devolvió null");
            }

            $body = (string) $response->getBody();

            Log::info('Respuesta de WAPI', [
                'status' => $response->getStatusCode(),
                'body'   => $body,
            ]);

            return $body;

        } catch (\Throwable $e) {
            Log::error('Error en getInstance: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null; // o puedes lanzar la excepción si quieres romper
        }
    }


    public function getInstanceById(int $id)
    {
        $instance = \App\Instance::find($id); // asegúrate que el namespace sea correcto

        if (!$instance) {
            throw new \Exception("No se encontró la instancia con id $id");
        }

        if (!$instance->uuid) {
            throw new \Exception("La instancia encontrada no tiene UUID asignado");
        }

        // Llamar al endpoint de Wapi usando el UUID
        $response = $this->makeRequest(
            "GET",
            $this->baseUri . "/api/v1/channel/wbot/" . $instance->uuid,
            [],
            [],
            $this->headers
        );

        // Convertir a JSON si viene como array
        if (is_array($response)) {
            return json_encode($response);
        }

        return $response;
    }



    public function initSession(string $uuid)
    {
        return $this->makeRequest(
            "POST",
            $this->baseUri . "/api/v1/start-session/" . $uuid,
            [],
            [],
            $this->headers,
            true
        );
    }

    public function sendMessageMedia(string $uuid, string $apiKey, array $body)
    {
        return $this->makeRequest(
            "POST",
            $this->baseUri . "/api/v1/send/" . $uuid,
            [],
            $body, // 👈 mandas el body limpio
            $this->headers,
            true
        );
    }
}
