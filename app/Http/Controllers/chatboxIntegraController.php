<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class chatboxIntegraController extends Controller
{


    public function chatIaWebhook(Request $request)
    {
        $message   = $request->input('content', 'Hola desde Integra');
        $sessionId = $request->input('session_id', 'session-' . session()->getId());
        $url       = env('VIBIO_CHAT_WEBHOOK_URL');

        try {
            $client = new Client(['timeout' => 20]);

            $res = $client->post($url, [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'chatInput' => $message,
                    'sessionId' => $sessionId,
                ],
            ]);

            $status = $res->getStatusCode();
            $body   = json_decode($res->getBody(), true);

            // Aquí tomas solo el texto
            $reply = $body['output'] ?? 'Sin respuesta del servidor IA';

            Log::info('✅ Vibio SUCCESS', [
                'status'    => $status,
                'sent'      => $message,
                'sessionId' => $sessionId,
                'response'  => $body,
            ]);

            return response()->json([
                'ok'    => true,
                'reply' => $reply,
            ]);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $response  = $e->getResponse();
            $errorBody = json_decode($response->getBody(), true);

            Log::error('🤖 No puedo atenderte en este momento', [
                'sent'      => $message,
                'sessionId' => $sessionId,
                'status'    => $response->getStatusCode(),
                'error'     => $errorBody,
            ]);

            return response()->json([
                'ok'     => false,
                'error'  => '🤖 Error en el servidor IA',
                'detail' => $errorBody,
            ], 500);
        } catch (\Throwable $e) {
            Log::error('🤖 Error en la petición', [
                'sent'      => $message,
                'sessionId' => $sessionId,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
