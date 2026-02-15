<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConversation extends Model
{
    protected $table = 'whatsapp_conversations';
    protected $fillable = [
        'instance_id',
        'wa_id',
        'phone_number',
        'name',
        'profile_pic_url',
        'last_message',
        'last_message_at',
        'status',
        'assigned_to',
        'unread_count',
        'metadata'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = ['initials'];

    /**
     * Relación con la instancia
     */
    public function instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
    }

    /**
     * Relación con mensajes
     */
    public function messages()
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id');
    }

    /**
     * Relación con agente asignado
     */
    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Marcar conversación como leída
     */
    public function markAsRead()
    {
        $this->update(['unread_count' => 0]);
    }

    /**
     * Incrementar contador de no leídos
     */
    public function incrementUnread()
    {
        $this->increment('unread_count');
    }

    /**
     * Obtener iniciales para avatar
     */
    public function getInitialsAttribute()
    {
        $words = explode(' ', $this->name ?? 'U');
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($words[0], 0, 2));
    }

    /**
     * Scope para conversaciones abiertas
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope para conversaciones de una instancia
     */
    public function scopeForInstance($query, $instanceId)
    {
        return $query->where('instance_id', $instanceId);
    }

    /**
     * Scope para buscar por nombre o teléfono
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone_number', 'like', "%{$search}%");
        });
    }
}
