<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Factura; // ajusta el namespace si es distinto

class OnepayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1️⃣ Validar header + secret
        $headerName     = env('ONEPAY_WEBHOOK_HEADER');   // p.ej. wh_hdr_ysWnp...
        $expectedSecret = env('ONEPAY_WEBHOOK_SECRET');   // p.ej. wh_tok_m7B...

        if (!$headerName || !$expectedSecret) {
            Log::warning('Onepay webhook sin configuración de header/secret', [
                'headers' => $request->headers->all(),
            ]);

            return response()->json(['message' => 'Config incompleta'], 500);
        }

        $receivedSecret = $request->header($headerName);

        if (!$receivedSecret || !hash_equals($expectedSecret, $receivedSecret)) {
            Log::warning('Onepay webhook firma inválida', [
                'header_name'      => $headerName,
                'received_secret'  => $receivedSecret,
            ]);

            return response()->json(['message' => 'Firma inválida'], 401);
        }

        // 2️⃣ Leer el payload (crudo y decodificado)
        $rawBody = $request->getContent();
        $payload = $request->json()->all();

        Log::info('Onepay webhook recibido', [
            'headers' => $request->headers->all(),
            'raw'     => $rawBody,
            'payload' => $payload,
        ]);

        // 3️⃣ (PRIMERA FASE) Sólo responder OK y loguear
        //    Después de ver el log ya mapeamos bien qué campos usar.
        return response()->json(['ok' => true]);
    }
}
