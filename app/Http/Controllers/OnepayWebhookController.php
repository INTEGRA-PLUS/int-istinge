<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Model\Ingresos\Factura;

class OnepayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Solo para referencia en logs (opcional)
        $expectedTokenId = env('ONEPAY_WEBHOOK_HEADER');   // wh_hdr_...
        $secretKey       = env('ONEPAY_WEBHOOK_SECRET');   // wh_tok_...
    
        $tokenHeader     = $request->header('x-webhook-token');
        $signatureHeader = $request->header('signature');
        $rawBody         = $request->getContent();
        $eventHeader     = $request->header('x-webhook-event'); // p.ej. invoice.paid
    
        // 🔔 LOG del HIT SIEMPRE
        Log::info('Onepay webhook HIT', [
            'url'           => $request->fullUrl(),
            'event'         => $eventHeader,
            'headers'       => $request->headers->all(),
            'token_header'  => $tokenHeader,
            'signature'     => $signatureHeader,
            'token_env'     => $expectedTokenId,
            'secret_env'    => $secretKey ? '***' : null,
        ]);
    
        // ⬇️ SIN VALIDACIONES: no bloqueamos por token ni firma
        // Solo seguimos y procesamos el payload
    
        $payload = $request->json()->all();
    
        Log::info('Onepay webhook payload recibido', [
            'event'   => $eventHeader,
            'payload' => $payload,
        ]);
    
        //   GUARDAR EN onepay_events
    
        $event = $eventHeader;
    
        if ($event === 'invoice.paid') {
            $onepayInvoiceId = data_get($payload, 'invoice.id')
                             ?? data_get($payload, 'data.id')
                             ?? null;
    
            // 💰 Monto correcto: viene en invoice.payment.amount
            $amount = (float) (
                data_get($payload, 'invoice.payment.amount')
                ?? data_get($payload, 'invoice.amount')      
                ?? data_get($payload, 'data.amount')
                ?? 0
            );
    
            // Moneda correcta: invoice.payment.currency (COP)
            $currency = data_get($payload, 'invoice.payment.currency')
                      ?? data_get($payload, 'invoice.currency')
                      ?? data_get($payload, 'data.currency')
                      ?? null;
    
            $factura   = null;
            $facturaId = null;
            $empresaId = null;
    
            // 1) Por onepay_invoice_id (si la guardas así en tu tabla facturas)
            if ($onepayInvoiceId) {
                $factura = Factura::where('onepay_invoice_id', $onepayInvoiceId)->first();
            }
    
            if (!$factura) {
                $facturaIdMeta = data_get($payload, 'invoice.metadata.factura_id');
                if ($facturaIdMeta) {
                    $factura = Factura::find($facturaIdMeta);
                }
            }
    
            if ($factura) {
                $facturaId = $factura->id;
                $empresaId = $factura->empresa;
            } else {
                // Si no encontró factura, al menos intentamos tomar empresa de metadata
                $empresaId = data_get($payload, 'invoice.metadata.empresa_id');
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
                        // Actualizamos el registro existente (por reintentos)
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
            // Otros eventos, sólo log (si quieres diferenciarlos)
            Log::info("Onepay evento no manejado específicamente (se ignora): {$event}", [
                'payload' => $payload,
            ]);
        }
    
        // Respuesta final a Onepay
        return response()->json(['ok' => true]);
    }
}
