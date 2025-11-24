<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Factura;

class OnepayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1️⃣ Log básico para ver qué está mandando Onepay
        Log::info('Webhook Onepay recibido', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        // 2️⃣ (Opcional pero recomendado) Validar algún secreto / firma
        // Si Onepay envía algo como 'X-Signature' o 'X-Onepay-Signature', aquí lo validas.
        // Ejemplo genérico:
        /*
        $signature = $request->header('X-Onepay-Signature');
        $secret    = env('ONEPAY_WEBHOOK_SECRET'); // lo defines en tu .env

        $computed = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($computed, $signature)) {
            Log::warning('Firma de webhook Onepay inválida', [
                'received' => $signature,
                'computed' => $computed,
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        */

        $payload = $request->all();

        // 3️⃣ Identificar el invoice de Onepay
        // según el ejemplo de respuesta de creación que me mostraste,
        // el ID del invoice viene como "id" en la raíz:
        // {
        //   "id": "019aa718-53c4-717f-a72f-77c972d06c1a",
        //   ...
        // }
        $invoiceId = data_get($payload, 'id') 
            ?? data_get($payload, 'invoice.id'); // fallback si Onepay manda otro formato

        if (! $invoiceId) {
            Log::warning('Webhook Onepay sin invoiceId identificable', [
                'payload' => $payload,
            ]);
            return response()->json(['status' => 'ignored'], 200);
        }

        // 4️⃣ Buscar la factura por el onepay_invoice_id que guardas en store()
        $factura = Factura::where('onepay_invoice_id', $invoiceId)->first();

        if (! $factura) {
            Log::warning('Webhook Onepay: no se encontró factura con ese invoiceId', [
                'invoice_id' => $invoiceId,
            ]);
            return response()->json(['status' => 'not_found'], 200);
        }

        // 5️⃣ Obtener estados (depende del formato del webhook de Onepay)
        // Ejemplo: status de la factura e interno del pago:
        $invoiceStatus = data_get($payload, 'status');             // p.ej: "CREATED", "PAID", etc
        $paymentStatus = data_get($payload, 'payment.status');     // p.ej: "pending", "paid", "failed"
        $paymentLink   = data_get($payload, 'payment.payment_link'); // por si lo quieres guardar

        // 🔧 Aquí ya depende de los campos que tengas en tu tabla facturas.
        // Ejemplo si agregas columnas:
        //  - onepay_status
        //  - onepay_payment_status
        //  - onepay_payment_link

        $algoCambio = false;

        if ($invoiceStatus) {
            if (property_exists($factura, 'onepay_status') || array_key_exists('onepay_status', $factura->getAttributes())) {
                $factura->onepay_status = $invoiceStatus;
                $algoCambio = true;
            }
        }

        if ($paymentStatus) {
            if (property_exists($factura, 'onepay_payment_status') || array_key_exists('onepay_payment_status', $factura->getAttributes())) {
                $factura->onepay_payment_status = $paymentStatus;
                $algoCambio = true;
            }

            // Si quieres mapear el pago a tu lógica interna (por ejemplo marcar factura pagada):
            // OJO: ajusta al nombre real de tu columna (p.ej. estatus, estado, pagado, etc.)
            /*
            if ($paymentStatus === 'paid') {
                $factura->estatus_pago = 'PAGADA'; // ejemplo
                $algoCambio = true;
            }
            */
        }

        if ($paymentLink) {
            if (property_exists($factura, 'onepay_payment_link') || array_key_exists('onepay_payment_link', $factura->getAttributes())) {
                $factura->onepay_payment_link = $paymentLink;
                $algoCambio = true;
            }
        }

        // En cualquier caso guarda el último payload del webhook (útil para debug)
        if (property_exists($factura, 'onepay_last_webhook') || array_key_exists('onepay_last_webhook', $factura->getAttributes())) {
            $factura->onepay_last_webhook = json_encode($payload);
            $algoCambio = true;
        }

        if ($algoCambio) {
            $factura->save();
        }

        Log::info('Webhook Onepay procesado para factura', [
            'factura_id'   => $factura->id,
            'invoice_id'   => $invoiceId,
            'invoiceStatus'=> $invoiceStatus,
            'paymentStatus'=> $paymentStatus,
        ]);

        // 6️⃣ Responder 200 para que Onepay sepa que lo recibiste correctamente
        return response()->json(['status' => 'ok'], 200);
    }
}
