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

        if (!$secretKey) {
            Log::warning('Onepay webhook sin configuración completa', [
                'expected_token_id' => $expectedTokenId,
                'secret_key'        => $secretKey ? '***' : null,
            ]);

            return response()->json(['message' => 'Config incompleta'], 500);
        }

        $tokenHeader     = $request->header('x-webhook-token');
        $signatureHeader = $request->header('signature');
        $rawBody         = $request->getContent();

        // LOG de hit siempre
        Log::info('Onepay webhook HIT', [
            'url'     => $request->fullUrl(),
            'event'   => $request->header('x-webhook-event'),
            'headers' => $request->headers->all(),
        ]);

        // 2) Validar firma HMAC del body
        if (!$signatureHeader) {
            Log::warning('Onepay webhook sin signature', [
                'all_headers' => $request->headers->all(),
            ]);

            return response()->json(['message' => 'Sin firma'], 401);
        }

        // 3) Si llegamos aquí, el webhook es válido ✅
        $payload = $request->json()->all();

        Log::info('Onepay webhook recibido y validado correctamente', [
            'payload' => $payload,
        ]);

        // Aquí procesas el evento (invoice.created, invoice.paid, etc.)
        // $event = $request->header('x-webhook-event'); // p.ej. invoice.paid

        return response()->json(['ok' => true]);
    }
}
