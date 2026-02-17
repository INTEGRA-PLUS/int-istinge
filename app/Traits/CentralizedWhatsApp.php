<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait CentralizedWhatsApp
{
    /**
     * Registra un mensaje de WhatsApp en el sistema centralizado.
     *
     * @param string $phoneNumberId ID del número de teléfono de la instancia Meta.
     * @param string $phone Teléfono del destinatario (con prefijo).
     * @param string $wamid ID del mensaje devuelto por Meta.
     * @param string $content Contenido completo del mensaje procesado.
     * @param string $name Nombre del contacto.
     * @param string $type Tipo de mensaje (por defecto 'template').
     * @param string $status Estado del mensaje (por defecto 'sent').
     * @return void
     */
    public function registerCentralizedBatch(string $phoneNumberId, string $phone, string $wamid, string $content, string $name, string $type = 'template', string $status = 'sent')
    {
        try {
            Http::withHeaders([
                'X-Instance-Token' => $phoneNumberId,
            ])->post('http://whatsapp.integracolombia.com/api/v1/messages/register', [
                'to'      => $phone,
                'wamid'   => $wamid,
                'content' => $content,
                'type'    => $type,
                'status'  => $status,
                'name'    => $name,
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncing WhatsApp message to central chat: ' . $e->getMessage());
        }
    }
}
