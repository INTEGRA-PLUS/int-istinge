<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Support\Str;

class OnepayService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $secretToken;
    protected $headers;

    public function __construct()
    {
        // URL base de Onepay
        $this->baseUri     = env('ONEPAY_URL', 'https://api.onepay.la/v1');
        $this->secretToken = (string) env('ONEPAY_TOKEN');

        $this->headers = [
            'cache-control' => 'no-cache',
            'content-type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->secretToken,
        ];
    }

    public function resolveAuthorization(&$queryParams, &$formParams, &$headers)
    {
        // Por si el trait usa este hook para auth
        $headers['Authorization'] = 'Bearer ' . $this->secretToken;
    }

    /**
     * Genera una idempotency-key única para Onepay
     */
    protected function generateIdempotencyKey(): string
    {
        return 'inv_' . Str::uuid()->toString();
    }

    /**
     * Crear factura (invoice) en Onepay
     */
    public function createInvoice(array $body, string $idempotencyKey = null)
    {
        if (!$idempotencyKey) {
            $idempotencyKey = $this->generateIdempotencyKey();
        }

        $headers = $this->headers;
        $headers['x-idempotency'] = $idempotencyKey;

        return $this->makeRequest(
            'POST',
            $this->baseUri . '/invoices',
            [],           // query params
            $body,        // body (el trait lo envía como JSON por el content-type)
            $headers,
            true          // true => esperamos JSON
        );
    }

    /**
     * Actualizar factura en Onepay
     *
     * @param string $invoiceId   ID de la factura en Onepay (invoice_id)
     * @param array  $body        Campos a actualizar (reference, provider_id, amount, name, phone, email)
     */
    public function updateInvoice(string $invoiceId, array $body)
    {
        $headers = $this->headers;

        return $this->makeRequest(
            'PUT',
            $this->baseUri . '/invoices/' . $invoiceId,
            [],           // query params
            $body,        // body JSON
            $headers,
            true          // esperamos JSON
        );
    }

    /**
     * Eliminar factura en Onepay
     *
     * @param string $invoiceId   ID de la factura en Onepay (invoice_id)
     */
    public function deleteInvoice(string $invoiceId)
    {
        $headers = $this->headers;

        return $this->makeRequest(
            'DELETE',
            $this->baseUri . '/invoices/' . $invoiceId,
            [],           
            [],           
            $headers,
            true          
        );
    }

    
}
