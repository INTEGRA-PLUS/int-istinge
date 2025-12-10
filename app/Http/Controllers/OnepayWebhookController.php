<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        $eventHeader = $request->header('x-webhook-event');
        Log::info('Onepay webhook HIT', [
            'url'     => $request->fullUrl(),
            'event'   => $eventHeader,
            'headers' => $request->headers->all(),
        ]);

        if (!$tokenHeader) {
            Log::warning('Onepay webhook sin x-webhook-token', [
                'all_headers' => $request->headers->all(),
            ]);

            return response()->json(['message' => 'Token faltante'], 401);
        }

        // Si tenemos un token esperado y no coincide, solo lo dejamos en log
        if ($expectedTokenId && $tokenHeader !== $expectedTokenId) {
            Log::warning('Onepay webhook token ID distinto (no se bloquea la petición)', [
                'expected_token_id' => $expectedTokenId,
                'received_token_id' => $tokenHeader,
            ]);
            // NO retornamos, seguimos para validar firma
        }

        // 2) Validar firma HMAC del body
        if (!$signatureHeader) {
            Log::warning('Onepay webhook sin signature', [
                'all_headers' => $request->headers->all(),
            ]);

            return response()->json(['message' => 'Sin firma'], 401);
        }

        // Onepay firma el body bruto con HMAC-SHA256 (hex)
        $calculatedSignature = hash_hmac('sha256', $rawBody, $secretKey);

        if (!hash_equals($calculatedSignature, $signatureHeader)) {
            Log::warning('Onepay webhook firma HMAC inválida', [
                'expected_signature' => $calculatedSignature,
                'received_signature' => $signatureHeader,
            ]);
            return response()->json(['message' => 'Firma inválida'], 401);
        }

        // 3) Si llegamos aquí, el webhook es válido ✅
        $payload = $request->json()->all();

        Log::info('Onepay webhook recibido y validado correctamente', [
            'payload' => $payload,
        ]);

        // ============================
        //   GUARDAR EN onepay_events
        // ============================

        $event = $eventHeader; // p.ej. 'invoice.paid'

        if ($event === 'invoice.paid') {

            // ⚠ Ajusta estas rutas data_get según el JSON real de Onepay
            // Ejemplos: invoice.id, data.id, object.id
            $onepayInvoiceId = data_get($payload, 'invoice.id')
                             ?? data_get($payload, 'data.id')
                             ?? null;

            $amount = (float) (
                data_get($payload, 'invoice.amount')
                ?? data_get($payload, 'data.amount')
                ?? 0
            );

            $currency = data_get($payload, 'invoice.currency')
                      ?? data_get($payload, 'data.currency')
                      ?? null;

            // Buscar la factura interna por onepay_invoice_id
            $factura   = null;
            $facturaId = null;
            $empresaId = null;

            if ($onepayInvoiceId) {
                $factura = Factura::where('onepay_invoice_id', $onepayInvoiceId)->first();

                if ($factura) {
                    $facturaId = $factura->id;
                    $empresaId = $factura->empresa;
                }
            }

            if (!$onepayInvoiceId) {
                Log::warning('Onepay invoice.paid sin onepayInvoiceId (invoice.id)', [
                    'payload' => $payload,
                ]);
            } else {
                try {
                    // Verificamos si ya existe un evento para este onepay_invoice_id
                    $existing = DB::table('onepay_events')
                        ->where('onepay_invoice_id', $onepayInvoiceId)
                        ->first();

                    $data = [
                        'onepay_invoice_id' => $onepayInvoiceId,
                        'factura_id'        => $facturaId,
                        'empresa_id'        => $empresaId,
                        'amount'            => $amount,
                        'currency'          => $currency,
                        'payload'           => json_encode($payload),
                        'status'            => 'pending',
                        'ingreso_id'        => null,
                        'processed_at'      => null,
                        'error_message'     => null,
                        'updated_at'        => now(),
                    ];

                    if ($existing) {
                        // Actualizamos el registro existente (por reintentos de webhook)
                        DB::table('onepay_events')
                            ->where('id', $existing->id)
                            ->update($data);
                    } else {
                        // Creamos un nuevo registro
                        $data['created_at'] = now();
                        DB::table('onepay_events')->insert($data);
                    }

                    Log::info('Onepay invoice.paid almacenado en onepay_events', [
                        'onepay_invoice_id' => $onepayInvoiceId,
                        'factura_id'        => $facturaId,
                        'empresa_id'        => $empresaId,
                        'amount'            => $amount,
                        'currency'          => $currency,
                    ]);

                } catch (\Throwable $e) {
                    Log::error('Error guardando onepay_events: ' . $e->getMessage(), [
                        'payload' => $payload,
                    ]);
                }
            }
        } else {
            // Otros eventos, sólo log si quieres
            Log::info("Onepay evento no manejado específicamente (se ignora): {$event}");
        }

        // Respuesta final a Onepay
        return response()->json(['ok' => true]);
    }
}
