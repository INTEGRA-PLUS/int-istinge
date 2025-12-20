<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class chatboxIntegraController extends Controller
{


    public function chatIaWebhook(Request $request)
    {
        $message = $request->input('content', 'Hello from Integra (test)');
        $url = env('VIBIO_CHAT_WEBHOOK_URL');

        try {
            $client = new Client(['timeout' => 20]);

            $res = $client->post($url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => ['content' => $message],
            ]);

            $status = $res->getStatusCode();
            $body = json_decode($res->getBody(), true);

            Log::info('✅ Vibio SUCCESS', [
                'status' => $status,
                'sent' => $message,
                'response' => $body,
            ]);

            return response()->json([
                'ok' => true,
                'response' => $body,
            ]);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            // 500-level errors from Vibio
            $response = $e->getResponse();
            $errorBody = json_decode($response->getBody(), true);

            Log::error('🤖 No puedo atenderte en este momento', [
                'sent' => $message,
                'status' => $response->getStatusCode(),
                'error' => $errorBody,
                'hint' => 'Check if n8n workflow is active and configured correctly',
            ]);

            return response()->json([
                'ok' => false,
                'error' => '🤖 Error en el servidor IA',
                'details' => $errorBody,
            ], 500);
        } catch (\Throwable $e) {
            Log::error('🤖 Error en la petición', [
                'sent' => $message,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
