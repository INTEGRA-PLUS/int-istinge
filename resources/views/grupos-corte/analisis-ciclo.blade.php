@extends('layouts.app')

@section('boton')
<a href="{{ route('grupos-corte.index') }}" class="btn btn-outline-danger btn-sm"><i class="fas fa-backward"></i> Regresar al Listado</a>
<a href="{{ route('grupos-corte.show', $grupo->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-eye"></i> Ver Grupo</a>
@endsection

@section('styles')
<style>
.stat-card {
    border-left: 4px solid;
    transition: transform 0.2s;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
.stat-card:hover {
    transform: translateY(-5px);
}
.stat-card.primary { border-left-color: #007bff; }
.stat-card.success { border-left-color: #28a745; }
.stat-card.warning { border-left-color: #ffc107; }
.stat-card.danger { border-left-color: #dc3545; }
.stat-card.info { border-left-color: #17a2b8; }

.reason-card {
    cursor: pointer;
    transition: all 0.3s;
    border: 1px solid rgba(0,0,0,0.1);
}
.reason-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.text-danger-bold {
    color: #dc3545 !important;
    font-weight: bold;
}

.bg-light-blue {
    background-color: #f0f7ff;
}

.opacity-25 {
    opacity: 0.25;
}
</style>
@endsection

@section('content')

<!-- Header con Selectores de Navegación -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm bg-light-blue">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h4 class="mb-0 text-primary font-weight-bold"><i class="fas fa-chart-line"></i> Análisis de Ciclo</h4>
                        <p class="mb-0 text-muted">Grupo: <strong>{{ $grupo->nombre }}</strong> | Período: <strong>{{ $periodo }}</strong></p>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label for="grupoSelector" class="small font-weight-bold">Cambiar Grupo de Corte:</label>
                            <select id="grupoSelector" class="form-control selectpicker" data-live-search="true" data-style="btn-white">
                                @foreach($grupos as $g)
                                    <option value="{{ $g->id }}" {{ $g->id == $grupo->id ? 'selected' : '' }}>{{ $g->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label for="periodoSelector" class="small font-weight-bold">Seleccionar Período:</label>
                            <input type="month" id="periodoSelector" class="form-control" value="{{ $periodo }}" max="{{ Carbon\Carbon::now()->format('Y-m') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Información Detallada del Grupo -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="row text-center">
                    <div class="col border-right">
                        <small class="text-muted d-block">Día Factura</small>
                        <span class="font-weight-bold">{{ $grupo->fecha_factura == 0 ? 'No Aplica' : $grupo->fecha_factura }}</span>
                    </div>
                    <div class="col border-right">
                        <small class="text-muted d-block">Día Pago</small>
                        <span class="font-weight-bold">{{ $grupo->fecha_pago == 0 ? 'No Aplica' : $grupo->fecha_pago }}</span>
                    </div>
                    <div class="col border-right">
                        <small class="text-muted d-block">Día Corte</small>
                        <span class="font-weight-bold">{{ $grupo->fecha_corte == 0 ? 'No Aplica' : $grupo->fecha_corte }}</span>
                    </div>
                    <div class="col border-right">
                        <small class="text-muted d-block">Día Suspensión</small>
                        <span class="font-weight-bold">{{ $grupo->fecha_suspension == 0 ? 'No Aplica' : $grupo->fecha_suspension }}</span>
                    </div>
                    <div class="col">
                        <small class="text-muted d-block">Período</small>
                        <span class="font-weight-bold">
                            @if($grupo->periodo_facturacion == 1) Mes anticipado
                            @elseif($grupo->periodo_facturacion == 2) Mes vencido
                            @elseif($grupo->periodo_facturacion == 3) Mes actual
                            @else {{ $grupo->periodo_facturacion }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reporte de Cronología de Creación -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card stat-card success h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">
                        @if(($cycleStats['dia_esperado'] ?? 0) > 0)
                            En Fecha Esperada (Día {{ $cycleStats['dia_esperado'] }})
                        @else
                            Facturas en el Mes (Día No Aplica)
                        @endif
                    </h6>
                    <h2 class="mb-0 text-success font-weight-bold">{{ $cycleStats['facturas_en_fecha'] ?? 0 }}</h2>
                    <small class="text-muted">
                        @if(($cycleStats['dia_esperado'] ?? 0) > 0)
                            Facturas creadas en el día programado
                        @else
                            Total de facturas detectadas para este período
                        @endif
                    </small>
                </div>
                <i class="fas fa-calendar-check fa-3x text-success opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card stat-card warning h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">
                        @if(($cycleStats['dia_esperado'] ?? 0) > 0)
                            En Otras Fechas
                        @else
                            Reporte no disponible
                        @endif
                    </h6>
                    <h2 class="mb-0 text-warning font-weight-bold">{{ $cycleStats['facturas_fuera_fecha'] ?? 0 }}</h2>
                    <small class="text-muted">
                        @if(($cycleStats['dia_esperado'] ?? 0) > 0)
                            Facturas creadas antes o después del día programado
                        @else
                            El grupo no tiene un día de factura fijo
                        @endif
                    </small>
                </div>
                <i class="fas fa-calendar-minus fa-3x text-warning opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<!-- Cards de Estadísticas Generales -->
<div class="row mb-4">
    <div class="col-md-2 col-sm-4 mb-3">
        <div class="card stat-card info h-100">
            <div class="card-body text-center p-3">
                <h6 class="text-muted mb-2 small font-weight-bold">Contratos Totales</h6>
                <h3 class="mb-0 text-info">{{ $cycleStats['total_contratos'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-3">
        <div class="card stat-card success h-100">
            <div class="card-body text-center p-3">
                <h6 class="text-muted mb-2 small font-weight-bold">Generadas</h6>
                <h3 class="mb-0 text-success">{{ $cycleStats['facturas_generadas'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-3">
        <div class="card stat-card primary h-100">
            <div class="card-body text-center p-3">
                <h6 class="text-muted mb-2 small font-weight-bold">Esperadas</h6>
                <h3 class="mb-0 text-primary">{{ $cycleStats['facturas_esperadas'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-3">
        <div class="card stat-card danger h-100">
            <div class="card-body text-center p-3">
                <h6 class="text-muted mb-2 small font-weight-bold">Faltantes</h6>
                <h3 class="mb-0 text-danger">{{ $cycleStats['facturas_faltantes'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-3">
        <div class="card stat-card warning h-100">
            <div class="card-body text-center p-3">
                <h6 class="text-muted mb-2 small font-weight-bold">Tasa Éxito</h6>
                <h3 class="mb-0 text-warning">{{ $cycleStats['tasa_exito'] ?? 0 }}%</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-3">
        <div class="card stat-card info h-100" title="Promedio últimos 6 meses">
            <div class="card-body text-center p-3">
                <h6 class="text-muted mb-2 small font-weight-bold">Promedio Hist.</h6>
                <h3 class="mb-0 text-info">{{ $promedioFacturas }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Métricas Comparativas -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="text-muted mb-0">Variación vs Mes Anterior</h6>
                    <h4 class="mb-0 {{ $variacionMesAnterior >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
                        <i class="fas fa-{{ $variacionMesAnterior >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                        {{ abs($variacionMesAnterior) }}%
                    </h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="text-muted mb-0">vs Promedio General</h6>
                    @php
                        $diff = $cycleStats && $promedioFacturas > 0 ? (($cycleStats['facturas_generadas'] - $promedioFacturas) / $promedioFacturas) * 100 : 0;
                    @endphp
                    <h4 class="mb-0 {{ $diff >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
                        <i class="fas fa-{{ $diff >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                        {{ abs(round($diff, 2)) }}%
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Análisis de Facturas Faltantes -->
@if($cycleStats && isset($cycleStats['missing_reasons']) && count($cycleStats['missing_reasons']) > 0)
<div class="row mb-4">
    <div class="col-12">
        <h5 class="mb-3 font-weight-bold text-dark"><i class="fas fa-search-minus text-warning"></i> ¿Por qué faltaron facturas?</h5>
    </div>
    
    @foreach($cycleStats['missing_reasons'] as $reason)
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card reason-card h-100" onclick="showReasonDetails('{{ $reason['code'] }}')">
            <div class="card-body text-center p-3">
                <div class="badge badge-{{ $reason['color'] }} px-3 mb-2" style="font-size: 0.9rem;">{{ $reason['count'] }}</div>
                <h6 class="mb-0 text-dark" style="font-size: 0.85rem;">{{ $reason['title'] }}</h6>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<!-- Tabla de Facturas Generadas -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 font-weight-bold text-primary"><i class="fas fa-file-invoice"></i> Facturas Generadas en el Ciclo</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped table-hover w-100" id="facturasTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Contrato</th>
                            <th>Fecha Creación</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($cycleStats && isset($cycleStats['facturas']))
                            @foreach($cycleStats['facturas'] as $factura)
                            <tr>
                                <td>{{ $factura->codigo ?? $factura->nro }}</td>
                                <td>{{ $factura->nombre_cliente }}</td>
                                <td>{{ $factura->contrato_nro }}</td>
                                <td>{{ \Carbon\Carbon::parse($factura->fecha)->format('d-m-Y') }}</td>
                                <td>${{ number_format($factura->totalAPI(1)->total ?? 0, 0, ',', '.') }}</td>
                                <td>
                                    @if($factura->estatus == 1)
                                        <span class="badge badge-success">Abierta</span>
                                    @else
                                        <span class="badge badge-secondary">Cerrada</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('facturas.show', $factura->id) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalles de Razones -->
<div class="modal fade" id="reasonDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title font-weight-bold" id="reasonModalTitle">Detalles</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="px-3 py-2 bg-light border-bottom">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="modalSearch" class="form-control" placeholder="Buscar por nombre, identificación o contrato...">
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped mb-0" id="reasonDetailsTable">
                        <thead class="bg-secondary text-white">
                            <tr>
                                <th>Contrato</th>
                                <th>Identificación</th>
                                <th>Cliente</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody id="reasonDetailsBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const cycleStats = @json($cycleStats);
const grupoId = {{ $grupo->id }};

// Mostrar detalles de razón
function showReasonDetails(reasonCode) {
    const reason = cycleStats.missing_reasons.find(r => r.code === reasonCode);
    const details = cycleStats.missing_details.filter(d => d.razon_code === reasonCode);
    
    document.getElementById('reasonModalTitle').textContent = reason.title + ` (${reason.count} contratos)`;
    
    const tbody = document.getElementById('reasonDetailsBody');
    tbody.innerHTML = '';
    
    details.forEach(detail => {
        let description = detail.razon_description;
        if (reasonCode === 'status_inactive') {
            description = '<span class="text-danger-bold">El contrato tiene estado deshabilitado</span>';
        }

        const contratoUrl = "{{ route('contratos.show', ':id') }}".replace(':id', detail.contrato_id);
        const clienteUrl = "{{ route('contactos.show', ':id') }}".replace(':id', detail.cliente_id);

        const row = `
            <tr>
                <td><a href="${contratoUrl}" target="_blank" class="font-weight-bold">${detail.contrato_nro}</a></td>
                <td>${detail.cliente_nit || 'N/A'}</td>
                <td><a href="${clienteUrl}" target="_blank">${detail.cliente_nombre}</a></td>
                <td>${description}</td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
    
    // Limpiar buscador y mostrar modal
    document.getElementById('modalSearch').value = '';
    $('#reasonDetailsModal').modal('show');
}

// Document ready
$(document).ready(function() {
    // Inicializar DataTable con paginación
    $('#facturasTable').DataTable({
        responsive: true,
        pageLength: 25,
        language: {
            'url': '/vendors/DataTables/es.json'
        },
        order: [[3, "desc"]]
    });
    
    // Recarga automática al cambiar período
    $('#periodoSelector').on('change', function() {
        const periodo = $(this).val();
        if (periodo) {
            let url = "{{ route('grupos-corte.analisis-ciclo', ['idGrupo' => 'ID_PLACEHOLDER', 'periodo' => 'PERIODO_PLACEHOLDER']) }}";
            url = url.replace('ID_PLACEHOLDER', grupoId).replace('PERIODO_PLACEHOLDER', periodo);
            window.location.href = url;
        }
    });

    // Recarga automática al cambiar grupo
    $('#grupoSelector').on('change', function() {
        const selectedId = $(this).val();
        const periodo = $('#periodoSelector').val();
        if (selectedId) {
            let url = "{{ route('grupos-corte.analisis-ciclo', ['idGrupo' => 'ID_PLACEHOLDER', 'periodo' => 'PERIODO_PLACEHOLDER']) }}";
            url = url.replace('ID_PLACEHOLDER', selectedId).replace('PERIODO_PLACEHOLDER', periodo);
            window.location.href = url;
        }
    });

    // Buscador en tiempo real para el modal
    $('#modalSearch').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $("#reasonDetailsBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});
</script>
@endsection
