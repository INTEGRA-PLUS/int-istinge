<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';
    protected $fillable = [
        'conversation_id',
        'wamid',
        'type',
        'content',
        'media_id',
        'media_url',
        'media_mime_type',
        'filename',
        'direction',
        'status',
        'sent_by',
        'sent_at',
        'delivered_at',
        'read_at',
        'error_message',
        'metadata'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relación con conversación
     */
    public function conversation()
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    /**
     * Relación con usuario que envió (si es outbound)
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Verificar si es del cliente
     */
    public function isFromCustomer()
    {
        return $this->direction === 'inbound';
    }

    /**
     * Verificar si tiene media
     */
    public function hasMedia()
    {
        return in_array($this->type, ['image', 'document', 'audio', 'video']);
    }

    /**
     * Scope mensajes entrantes
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    /**
     * Scope mensajes salientes
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    /**
     * Scope por estado
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
