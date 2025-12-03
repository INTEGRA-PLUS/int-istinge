@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            @if(!$instance)
                <div class="alert alert-warning mt-3 mb-0">
                    No se encontró una instancia configurada para Chat Meta (type = 1).
                    Por favor verifica la configuración de la instancia.
                </div>
            @endif
        </div>
    </div>

    <div class="row" style="height: calc(100vh - 150px);">
        {{-- Sidebar de chats --}}
        <div class="col-md-4 col-lg-3 d-none d-md-block">
            <div class="card h-100 d-flex flex-column">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Chats Meta</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <div class="card-body p-0 flex-grow-1" style="overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        @forelse($contacts as $index => $contact)
                            @php
                                $name       = $contact['name'] ?? null;
                                $phone      = $contact['phone'] ?? '';
                                $profilePic = $contact['profilePic'] ?? null;
                                $channel    = $contact['channel']['name'] ?? null;
                                $tags       = $contact['tags'] ?? [];
                                $uuid       = $contact['uuid'] ?? null;

                                $displayName = $name ?: ($phone ?: 'Sin nombre');
                                $cleanName   = preg_replace('/\s+/', '', $displayName);
                                $initial     = strtoupper(substr($cleanName, 0, 1));
                            @endphp

                            <button type="button"
                                class="list-group-item list-group-item-action chat-meta-contact {{ $index === 0 ? 'active' : '' }}"
                                data-uuid="{{ $uuid }}"
                                data-name="{{ $displayName }}"
                                data-phone="{{ $phone }}">
                                <div class="d-flex align-items-center">
                                    @if($profilePic)
                                        <img src="{{ $profilePic }}"
                                             alt="Avatar"
                                             class="rounded-circle mr-3"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    @else
                                        <div class="rounded-circle mr-3 d-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px; background: #e9ecef;">
                                            <span class="font-weight-bold">{{ $initial }}</span>
                                        </div>
                                    @endif

                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <strong>{{ $displayName }}</strong>
                                        </div>

                                        <div class="small text-muted">
                                            {{ $phone ?: 'Sin número' }}
                                            @if($channel)
                                                · {{ $channel }}
                                            @endif
                                        </div>

                                        @if(!empty($tags))
                                            <div class="small mt-1">
                                                @foreach($tags as $tag)
                                                    <span class="badge badge-light border">
                                                        {{ is_array($tag) ? ($tag['name'] ?? 'Tag') : $tag }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        @empty
                            <li class="list-group-item text-center text-muted">
                                No hay contactos registrados aún en el canal Meta.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Panel del chat --}}
        <div class="col-md-8 col-lg-9">
            <div class="card h-100 d-flex flex-column">
                {{-- Header del chat --}}
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle mr-3 d-flex align-items-center justify-content-center"
                             style="width: 40px; height: 40px; background: #e9ecef;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div><strong class="chat-contact-name">Selecciona un contacto</strong></div>
                            <div class="small text-muted chat-contact-subtitle">
                                Conversación con Meta
                            </div>
                        </div>
                    </div>
                    <div class="d-none d-md-block">
                        @if($instance)
                            <span class="badge badge-success">Instancia configurada</span>
                        @else
                            <span class="badge badge-secondary">Sin instancia</span>
                        @endif
                    </div>
                </div>

                {{-- Área de mensajes --}}
                <div id="chat-body" class="card-body" style="overflow-y: auto; background: #f5f5f5;">
                    <div id="chat-messages">
                        <div class="text-muted text-center mt-5">
                            Selecciona un contacto en la izquierda para ver la conversación.
                        </div>
                    </div>
                    <div id="ajax-debug"></div>
                </div>

                {{-- Input para escribir mensaje --}}
                <div class="card-footer">
                    <form action="javascript:void(0);">
                        <div class="input-group">
                            <div class="input-group-prepend d-none d-md-flex">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="far fa-smile"></i>
                                </button>
                            </div>
                            <input type="text" class="form-control" placeholder="Escribe un mensaje...">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                        <div class="small text-muted mt-1">
                            Esta es una interfaz de ejemplo. Aquí luego puedes conectar la lógica real del Chat Meta.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Forzar que el contacto activo se vea azul/blanco */
    .chat-meta-contact.active {
        background-color: #007bff !important;
        color: #fff !important;
    }

    .chat-meta-contact.active .small,
    .chat-meta-contact.active strong {
        color: #fff !important;
    }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        // Cache local de mensajes por contacto: { uuid: [ {id, body, ...}, ... ] }
        var messagesByContact = {};

        var $contactItems = $('.chat-meta-contact');
        var $chatMessages = $('#chat-messages');
        var $chatBody     = $('#chat-body');
        var $nameEl       = $('.chat-contact-name');
        var $subtitleEl   = $('.chat-contact-subtitle');

        // URL base del endpoint de mensajes, con un marcador para el UUID
        var baseMessagesUrl = @json(route('crm.chatMeta.messages', ['uuid' => 'UUID_PLACEHOLDER']));

        function renderMessages(uuid, name, phone) {
            var mensajes = messagesByContact[uuid] || [];

            console.log('💬 [META] Renderizando mensajes para', uuid, ' -> ', mensajes);

            $nameEl.text(name || 'Sin nombre');
            $subtitleEl.text(
                phone ? phone + ' · Conversación con Meta' : 'Conversación con Meta'
            );

            $chatMessages.empty();

            if (!mensajes.length) {
                $chatMessages.html(
                    '<div class="text-muted text-center mt-4">No hay mensajes para este contacto.</div>'
                );
                return;
            }

            // 🔽🔽🔽 AQUÍ VA EL NUEVO CÓDIGO DE LAS BURBUJAS 🔽🔽🔽
            $.each(mensajes, function (i, m) {
                var fromMe = m.sentByMe === true;
                var texto  = m.body || '';
                var fecha  = (m.createdAt || '').toString();
                var hora   = fecha ? fecha.substring(11, 16) : '';

                // Contenedor alineado izquierda/derecha
                var $wrapper = $('<div>')
                    .addClass('d-flex mb-2')
                    .css('justify-content', fromMe ? 'flex-end' : 'flex-start');

                // Estilo tipo WhatsApp
                var bubbleStyles = {
                    maxWidth: '70%',
                    padding: '10px 12px',
                    borderRadius: '10px',
                    boxShadow: '0 1px 1px rgba(0,0,0,0.1)',
                    background: fromMe ? '#d1f7c4' : '#ffffff',   // verde suave / blanco
                    border: fromMe ? '1px solid #b2e59e' : '1px solid #ddd',
                    color: '#333',
                    whiteSpace: 'pre-wrap',
                    wordBreak: 'break-word'
                };

                var $bubble = $('<div>').css(bubbleStyles);

                var $body = $('<div>').text(texto);

                var $time = $('<div>')
                    .addClass('small text-muted text-right mt-1')
                    .css({ fontSize: '11px' })
                    .text(hora || '');

                $bubble.append($body, $time);
                $wrapper.append($bubble);
                $chatMessages.append($wrapper);
            });
            // 🔼🔼🔼 FIN NUEVO CÓDIGO DE LAS BURBUJAS 🔼🔼🔼

            if ($chatBody.length) {
                setTimeout(function() {
                    $chatBody.scrollTop($chatBody[0].scrollHeight);
                }, 100);
            }
        }

        function loadMessages(uuid, name, phone) {
            // Si ya tenemos mensajes en cache, no pegamos al backend
            if (messagesByContact[uuid]) {
                console.log('♻ [META] Usando mensajes en cache para', uuid);
                renderMessages(uuid, name, phone);
                return;
            }

            // Construir URL reemplazando el placeholder
            var url = baseMessagesUrl.replace('UUID_PLACEHOLDER', uuid);

            console.log('🌐 [META] Llamando a AJAX para mensajes:', url);

            $chatMessages.html(
                '<div class="text-muted text-center mt-4">Cargando mensajes...</div>'
            );

            $.get(url)
                .done(function (res) {
                    $('#ajax-debug').html(
                        "<div class='alert alert-info'>AJAX ejecutado para UUID: "+ uuid +"</div>"
                    );

                    console.log('AJAX EJECUTADO:', res);
                    console.log('✅ [META] Respuesta completa del 2do paso para', uuid, ':', res);
                    console.log('📦 [META] Mensajes (res.messages) para', uuid, ':', res.messages);

                    if (res.status === 'success') {
                        messagesByContact[uuid] = res.messages || [];
                        renderMessages(uuid, name, phone);
                    } else {
                        messagesByContact[uuid] = [];
                        $chatMessages.html(
                            '<div class="text-muted text-center mt-4">No se pudieron obtener mensajes.</div>'
                        );
                    }
                })
                .fail(function (xhr) {
                    $('#ajax-debug').html(
                        "<div class='alert alert-danger'>AJAX ERROR para UUID: "+ uuid +"<br>Status: "+xhr.status+"</div>"
                    );

                    console.error('❌ AJAX ERROR:', xhr);
                    console.error('❌ [META] Error AJAX al cargar mensajes:', xhr);
                    $chatMessages.html(
                        '<div class="text-muted text-center mt-4">Error al cargar mensajes.</div>'
                    );
                });
        }

        // Click en contacto (para cuando el usuario cambie a otro)
        $contactItems.on('click', function (e) {
            e.preventDefault();

            var $this = $(this);
            var uuid  = $this.data('uuid');
            var name  = $this.data('name');
            var phone = $this.data('phone');

            console.log('👉 [META] Click en contacto:', { uuid: uuid, name: name, phone: phone });

            // Marcar visualmente el contacto activo
            $contactItems.removeClass('active');
            $this.addClass('active');

            if (uuid) {
                loadMessages(uuid, name, phone); // Aquí se llama a tu método del controller vía AJAX
            } else {
                console.warn('⚠ [META] Este contacto no tiene uuid, no se pueden cargar mensajes');
                $chatMessages.html(
                    '<div class="text-muted text-center mt-4">⚠️ Este contacto no tiene UUID válido</div>'
                );
            }
        });

        // 🚀 Al entrar a la vista: cargar mensajes del primer contacto activo (el azul)
        var $firstActive = $('.chat-meta-contact.active').first();
        if ($firstActive.length) {
            var firstUuid  = $firstActive.data('uuid');
            var firstName  = $firstActive.data('name');
            var firstPhone = $firstActive.data('phone');

            console.log('✅ [META] Cargando mensajes iniciales para primer contacto activo:', {
                uuid: firstUuid,
                name: firstName,
                phone: firstPhone
            });

            if (firstUuid) {
                loadMessages(firstUuid, firstName, firstPhone);
            }
        } else {
            console.log('ℹ [META] No hay contacto activo por defecto');
        }
    });
</script>
@endpush
