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

                {{-- Header --}}
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Chats IA</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                {{-- 🔎 Buscador --}}
                <div class="px-3 pt-2 pb-2 border-bottom">
                    <div class="input-group input-group-sm">
                        <input type="text"
                               id="contact-search-ia"
                               class="form-control"
                               placeholder="Buscar por nombre o número...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="btn-clear-search-ia">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <small id="search-helper-ia" class="text-muted d-block mt-1">
                        Escribe al menos 3 caracteres para buscar.
                    </small>
                    <small id="search-info-ia" class="text-muted d-none"></small>
                </div>

                {{-- Lista de contactos --}}
                <div class="card-body p-0 flex-grow-1" style="overflow-y: auto; max-height: 500px;">
                    <ul class="list-group list-group-flush" id="contacts-list-ia">
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
                                No hay contactos registrados aún en el canal IA.
                            </li>
                        @endforelse
                    </ul>
                </div>

                {{-- Paginación de contactos --}}
                @if(!empty($pagination))
                <div class="card-footer p-2" id="pagination-wrapper-ia">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted" id="pagination-info-ia">
                            Página {{ $pagination['page'] ?? 1 }} de {{ $pagination['totalPages'] ?? 1 }}
                            ({{ count($contacts) }} de {{ $pagination['total'] ?? 0 }})
                        </small>
                        <div class="btn-group btn-group-sm" role="group" id="pagination-controls-ia">
                            <button type="button"
                                    class="btn btn-outline-secondary"
                                    id="btn-prev-page-ia"
                                    data-page="{{ ($pagination['page'] ?? 1) - 1 }}"
                                    {{ !($pagination['hasPrevPage'] ?? false) ? 'disabled' : '' }}>
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-outline-secondary"
                                    id="btn-next-page-ia"
                                    data-page="{{ ($pagination['page'] ?? 1) + 1 }}"
                                    {{ !($pagination['hasNextPage'] ?? false) ? 'disabled' : '' }}>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endif
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
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <div><strong class="chat-contact-name-ia">Selecciona un contacto</strong></div>
                            <div class="small text-muted chat-contact-subtitle-ia">
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
                <div id="chat-body-ia" class="card-body" style="overflow-y: auto; background: #f5f5f5;">
                    <div id="chat-messages-ia">
                        <div class="text-muted text-center mt-5">
                            Selecciona un contacto en la izquierda para ver la conversación.
                        </div>
                    </div>
                </div>

                {{-- Input --}}
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
                            (Luego conectamos envío real. Por ahora solo lectura.)
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
    .chat-ia-contact.active {
        background-color: #007bff !important;
        color: #fff !important;
    }
    .chat-ia-contact.active .small,
    .chat-ia-contact.active strong {
        color: #fff !important;
    }
    .chat-ia-contact { min-height: 70px; }
</style>
@endpush

@section('scripts')
<script>
(function() {
    'use strict';

    // URLs (IA)
    var baseMessagesUrl = @json(route('crm.chatIA.messages', ['uuid' => 'UUID_PLACEHOLDER']));
    var loadMoreUrl     = @json(route('crm.chatIA.loadMore'));
    var searchUrl       = @json(route('crm.chatIA.search'));

    // Cache local
    var messagesByContact = {};

    // Elementos
    var $chatMessages    = $('#chat-messages-ia');
    var $chatBody        = $('#chat-body-ia');
    var $nameEl          = $('.chat-contact-name-ia');
    var $subtitleEl      = $('.chat-contact-subtitle-ia');
    var $contactsList    = $('#contacts-list-ia');

    var $paginationInfo  = $('#pagination-info-ia');
    var $btnPrev         = $('#btn-prev-page-ia');
    var $btnNext         = $('#btn-next-page-ia');
    var $paginationWrap  = $('#pagination-wrapper-ia');

    var initialContactsHtml = $contactsList.html();

    // Buscador
    var $searchInput = $('#contact-search-ia');
    var $searchHelper = $('#search-helper-ia');
    var $searchInfo = $('#search-info-ia');
    var $btnClearSearch = $('#btn-clear-search-ia');
    var searchTimeout = null;
    var isSearching = false;

    function createContactHTML(contact, isActive) {
        var name       = contact.name || null;
        var phone      = contact.phone || '';
        var profilePic = contact.profilePic || null;
        var channel    = (contact.channel && contact.channel.name) ? contact.channel.name : null;
        var tags       = contact.tags || [];
        var uuid       = contact.uuid || null;

        var displayName = name || (phone || 'Sin nombre');
        var cleanName   = displayName.replace(/\s+/g, '');
        var initial     = cleanName.charAt(0).toUpperCase();
        var activeClass = isActive ? 'active' : '';

        var html = '';
        html += '<button type="button" class="list-group-item list-group-item-action chat-ia-contact ' + activeClass + '" ';
        html += 'data-uuid="' + uuid + '" data-name="' + displayName + '" data-phone="' + phone + '">';
        html += '<div class="d-flex align-items-center">';

        if (profilePic) {
            html += '<img src="' + profilePic + '" alt="Avatar" class="rounded-circle mr-3" style="width:40px;height:40px;object-fit:cover;">';
        } else {
            html += '<div class="rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#e9ecef;">';
            html += '<span class="font-weight-bold">' + initial + '</span></div>';
        }

        html += '<div class="flex-grow-1">';
        html += '  <div class="d-flex justify-content-between"><strong>' + displayName + '</strong></div>';
        html += '  <div class="small text-muted">' + (phone || 'Sin número');
        if (channel) html += ' · ' + channel;
        html += '  </div>';

        if (tags.length > 0) {
            html += '<div class="small mt-1">';
            tags.forEach(function(tag) {
                var tagName = typeof tag === 'object' ? (tag.name || 'Tag') : tag;
                html += '<span class="badge badge-light border mr-1">' + tagName + '</span>';
            });
            html += '</div>';
        }

        html += '</div></div></button>';
        return html;
    }

    function loadContactsPage(page) {
        if (!$btnPrev.length || !$btnNext.length) return;

        $btnPrev.prop('disabled', true);
        $btnNext.prop('disabled', true);

        $contactsList.html('<li class="list-group-item text-center"><i class="fas fa-spinner fa-spin"></i> Cargando contactos...</li>');

        $.ajax({
            url: loadMoreUrl,
            method: 'GET',
            data: { page: page },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.contacts && response.contacts.length > 0) {
                    $contactsList.empty();

                    response.contacts.forEach(function(contact, index) {
                        $contactsList.append(createContactHTML(contact, index === 0));
                    });

                    if (response.pagination && $paginationInfo.length) {
                        var p = response.pagination;
                        $paginationInfo.text(
                            'Página ' + p.page + ' de ' + p.totalPages +
                            ' (' + response.contacts.length + ' de ' + p.total + ')'
                        );
                        $btnPrev.data('page', p.page - 1).prop('disabled', !p.hasPrevPage);
                        $btnNext.data('page', p.page + 1).prop('disabled', !p.hasNextPage);
                    }

                    var $firstContact = $('.chat-ia-contact').first();
                    if ($firstContact.length) {
                        loadMessages($firstContact.data('uuid'), $firstContact.data('name'), $firstContact.data('phone'));
                    }
                } else {
                    $contactsList.html('<li class="list-group-item text-center text-muted">No hay contactos en esta página.</li>');
                }
            },
            error: function() {
                $contactsList.html('<li class="list-group-item text-center text-danger">Error al cargar contactos.</li>');
            },
            complete: function() {
                if ($btnPrev.length && $btnNext.length) {
                    $btnPrev.prop('disabled', false);
                    $btnNext.prop('disabled', false);
                }
            }
        });
    }

    if ($btnPrev.length) {
        $btnPrev.on('click', function() {
            if (isSearching) return;
            var page = $(this).data('page');
            if (page > 0) loadContactsPage(page);
        });
    }
    if ($btnNext.length) {
        $btnNext.on('click', function() {
            if (isSearching) return;
            var page = $(this).data('page');
            loadContactsPage(page);
        });
    }

    function renderMessages(uuid, name, phone) {
        var mensajes = messagesByContact[uuid] || [];

        $nameEl.text(name || 'Sin nombre');
        $subtitleEl.text(phone ? (phone + ' · Conversación con IA') : 'Conversación con IA');

        $chatMessages.empty();

        if (!mensajes.length) {
            $chatMessages.html('<div class="text-muted text-center mt-4">No hay mensajes para este contacto.</div>');
            return;
        }

        $.each(mensajes, function (i, m) {
            var fromMe = m.sentByMe === true;

            var texto  = m.body || '';
            var fecha  = (m.createdAt || '').toString();
            var hora   = fecha ? fecha.substring(11, 16) : '';

            var $wrapper = $('<div>')
                .addClass('d-flex mb-2')
                .css('justify-content', fromMe ? 'flex-end' : 'flex-start');

            var bubbleStyles = {
                maxWidth: '70%',
                padding: '10px 12px',
                borderRadius: '10px',
                boxShadow: '0 1px 1px rgba(0,0,0,0.1)',
                background: fromMe ? '#d1f7c4' : '#ffffff',
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

        if ($chatBody.length) {
            setTimeout(function() {
                $chatBody.scrollTop($chatBody[0].scrollHeight);
            }, 100);
        }
    }

    function loadMessages(uuid, name, phone) {
        if (!uuid) {
            $chatMessages.html('<div class="text-muted text-center mt-4">⚠️ Este contacto no tiene UUID válido</div>');
            return;
        }

        if (messagesByContact[uuid]) {
            renderMessages(uuid, name, phone);
            return;
        }

        var url = baseMessagesUrl.replace('UUID_PLACEHOLDER', uuid);

        $chatMessages.html('<div class="text-muted text-center mt-4"><i class="fas fa-spinner fa-spin"></i> Cargando mensajes...</div>');

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    messagesByContact[uuid] = res.messages || [];
                    renderMessages(uuid, name, phone);
                } else {
                    messagesByContact[uuid] = [];
                    $chatMessages.html('<div class="text-muted text-center mt-4">No se pudieron obtener mensajes.</div>');
                }
            },
            error: function() {
                $chatMessages.html('<div class="text-muted text-center mt-4">Error al cargar mensajes.</div>');
            }
        });
    }

    // Click contacto (delegado)
    $(document).on('click', '.chat-ia-contact', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $this = $(this);
        var uuid  = $this.data('uuid');
        var name  = $this.data('name');
        var phone = $this.data('phone');

        $('.chat-ia-contact').removeClass('active');
        $this.addClass('active');

        loadMessages(uuid, name, phone);
    });

    // =============== BUSCADOR ===============
    function performSearch(query) {
        query = (query || '').trim();

        if (!query) {
            isSearching = false;
            $searchInfo.addClass('d-none').text('');
            $searchHelper.removeClass('d-none');
            $contactsList.html(initialContactsHtml);
            if ($paginationWrap.length) $paginationWrap.show();
            return;
        }

        if (query.length < 3) {
            $searchInfo.removeClass('d-none').removeClass('text-danger').addClass('text-muted')
                .text('Escribe al menos 3 caracteres para buscar...');
            return;
        }

        isSearching = true;
        if ($paginationWrap.length) $paginationWrap.hide();

        $searchHelper.addClass('d-none');
        $searchInfo.removeClass('d-none').removeClass('text-danger').addClass('text-muted')
            .text('Buscando "' + query + '" ...');

        $contactsList.html('<li class="list-group-item text-center"><i class="fas fa-spinner fa-spin"></i> Buscando contactos...</li>');

        $.ajax({
            url: searchUrl,
            method: 'GET',
            dataType: 'json',
            data: { q: query },
            success: function(res) {
                if (res.status === 'success') {
                    var contacts = res.contacts || [];
                    var total = res.total || contacts.length;

                    $contactsList.empty();

                    if (!contacts.length) {
                        $contactsList.html('<li class="list-group-item text-center text-muted">No se encontraron contactos para "' + query + '".</li>');
                        $searchInfo.removeClass('text-muted').addClass('text-danger').text('0 resultados para "' + query + '"');
                        return;
                    }

                    contacts.forEach(function(contact, index) {
                        $contactsList.append(createContactHTML(contact, index === 0));
                    });

                    $searchInfo.removeClass('text-danger').addClass('text-muted')
                        .text(total + ' resultado(s) para "' + query + '"');

                    var $firstContact = $('.chat-ia-contact').first();
                    if ($firstContact.length) {
                        loadMessages($firstContact.data('uuid'), $firstContact.data('name'), $firstContact.data('phone'));
                    }
                } else {
                    $contactsList.html('<li class="list-group-item text-center text-danger">Error en la búsqueda.</li>');
                    $searchInfo.removeClass('text-muted').addClass('text-danger').text(res.message || 'Error en la búsqueda.');
                }
            },
            error: function() {
                $contactsList.html('<li class="list-group-item text-center text-danger">Error al buscar contactos.</li>');
                $searchInfo.removeClass('text-muted').addClass('text-danger').text('Error al buscar contactos.');
            }
        });
    }

    $searchInput.on('keyup', function() {
        var q = $(this).val();
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() { performSearch(q); }, 400);
    });

    $btnClearSearch.on('click', function() {
        $searchInput.val('');
        performSearch('');
    });

    // =============== Primer contacto ===============
    $(document).ready(function() {
        var $firstActive = $('.chat-ia-contact.active').first();
        if ($firstActive.length) {
            var uuid  = $firstActive.data('uuid');
            if (uuid) {
                loadMessages(uuid, $firstActive.data('name'), $firstActive.data('phone'));
            }
        }
    });

})();
</script>
@endsection
