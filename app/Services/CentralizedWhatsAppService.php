<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Support\Facades\Log;

class CentralizedWhatsAppService
{
    use ConsumesExternalServices;

    protected $baseUri;

    public function __construct()
    {
        $this->baseUri = rtrim(env('WAPP_CENTRAL_URL', 'http://whatsapp.integracolombia.com/api/v1'), '/') . '/';
    }

    /**
     * Get list of conversations
     * 
     * @param string $token (phone_number_id)
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getConversations(string $token, int $page = 1, int $perPage = 20)
    {
        return $this->makeRequest(
            'GET',
            'conversations',
            [
                'page' => $page,
                'per_page' => $perPage,
            ],
            [],
            ['X-Instance-Token' => $token],
            true
        );
    }

    /**
     * Get messages of a conversation
     * 
     * @param string $token (phone_number_id)
     * @param mixed $conversationId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getMessages(string $token, $conversationId, int $page = 1, int $perPage = 50)
    {
        return $this->makeRequest(
            'GET',
            "conversations/{$conversationId}/messages",
            [
                'page' => $page,
                'per_page' => $perPage,
            ],
            [],
            ['X-Instance-Token' => $token],
            true
        );
    }

    /**
     * Send a text message
     * 
     * @param string $token (phone_number_id)
     * @param string $to
     * @param string $message
     * @return array
     */
    public function sendMessage(string $token, string $to, string $message)
    {
        return $this->makeRequest(
            'POST',
            'messages/send',
            [],
            [
                'to' => $to,
                'message' => $message,
            ],
            ['X-Instance-Token' => $token],
            true
        );
    }

    /**
     * Send a template message
     * 
     * @param string $token (phone_number_id)
     * @param string $to
     * @param string $templateName
     * @param string $languageCode
     * @param array $components
     * @return array
     */
    public function sendTemplate(string $token, string $to, string $templateName, string $languageCode = 'es', array $components = [])
    {
        return $this->makeRequest(
            'POST',
            'messages/template',
            [],
            [
                'to' => $to,
                'template_name' => $templateName,
                'language_code' => $languageCode,
                'components' => $components,
            ],
            ['X-Instance-Token' => $token],
            true
        );
    }

    /**
     * Register a message (sync)
     * 
     * @param string $token (phone_number_id)
     * @param array $data
     * @return array
     */
    public function registerMessage(string $token, array $data)
    {
        return $this->makeRequest(
            'POST',
            'messages/register',
            [],
            $data,
            ['X-Instance-Token' => $token],
            true
        );
    }

    public function decodeResponse($response)
    {
        return json_decode($response, true);
    }

    /**
     * Custom resolve authorization to avoid default behavior if any
     */
    public function resolveAuthorization(&$queryParams, &$formParams, &$headers)
    {
        // Token is handled via headers in each request method
    }
}
