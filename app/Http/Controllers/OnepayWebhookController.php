<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Factura;

class OnepayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $expectedTokenId = env('ONEPAY_WEBHOOK_HEADER');   // wh_hdr_...
        $secretKey       = env('ONEPAY_WEBHOOK_SECRET');   // wh_tok_...

        if (!$expectedTokenId || !$secretKey) {
            Log::warning('Onepay webhook sin configuración completa', [
                'expected_token_id' => $expectedTokenId,
                'secret_key'        => $secretKey ? '***' : null,
            ]);

            return response()->json(['message' => 'Config incompleta'], 500);
        }

        // Lo que llega realmente en los headers
        $tokenHeader      = $request->header('x-webhook-token');
        $signatureHeader  = $request->header('signature');
        $rawBody          = $request->getContent();

        // 1) Validar que el webhook que llama es el que esperamos
        if (!$tokenHeader || $tokenHeader !== $expectedTokenId) {
            Log::warning('Onepay webhook token ID inválido', [
                'expected_token_id' => $expectedTokenId,
                'received_token_id' => $tokenHeader,
                'all_headers'       => $request->headers->all(),
            ]);

            return response()->json(['message' => 'Token inválido'], 401);
        }

        // 2) Validar firma HMAC del body
        if (!$signatureHeader) {
            Log::warning('Onepay webhook sin signature', [
                'all_headers' => $request->headers->all(),
            ]);

            return response()->json(['message' => 'Sin firma'], 401);
        }

        // Onepay casi seguro firma el body bruto con HMAC-SHA256
        $calculatedSignature = hash_hmac('sha256', $rawBody, $secretKey);

        if (!hash_equals($calculatedSignature, $signatureHeader)) {
            Log::warning('Onepay webhook firma HMAC inválida', [
                'expected_signature' => $calculatedSignature,
                'received_signature' => $signatureHeader,
                'all_headers'        => $request->headers->all(),
            ]);

            return response()->json(['message' => 'Firma inválida'], 401);
        }

        // 3) Si llegamos aquí, el webhook es válido ✅
        $payload = $request->json()->all();

        Log::info('Onepay webhook recibido y validado correctamente', [
            'headers' => $request->headers->all(),
            'raw'     => $rawBody,
            'payload' => $payload,
        ]);

        // ==========================
        // Aquí ya puedes procesar el evento
        // Ejemplo:
        // - $event = $payload['event'] ?? $payload['type'] ?? null;
        // - $data  = $payload['data'] ?? [];
        // - Buscar factura por referencia / invoice_id y marcar como pagada
        // ==========================

        return response()->json(['ok' => true]);
    }
}
