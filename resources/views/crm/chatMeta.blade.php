@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">
                <i class="fas fa-comment"></i> Chat Meta
            </h4>

            @if(!$instance)
                <div class="alert alert-warning mt-3 mb-0">
                    No se encontró una instancia configurada para Chat Meta (type = 3).
                    Verifica la configuración.
                </div>
            @endif
        </div>
    </div>

    @include("crm.chatIA_layout") {{-- si quieres reutilizar estructura --}}
    
    {{-- O si prefieres copiar la interfaz completa de ChatIA: --}}
    <div class="row" style="height: calc(100vh - 150px);">
        
        {{-- Panel izquierdo --}}
        <div class="col-md-4 col-lg-3 d-none d-md-block">
            <div class="card h-100">
                <div class="card-header">
                    <strong>Chats Meta</strong>
                </div>
                <div class="card-body p-0" style="overflow-y:auto;">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item active">
                            <strong>Cliente de prueba</strong>
                            <div class="small text-light">Último mensaje...</div>
                        </li>
                        <li class="list-group-item">
                            <strong>Soporte Conecta</strong>
                            <div class="small text-muted">Conversación de ejemplo</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Panel derecho --}}
        <div class="col-md-8 col-lg-9">
            <div class="card h-100 d-flex flex-column">

                <div class="card-header">
                    <strong>Chat con Meta</strong>
                </div>

                <div class="card-body" style="overflow-y:auto; background:#f5f5f5;">
                    <div class="d-flex mb-3">
                        <div class="p-2 rounded" style="background:#fff; max-width:70%;">
                            <strong>Cliente</strong>
                            <div>Hola, necesito información.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-3">
                        <div class="p-2 rounded" style="background:#dcf8c6; max-width:70%;">
                            <strong>Meta</strong>
                            <div>Con gusto, ¿en qué puedo ayudarte?</div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <form action="javascript:void(0);">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Escribe un mensaje...">
                            <div class="input-group-append">
                                <button class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Interfaz demo Chat Meta</small>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
