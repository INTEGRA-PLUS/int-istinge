@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">
                <i class="fas fa-robot"></i> Chat IA
            </h4>
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

                {{-- 👇 OJO: flex-grow-1 + overflow-y --}}
                <div class="card-body p-0 flex-grow-1" style="overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        @forelse($contacts as $contact)
                            @php
                                $name       = $contact['name'] ?? null;
                                $phone      = $contact['phone'] ?? '';
                                $profilePic = $contact['profilePic'] ?? null;
                                $channel    = $contact['channel']['name'] ?? null;
                                $tags       = $contact['tags'] ?? [];

                                $displayName = $name ?: ($phone ?: 'Sin nombre');
                                $cleanName   = preg_replace('/\s+/', '', $displayName);
                                $initial     = strtoupper(substr($cleanName, 0, 1));
                            @endphp

                            <li class="list-group-item contacto-item" data-uuid="{{ $contact['uuid'] }}">
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
                            </li>
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
                            <div><strong>Cliente de prueba</strong></div>
                            <div class="small text-muted">
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
                <div class="card-body" style="overflow-y: auto; background: #f5f5f5;">
                    {{-- Mensaje del cliente --}}
                    <div class="d-flex mb-3">
                        <div class="p-2 rounded" style="background: #ffffff; max-width: 70%;">
                            <div class="small mb-1"><strong>Cliente</strong></div>
                            <div>Hola, tengo dudas sobre mi factura de este mes.</div>
                            <div class="small text-muted text-right mt-1">10:15</div>
                        </div>
                    </div>

                    {{-- Respuesta de la IA --}}
                    <div class="d-flex justify-content-end mb-3">
                        <div class="p-2 rounded" style="background: #dcf8c6; max-width: 70%;">
                            <div class="small mb-1"><strong>IA</strong></div>
                            <div>
                                Claro, con gusto te ayudo. ¿Me confirmas tu número de contrato o cédula
                                para revisar el detalle de tu factura?
                            </div>
                            <div class="small text-muted text-right mt-1">10:16</div>
                        </div>
                    </div>

                    {{-- Más mensajes de ejemplo --}}
                    <div class="d-flex mb-3">
                        <div class="p-2 rounded" style="background: #ffffff; max-width: 70%;">
                            <div class="small mb-1"><strong>Cliente</strong></div>
                            <div>Mi cédula es 123456789.</div>
                            <div class="small text-muted text-right mt-1">10:17</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-3">
                        <div class="p-2 rounded" style="background: #dcf8c6; max-width: 70%;">
                            <div class="small mb-1"><strong>IA</strong></div>
                            <div>
                                Gracias, ya estoy validando tu información. Veo que tu factura de este mes
                                corresponde al servicio de Internet fibra 100 Mbps por un valor de $85.000.
                            </div>
                            <div class="small text-muted text-right mt-1">10:18</div>
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
