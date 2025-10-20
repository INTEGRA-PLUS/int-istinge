<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;

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
        $response = $this->makeRequest(
            "GET",
            $this->baseUri . "/api/v1/channel/wbot/" . $uuid,
            [],
            [],
            $this->headers,
            true
        );

        // Si viene como array, conviértelo a JSON
        if (is_array($response)) {
            return json_encode($response);
        }

        // Si ya es string lo devuelves igual
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

    public function sendTemplate(string $uuid, array $body)
    {
        return $this->makeRequest(
            "POST",
            $this->baseUri . "/api/v1/channels/waba/{$uuid}/send-template",
            [],
            $body,
            $this->headers,
            true
        );
    }
}