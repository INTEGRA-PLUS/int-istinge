@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            @if(!$instance)
                <div class="alert alert-warning mt-3 mb-0">
                    No se encontró una instancia configurada para Chat IA (type = 2).
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
                    <span>Chats</span>
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

                            {{-- Usamos <a> para que sea clickeable --}}
                            <button type="button"
                                class="list-group-item list-group-item-action chat-ia-contact {{ $index === 0 ? 'active' : '' }}"
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
                                No hay contactos registrados aún en el canal de IA.
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
                            {{-- Estos se actualizarán por JS --}}
                            <div><strong class="chat-contact-name">Selecciona un contacto</strong></div>
                            <div class="small text-muted chat-contact-subtitle">
                                Conversación con IA
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
                        {{-- Aquí se van a pintar los mensajes por JS --}}
                        <div class="text-muted text-center mt-5">
                            Selecciona un contacto en la izquierda para ver la conversación.
                        </div>
                    </div>
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
                            Esta es una interfaz de ejemplo. Aquí luego puedes conectar la lógica real del Chat IA.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        // Conversaciones: { uuid: [ {id, body, sentByMe, createdAt, ...}, ... ], ... }
        const messagesByContact = @json($messagesByContact ?? []);

        console.log('🔍 Todas las conversaciones agrupadas por contacto:', messagesByContact);
        console.log('🔍 Claves disponibles:', Object.keys(messagesByContact));

        const $contactItems = $('.chat-ia-contact');
        const $chatMessages = $('#chat-messages');
        const $chatBody     = $('#chat-body');
        const $nameEl       = $('.chat-contact-name');
        const $subtitleEl   = $('.chat-contact-subtitle');

        function renderMessages(uuid, name, phone) {
            console.log('📌 Intentando renderizar mensajes para UUID:', uuid);
            console.log('📌 Tipo de UUID:', typeof uuid);
            
            // Buscar los mensajes con conversión de tipo
            const mensajes = messagesByContact[uuid] || messagesByContact[String(uuid)] || [];

            console.log('💬 Mensajes encontrados para', uuid, ':', mensajes);
            console.log('💬 Cantidad de mensajes:', mensajes.length);

            // Header
            $nameEl.text(name || 'Sin nombre');
            $subtitleEl.text(
                phone ? phone + ' · Conversación con IA' : 'Conversación con IA'
            );

            // Limpiar mensajes
            $chatMessages.empty();

            if (!mensajes.length) {
                $chatMessages.html(
                    '<div class="text-muted text-center mt-4">No hay mensajes para este contacto.</div>'
                );
                return;
            }

            // Pintar mensajes
            mensajes.forEach(function (m) {
                const fromMe = m.sentByMe === true;
                const texto  = m.body || '';
                const fecha  = (m.createdAt || '').toString();
                const hora   = fecha ? fecha.substring(11, 16) : ''; // HH:MM

                const $wrapper = $('<div>')
                    .addClass('d-flex mb-3 ' + (fromMe ? 'justify-content-end' : ''));

                const $bubble = $('<div>')
                    .addClass('p-2 rounded')
                    .css({
                        maxWidth: '70%',
                        background: fromMe ? '#dcf8c6' : '#ffffff',
                        border: fromMe ? 'none' : '1px solid #e0e0e0'
                    });

                const $label = $('<div>')
                    .addClass('small mb-1 font-weight-bold')
                    .css('color', fromMe ? '#075e54' : '#128c7e')
                    .text(fromMe ? 'IA' : 'Cliente');

                const $body = $('<div>')
                    .css('word-wrap', 'break-word')
                    .text(texto);

                const $time = $('<div>')
                    .addClass('small text-muted text-right mt-1')
                    .text(hora || '');

                $bubble.append($label, $body, $time);
                $wrapper.append($bubble);
                $chatMessages.append($wrapper);
            });

            // Scroll al final
            if ($chatBody.length) {
                setTimeout(function() {
                    $chatBody.scrollTop($chatBody[0].scrollHeight);
                }, 100);
            }
        }

        // Click en contactos
        $contactItems.on('click', function (e) {
            e.preventDefault();

            const $this = $(this);
            const uuid  = $this.data('uuid');
            const name  = $this.data('name');
            const phone = $this.data('phone');

            console.log('👉 Click en contacto:', { uuid, name, phone });
            console.log('👉 Tipo de UUID recibido:', typeof uuid);

            // marcar activo en la lista
            $contactItems.removeClass('active');
            $this.addClass('active');

            if (uuid) {
                renderMessages(uuid, name, phone);
            } else {
                console.warn('⚠ Este contacto no tiene uuid, no se pueden cargar mensajes');
                $chatMessages.html(
                    '<div class="text-muted text-center mt-4">⚠️ Este contacto no tiene UUID válido</div>'
                );
            }
        });

        // Si hay un contacto marcado como activo, disparar su click al cargar
        const $firstActive = $('.chat-ia-contact.active').first();
        if ($firstActive.length) {
            console.log('✅ Disparando click en el primer contacto activo');
            $firstActive.trigger('click');
        } else {
            console.log('ℹ No hay contacto activo por defecto');
        }
    });
</script>
@endpush
