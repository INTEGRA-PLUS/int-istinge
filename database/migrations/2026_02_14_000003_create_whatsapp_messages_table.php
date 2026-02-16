<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->string('wamid')->unique(); // WhatsApp Message ID
            $table->enum('type', ['text', 'image', 'document', 'audio', 'video', 'sticker', 'location', 'contacts', 'template']);
            $table->text('content')->nullable(); // Texto o caption
            $table->string('media_id')->nullable(); // ID de Meta para media
            $table->string('media_url')->nullable(); // URL del archivo descargado
            $table->string('media_mime_type')->nullable();
            $table->string('filename')->nullable(); // Para documentos
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('status', ['sent', 'delivered', 'read', 'failed', 'pending'])->default('pending');
            $table->unsignedBigInteger('sent_by')->nullable(); // user_id (null = cliente)
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Datos adicionales
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('conversation_id')->references('id')->on('whatsapp_conversations')->onDelete('cascade');
            $table->foreign('sent_by')->references('id')->on('users')->onDelete('set null');
            
            // Índices
            $table->index(['conversation_id', 'created_at']);
            $table->index('wamid');
            $table->index(['direction', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_messages');
    }
}
