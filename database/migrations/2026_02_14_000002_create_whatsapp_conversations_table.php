<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappConversationsTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instance_id'); // Referencia a instances
            $table->string('wa_id')->index(); // WhatsApp ID del cliente
            $table->string('phone_number', 20); // Número con código país
            $table->string('name', 100)->nullable();
            $table->string('profile_pic_url')->nullable();
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->enum('status', ['open', 'closed', 'pending'])->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable(); // user_id del agente
            $table->integer('unread_count')->default(0);
            $table->json('metadata')->nullable(); // Datos adicionales
            $table->timestamps();
            
            // Índices
            $table->index(['instance_id', 'status']);
            $table->index('last_message_at');
            $table->unique(['instance_id', 'wa_id']); // Un cliente por instancia
            
            // Foreign keys
            $table->foreign('instance_id')->references('id')->on('instances')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
}
