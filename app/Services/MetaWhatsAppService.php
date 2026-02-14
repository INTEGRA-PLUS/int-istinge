<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Support\Facades\Http;

class MetaWhatsAppService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $accessToken;
    protected $apiVersion;

    public function __construct()
    {
        $this->apiVersion = config('services.meta.api_version', 'v21.0');
        $this->baseUri = "https://graph.facebook.com/{$this->apiVersion}";
        $this->accessToken = config('services.meta.access_token');
    }

    /**
     * Send a text message
     */
    public function sendMessage(string $phoneNumberId, string $to, string $message)
    {
        return $this->sendRequest($phoneNumberId, [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $message
            ]
        ]);
    }

    /**
     * Send a template message
     */
    public function sendTemplate(string $phoneNumberId, string $to, string $templateName, string $languageCode = 'es', array $components = [])
    {
        return $this->sendRequest($phoneNumberId, [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => [
                    'code' => $languageCode
                ],
                'components' => $components
            ]
        ]);
    }

    /**
     * Generic method to send requests to the Messages endpoint
     */
    protected function sendRequest(string $phoneNumberId, array $data)
    {
        $url = "{$this->baseUri}/{$phoneNumberId}/messages";

        $response = Http::withToken($this->accessToken)
            ->post($url, $data);

        return $response->json();
    }
}
