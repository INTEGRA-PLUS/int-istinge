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
                            <a href="#"
                               class="list-group-item contacto-item {{ $index === 0 ? 'active' : '' }}"
                               data-uuid="{{ $uuid }}"
                               data-name="{{ $displayName }}"
                               data-phone="{{ $phone }}"
                            >
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
                            </a>
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

@push('scripts')
<script>
    (function () {
        // Mensajes entregados desde el backend: { uuid: [ {id, body, sentByMe, createdAt, ...}, ... ], ... }
        const messagesByContact = @json($messagesByContact ?? []);

        const contactItems = document.querySelectorAll('.contacto-item');
        const chatMessages = document.getElementById('chat-messages');
        const chatBody     = document.getElementById('chat-body');
        const nameEl       = document.querySelector('.chat-contact-name');
        const subtitleEl   = document.querySelector('.chat-contact-subtitle');

        function renderMessages(uuid, name, phone) {
            const mensajes = messagesByContact[uuid] || [];

            // Header
            nameEl.textContent = name || 'Sin nombre';
            subtitleEl.textContent = phone ? phone + ' · Conversación con IA' : 'Conversación con IA';

            // Limpiar mensajes anteriores
            chatMessages.innerHTML = '';

            if (!mensajes.length) {
                chatMessages.innerHTML = '<div class="text-muted text-center mt-4">No hay mensajes para este contacto.</div>';
                return;
            }

            // Pintar mensajes
            mensajes.forEach(function (m) {
                // Según tu JSON:
                // "body": "...",
                // "sentByMe": true/false,
                // "createdAt": "2025-11-24T13:18:59.041Z",
                const fromMe = m.sentByMe === true;
                const texto  = m.body || '';
                const fecha  = (m.createdAt || '').toString();
                const hora   = fecha ? fecha.substring(11, 16) : ''; // HH:MM de la ISO simple

                const wrapper = document.createElement('div');
                wrapper.className = 'd-flex mb-3 ' + (fromMe ? 'justify-content-end' : '');

                const bubble = document.createElement('div');
                bubble.className = 'p-2 rounded';
                bubble.style.maxWidth = '70%';
                bubble.style.background = fromMe ? '#dcf8c6' : '#ffffff';

                const label = document.createElement('div');
                label.className = 'small mb-1';
                label.innerHTML = '<strong>' + (fromMe ? 'IA' : 'Cliente') + '</strong>';

                const body = document.createElement('div');
                body.textContent = texto;

                const time = document.createElement('div');
                time.className = 'small text-muted text-right mt-1';
                time.textContent = hora || '';

                bubble.appendChild(label);
                bubble.appendChild(body);
                bubble.appendChild(time);
                wrapper.appendChild(bubble);
                chatMessages.appendChild(wrapper);
            });

            // Scroll al final
            if (chatBody) {
                chatBody.scrollTop = chatBody.scrollHeight;
            }
        }

        // Listeners de click en contactos
        contactItems.forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                const uuid  = this.dataset.uuid;
                const name  = this.dataset.name;
                const phone = this.dataset.phone;

                // marcar activo
                contactItems.forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                if (uuid) {
                    renderMessages(uuid, name, phone);
                }
            });
        });

        // Si hay un contacto ya marcado como activo, cargarlo al inicio
        const firstActive = document.querySelector('.contacto-item.active');
        if (firstActive) {
            firstActive.click();
        }
    })();
</script>
@endpush
