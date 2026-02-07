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

    <!-- Sección: Estado de Numeración -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-sort-numeric-up text-info"></i> Estado de Numeración</h6>
                </div>
                <div class="card-body">
                    <div class="row" id="numberingStatusContainer">
                       <!-- Se llena dinámicamente con JS -->
                       <div class="col-12 text-center text-muted">
                           <i class="fas fa-spinner fa-spin"></i> Verificando numeraciones...
                       </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Acciones a Realizar (Sugerencias Dinámicas) -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-magic text-primary"></i> Acciones a realizar</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap" id="dynamicActionsContainer">
                    <!-- Botón: Generar Facturas Faltantes (Siempre visible si hay faltantes) -->
                    @if($cycleStats['facturas_faltantes'] > 0)
                    <button class="btn btn-primary mr-3 mb-2" onclick="confirmarGeneracionManual()">
                        <i class="fas fa-play-circle"></i> Generar Facturas faltantes del periodo {{ $cycleStats['dia_esperado'] ?: '1' }} {{ \Carbon\Carbon::parse($periodo)->locale('es')->isoFormat('MMMM') }}
                    </button>
                    @endif

                    <!-- Botón: Eliminar Facturación del Ciclo -->
                    <button class="btn btn-outline-danger mr-3 mb-2" onclick="eliminarCiclo()">
                        <i class="fas fa-trash-alt"></i> Eliminar facturación del ciclo
                    </button>

                    <!-- Botón: Refrescar Análisis -->
                    <button class="btn btn-info mr-3 mb-2" onclick="refrescarAnalisis()">
                        <i class="fas fa-sync-alt"></i> Refrescar Análisis
                    </button>

                    <!-- Facturación Automática -->
                    @if($empresa->factura_auto == 1)
                    <button class="btn btn-outline-secondary mr-3 mb-2" onclick="updateConfig('factura_auto', 0, '¿Deshabilitar Facturación Automática?')">
                        <i class="fas fa-stop-circle"></i> Deshabilitar Facturación Automática
                    </button>
                    @else
                    <button class="btn btn-outline-primary mr-3 mb-2" onclick="updateConfig('factura_auto', 1, '¿Habilitar Facturación Automática?')">
                        <i class="fas fa-play-circle"></i> Habilitar Facturación Automática
                    </button>
                    @endif

                    <!-- Saldos a Favor -->
                    @if($empresa->aplicar_saldofavor == 1)
                    <button class="btn btn-outline-secondary mr-3 mb-2" onclick="updateConfig('aplicar_saldofavor', 0, '¿Deshabilitar aplicación de saldos a favor automático?')">
                        <i class="fas fa-minus-circle"></i> Deshabilitar aplicación de saldos a favor automático
                    </button>
                    @else
                    <button class="btn btn-outline-primary mr-3 mb-2" onclick="updateConfig('aplicar_saldofavor', 1, '¿Habilitar aplicación de saldos a favor automático?')">
                        <i class="fas fa-plus-circle"></i> Habilitar aplicación de saldos a favor automático
                    </button>
                    @endif

                    <!-- Facturacion Abiertas -->
                    @if($empresa->cron_fact_abiertas == 1)
                    <button class="btn btn-outline-secondary mr-3 mb-2" onclick="updateConfig('cron_fact_abiertas', 0, '¿Deshabilitar facturación automatica fact. abiertas?')">
                        <i class="fas fa-folder"></i> Deshabilitar facturacion automatica fact. abiertas
                    </button>
                    @else
                    <button class="btn btn-outline-primary mr-3 mb-2" onclick="updateConfig('cron_fact_abiertas', 1, '¿Habilitar facturacion automatica fact. abiertas?')">
                        <i class="fas fa-folder-open"></i> Habilitar facturacion automatica fact. abiertas
                    </button>
                    @endif

                    <!-- Contratos OFF -->
                    @if($empresa->factura_contrato_off == 1)
                    <button class="btn btn-outline-secondary mr-3 mb-2" onclick="updateConfig('factura_contrato_off', 0, '¿Deshabilitar facturas en contratos deshabilitados?')">
                        <i class="fas fa-user-slash"></i> Deshabilitar facturas en contratos deshabilitados
                    </button>
                    @else
                    <button class="btn btn-outline-danger mr-3 mb-2" onclick="updateConfig('factura_contrato_off', 1, '¿Habilitar la creación de facturas en contratos deshabilitados?')">
                        <i class="fas fa-user-check"></i> Habilitar la creación de facturas en contratos deshabilitados
                    </button>
                    @endif

                    <!-- Prorrateo -->
                    @if($empresa->prorrateo == 1)
                    <button class="btn btn-outline-secondary mr-3 mb-2" onclick="prorrateoExtra()">
                        <i class="fas fa-calendar-minus"></i> Deshabilitar Prorrateo
                    </button>
                    @else
                    <button class="btn btn-outline-primary mr-3 mb-2" onclick="prorrateoExtra()">
                        <i class="fas fa-calendar-plus"></i> Habilitar Prorrateo
                    </button>
                    @endif
                    <input type="hidden" id="prorrateoid" value="{{ $empresa->prorrateo }}">

                    <div id="actionFixNumbering" style="display: none;">
                        <a href="{{ route('configuracion.numeraciones') }}" class="btn btn-outline-warning mr-3 mb-2">
                            <i class="fas fa-list-ol"></i> Ir a corregir numeraciones vencidas/no asignadas
                        </a>
                    </div>

                    <!-- Botón: Vincular Facturas Manuales (Dinámico via JS) -->
                    <div id="actionFixUnflaggedInvoices" style="display: none;">
                        <button class="btn btn-outline-success mr-3 mb-2" onclick="vincularFacturasManuales()">
                            <i class="fas fa-link"></i> Vincular facturas manuales hoy al ciclo actual
                        </button>
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
                <div class="d-flex justify-content-center align-items-baseline">
                    <h3 class="mb-0 text-success">{{ $cycleStats['facturas_generadas'] ?? 0 }}</h3>
                    @if(isset($cycleStats['duplicates_analysis']) && $cycleStats['duplicates_analysis']['total_excedentes'] > 0)
                        <span class="ml-2 badge badge-danger" data-toggle="tooltip" title="Se detectaron {{ $cycleStats['duplicates_analysis']['total_excedentes'] }} facturas de más (excedentes)"><i class="fas fa-exclamation-circle"></i> +{{ $cycleStats['duplicates_analysis']['total_excedentes'] }}</span>
                    @endif
                </div>
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
                <h6 class="text-muted mb-1 small font-weight-bold">Faltantes</h6>
                <div class="d-flex justify-content-center align-items-baseline">
                    <h3 class="mb-0 text-danger mr-2">{{ $cycleStats['facturas_faltantes'] ?? 0 }}</h3>
                </div>
                @if(isset($cycleStats['missing_breakdown']))
                <div class="mt-2 text-left small border-top pt-1 text-muted">
                    <div data-toggle="tooltip" title="Falta por crear {{ $cycleStats['missing_breakdown']['standard'] }} facturas estandar"><i class="fas fa-file-invoice"></i> Estándar: <b>{{ $cycleStats['missing_breakdown']['standard'] }}</b></div>
                    <div data-toggle="tooltip" title="Falta por crear {{ $cycleStats['missing_breakdown']['electronic'] }} numeraciones de facturas electronicas." ><i class="fas fa-file-invoice"></i> Electrónica: <b>{{ $cycleStats['missing_breakdown']['electronic'] }}</b></div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-3">
        <div class="card stat-card success h-100">
            <div class="card-body text-center p-3">
                <h6 class="text-muted mb-1 small font-weight-bold">Whatsapp</h6>
                <div class="d-flex justify-content-center align-items-baseline">
                    <h3 class="mb-0 text-success mr-2">{{ $cycleStats['whatsapp_stats']['sent'] ?? 0 }}</h3>
                </div>
                <div class="mt-2 text-left small border-top pt-1 text-muted">
                    <div title="Enviadas"><i class="fab fa-whatsapp text-success"></i> Env: <b>{{ $cycleStats['whatsapp_stats']['sent'] ?? 0 }}</b></div>
                    <div title="Pendientes"><i class="fab fa-whatsapp text-secondary"></i> Pend: <b>{{ $cycleStats['whatsapp_stats']['pending'] ?? 0 }}</b></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-3">
        <div class="card stat-card warning h-100">
            <div class="card-body text-center p-3">
                <h6 class="text-muted mb-2 small font-weight-bold">Tasa Éxito</h6>
                <h3 class="mb-0 text-warning">{{ $cycleStats['tasa_exito'] ?? 0 }}%</h3>
                <small class="text-muted small">Efectividad</small>
            </div>
        </div>
    </div>
</div>

<!-- Métricas Comparativas -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
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
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
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
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100 card-stat-info">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="text-muted mb-0">Promedio Histórico</h6>
                    <h4 class="mb-0 text-info font-weight-bold">
                        {{ $promedioFacturas }}
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

<!-- Análisis de Facturas Duplicadas / Excedentes -->
@if($cycleStats && isset($cycleStats['duplicates_analysis']) && $cycleStats['duplicates_analysis']['total_excedentes'] > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-clone text-danger"></i> Facturas Excedentes / Duplicadas</h5>
            <button class="btn btn-danger btn-sm" onclick="eliminarTodasDuplicadas()">
                <i class="fas fa-trash-alt"></i> Eliminar Todos los Duplicados ({{ $cycleStats['duplicates_analysis']['total_excedentes'] }})
            </button>
        </div>
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="fas fa-info-circle"></i> Se detectaron <b>{{ $cycleStats['duplicates_analysis']['total_excedentes'] }}</b> facturas adicionales a los contratos esperados. A continuación se listan los contratos que tienen más de una factura en este ciclo.
        </div>
    </div>
    
    @foreach($cycleStats['duplicates_analysis']['contratos_duplicados'] as $dup)
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-left border-danger">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="font-weight-bold mb-0 text-dark">
                            <a href="{{ route('contactos.show', $dup['cliente_id']) }}" target="_blank" class="text-primary text-decoration-none">
                                {{ $dup['cliente_nombre'] }} <i class="fas fa-external-link-alt small"></i>
                            </a>
                        </h6>
                        <small class="text-muted">Contrato: #{{ $dup['contrato_nro'] }}</small>
                    </div>
                    <span class="badge badge-danger">{{ $dup['cantidad'] }} facturas</span>
                </div>
                <div class="bg-light p-2 rounded small">
                    <ul class="list-unstyled mb-0">
                        @foreach($dup['facturas'] as $index => $f)
                        <li class="d-flex justify-content-between mb-1">
                            <div>
                                <i class="fas fa-file-invoice text-muted"></i> #{{ $f['codigo'] ?? $f['nro'] }}
                                <small class="text-muted ml-1">({{ \Carbon\Carbon::parse($f['fecha'])->translatedFormat('d-M') }})</small>
                                @if($index > 0)
                                <a href="javascript:void(0)" onclick="eliminarFacturaDuplicada({{ $f['id'] }}, {{ $dup['contrato_id'] }})" class="text-danger ml-2" title="Eliminar esta factura">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                @endif
                            </div>
                            <div class="text-right">
                                <span class="font-weight-bold">${{ number_format($f['total'], 0, ',', '.') }}</span>
                                <div class="small text-muted" style="font-size: 0.75rem;">{{ $f['tipo_operacion'] }}</div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @if($dup['cantidad'] > 1)
                    <div class="text-center mt-2">
                        <button class="btn btn-outline-danger btn-sm btn-block" onclick="eliminarDuplicadosContrato({{ $dup['contrato_id'] }})">
                            <i class="fas fa-trash-alt"></i> Eliminar Duplicados de Este Contrato
                        </button>
                    </div>
                    @endif
                </div>
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
                            <th>Vencimiento</th>
                            <th>Total</th>
                            <th>Whatsapp</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data loaded via DataTables Server Side -->
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

<!-- Modal de Confirmación para Generación Manual -->
<div class="modal fade" id="confirmGenerationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Confirmar Generación</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-file-invoice-dollar fa-4x text-primary opacity-25"></i>
                </div>
                <h5>¿Estás seguro de iniciar la generación manual?</h5>
                <p class="text-muted">Se intentarán crear las <strong>{{ $cycleStats['facturas_faltantes'] }}</strong> facturas faltantes para el día de facturación del grupo en el periodo seleccionado.</p>
                <div class="alert alert-info text-left small">
                    <ul class="mb-0">
                        <li>Solo se procesarán contratos del grupo <strong>{{ $grupo->nombre }}</strong>.</li>
                        <li>Se usará la fecha de proceso: <strong>{{ $cycleStats['fecha_ciclo'] }}</strong>.</li>
                        <li>Contratos que ya tienen factura en este mes serán omitidos automáticamente.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" id="btnRunGeneration" onclick="ejecutarGeneracionManual()">
                    <i class="fas fa-check"></i> Sí, Generar Ahora
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(function() {
        $('[data-toggle="tooltip"]').tooltip();
        
        if (typeof cycleStats !== 'undefined' && cycleStats.missing_reasons) {
            const hasOffBillingIssue = cycleStats.missing_reasons.some(r => r.code === 'contract_disabled_off');
            const hasNumberingIssue = cycleStats.missing_reasons.some(r => r.code === 'no_valid_numbering');
            const hasUnflaggedIssue = cycleStats.missing_reasons.some(r => r.code === 'manual_invoice_unflagged');

            if (hasOffBillingIssue) $('#actionFixOffBilling').show();
            if (hasNumberingIssue) $('#actionFixNumbering').show();
            if (hasUnflaggedIssue) $('#actionFixUnflaggedInvoices').show();
        }
    });
    
    // Convertir el JSON de PHP a objetos JS
    var cycleStats = @json($cycleStats);
    var historicalData = @json($historicalData);
    var grupoId = {{ $grupo->id }};
    var numberingHealth = cycleStats && cycleStats.numbering_health ? cycleStats.numbering_health : null;
    
    console.log('Stats:', cycleStats);
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
    // Inicializar DataTable con paginación optimizada (Server Side)
    $('#facturasTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        ajax: {
            url: "{{ route('grupos-corte.dt-generated-invoices') }}",
            data: function (d) {
                d.grupo_id = "{{ $grupo->id }}";
                d.periodo = "{{ $periodo }}";
            }
        },
        language: {
            'url': '/vendors/DataTables/es.json'
        },
        order: [[3, "desc"]],
        columns: [
            { data: 0, name: 'codigo' },
            { data: 1, name: 'nombre_cliente' },
            { data: 2, name: 'contrato_nro' },
            { data: 3, name: 'fecha' },
            { data: 4, name: 'vencimiento' },
            { data: 5, name: 'total', orderable: false, searchable: false },
            { data: 6, orderable: false, searchable: false },
            { data: 7, name: 'estatus' },
            { data: 8, orderable: false, searchable: false }
        ]
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


    // Renderizar estado de numeración
    if (numberingHealth) {
        const container = $('#numberingStatusContainer');
        container.empty();
        
        // Función auxiliar para generar HTML de estado con detalles avanzados
        const getStatusHtml = (type, title, data) => {
            let icon = data.status === 'ok' ? 'fa-check-circle text-success' : (data.status === 'warning' ? 'fa-exclamation-triangle text-warning' : 'fa-times-circle text-danger');
            let borderClass = data.status === 'ok' ? 'border-success' : (data.status === 'warning' ? 'border-warning' : 'border-danger');
            
            let actionLink = '';
            if (data.status !== 'ok') {
                actionLink = `
                    <div class="mt-2 text-right">
                        <a href="{{ route('configuracion.numeraciones') }}" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-cog"></i> Configurar Numeración
                        </a>
                    </div>
                `;
            }

            let detailsHtml = '';
            if (data.details) {
                detailsHtml = `
                    <div class="mt-2 pt-2 border-top small">
                        <div class="row">
                            <div class="col-6">
                                <span class="text-muted">Vence:</span> 
                                <span class="font-weight-bold text-dark">${data.details.expiration}</span>
                            </div>
                            <div class="col-6 text-right">
                                <span class="text-muted">Rango:</span> 
                                <span class="font-weight-bold text-dark">${data.details.current} / ${data.details.limit}</span>
                            </div>
                            <div class="col-12 mt-1">
                                <span class="text-muted">Proyección:</span> 
                                <span class="font-italic ${data.status === 'ok' ? 'text-success' : 'text-danger'}">${data.recommendation}</span>
                            </div>
                        </div>
                    </div>
                `;
            }

            return `
                <div class="col-md-6 mb-2">
                    <div class="h-100 p-3 border rounded ${borderClass} bg-light">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas ${icon} fa-2x mr-3"></i>
                            <div class="w-100">
                                <h6 class="mb-0 font-weight-bold text-dark">${title}</h6>
                                <p class="mb-0 text-secondary small">${data.message}</p>
                            </div>
                        </div>
                        ${detailsHtml}
                        ${actionLink}
                    </div>
                </div>
            `;
        };

        let html = '';
        html += getStatusHtml('standard', 'Facturación Estándar', numberingHealth.standard);
        html += getStatusHtml('electronic', 'Facturación Electrónica', numberingHealth.electronic);
        
        container.html(html);
    }
});

function confirmarGeneracionManual() {
    $('#confirmGenerationModal').modal('show');
}

function ejecutarGeneracionManual() {
    const btn = $('#btnRunGeneration');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

    let timeoutId;
    let ajaxRequest = $.ajax({
        url: "{{ route('grupos-corte.generar-facturas-faltantes') }}",
        method: 'POST',
        timeout: 30000, // 30 segundos
        data: {
            _token: '{{ csrf_token() }}',
            idGrupo: grupoId,
            periodo: '{{ $periodo }}'
        },
        success: function(response) {
            clearTimeout(timeoutId);
            swal({
                title: "¡Proceso Finalizado!",
                text: response.message,
                type: "success",
                confirmButtonClass: "btn-success",
                confirmButtonText: "Ok",
            }).then(() => {
                location.reload();
            });
        },
        error: function(xhr, status, error) {
            clearTimeout(timeoutId);
            
            // Si es un timeout de AJAX
            if (status === 'timeout') {
                swal({
                    title: "Proceso en Ejecución",
                    html: "El proceso está ejecutándose en segundo plano y puede tardar unos minutos más.<br><br>Puedes recargar la página para verificar el progreso o seguir esperando.",
                    type: "info",
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-sync"></i> Recargar Página',
                    cancelButtonText: '<i class="fas fa-clock"></i> Seguir Esperando',
                    confirmButtonClass: "btn-primary",
                    cancelButtonClass: "btn-secondary",
                }).then((result) => {
                    if (result.value) {
                        location.reload();
                    } else {
                        // Extender el timeout y reintentar
                        btn.html('<i class="fas fa-spinner fa-spin"></i> Esperando respuesta...');
                        ejecutarGeneracionManual();
                    }
                });
                return;
            }
            
            // Otros errores
            const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error desconocido';
            swal({
                title: "Atención",
                text: "Hubo un problema con la respuesta del servidor (" + msg + "), pero es posible que el proceso se haya ejecutado en segundo plano. Te recomendamos recargar para verificar.",
                type: "warning",
                confirmButtonText: "Recargar Página",
                showCancelButton: true,
                cancelButtonText: "Cerrar"
            }).then((result) => {
                if(result.value) {
                    location.reload();
                }
            });
            btn.prop('disabled', false).html('<i class="fas fa-check"></i> Sí, Generar Ahora');
        }
    });
}

function habilitarFacturacionOff() {
    updateConfig('factura_contrato_off', 1, '¿Habilitar la creación de facturas en contratos deshabilitados?');
}

function updateConfig(field, value, title) {
    swal({
        title: title,
        text: "Este cambio afectará a toda la empresa inmediatamente.",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: value == 1 ? "btn-success" : "btn-danger",
        confirmButtonText: "Sí, realizar cambio",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: "{{ route('grupos-corte.update-empresa-config') }}",
                method: 'POST',
                data: { 
                    _token: '{{ csrf_token() }}',
                    field: field,
                    value: value
                },
                success: function(response) {
                    swal("Actualizado", response.message, "success").then(() => {
                        location.reload();
                    });
                },
                error: function() {
                    swal("Error", "No se pudo actualizar la configuración.", "error");
                }
            });
        }
    });
}

function prorrateoExtra() {
    var url = '/prorrateo';
    if (window.location.pathname.split("/")[1] === "software") {
        url = '/software/prorrateo';
    }

    var isChecked = $("#prorrateoid").val();
    var titleswal = "";
    var text = "";

    if (isChecked == 0) {
        titleswal = "¿Desea habilitar el prorrateo de las facturas?";
        text = "Al habilitar esta opción, el sistema habilitará el cobro de prorrateo para todos los contratos actuales de la empresa. Además, por defecto, los nuevos contratos se crearán con la opción de prorrateo habilitada.";
    } else {
        titleswal = "¿Desea deshabilitar el prorrateo de las facturas?";
        text = "Al deshabilitar esta opción, el sistema deshabilitará el cobro de prorrateo para todos los contratos actuales de la empresa. Además, por defecto, los nuevos contratos se crearán con la opción de prorrateo deshabilitada.";
    }

    swal({
        title: titleswal,
        text: text,
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Aceptar',
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: url,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                method: 'post',
                data: { 
                    prorrateo: isChecked,
                    grupo_id: grupoId 
                },
                success: function(data) {
                    if (data == 1) {
                        swal({
                            title: 'Prorrateo para facturas ha sido habilitado.',
                            type: 'success',
                            showConfirmButton: false,
                            timer: 5000
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        swal({
                            title: 'Prorrateo para facturas ha sido deshabilitado',
                            type: 'success',
                            showConfirmButton: false,
                            timer: 5000
                        }).then(() => {
                            location.reload();
                        });
                    }
                }
            });
        }
    });
}

function vincularFacturasManuales() {
    swal({
        title: "¿Vincular facturas manuales?",
        text: "Se marcarán como 'Factura del Mes' todas las facturas manuales detectadas para que el sistema las reconozca en este ciclo.",
        type: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, vincular ahora",
        cancelButtonText: "Cancelar",
        confirmButtonClass: "btn-success",
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return $.ajax({
                url: "{{ route('grupos-corte.marcar-facturas-mes-lote') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    idGrupo: grupoId,
                    periodo: '{{ $periodo }}'
                }
            });
        },
        allowOutsideClick: () => !swal.isLoading()
    }).then((result) => {
        if (result.value) {
            if (result.value.success) {
                swal("¡Éxito!", result.value.message, "success").then(() => {
                    location.reload();
                });
            } else {
                swal("Error", result.value.message || "Ocurrió un error inesperado.", "error");
            }
        }
    });
}

/**
 * Eliminar una factura duplicada específica
 */
function eliminarFacturaDuplicada(facturaId, contratoId) {
    swal({
        title: '¿Eliminar esta factura?',
        text: 'Esta acción eliminará la factura y sus dependencias. Esta acción no se puede deshacer.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        confirmButtonClass: 'btn-danger',
        cancelButtonClass: 'btn-secondary'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: "{{ route('grupos-corte.eliminar-factura-duplicada') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    factura_id: facturaId,
                    idGrupo: grupoId,
                    periodo: '{{ $periodo }}'
                },
                success: function(response) {
                    swal({
                        title: '¡Éxito!',
                        text: response.message,
                        type: 'success'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error al eliminar la factura';
                    swal({
                        title: 'Error',
                        text: message,
                        type: 'error'
                    });
                }
            });
        }
    });
}

/**
 * Eliminar duplicados de un contrato específico
 */
function eliminarDuplicadosContrato(contratoId) {
    swal({
        title: '¿Eliminar duplicados de este contrato?',
        html: 'Se eliminarán todas las facturas duplicadas de este contrato, manteniendo solo la más reciente.<br><br><strong>Las facturas con pagos asociados no serán eliminadas.</strong>',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        confirmButtonClass: 'btn-danger',
        cancelButtonClass: 'btn-secondary'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: "{{ route('grupos-corte.eliminar-masivo-duplicados') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    idGrupo: grupoId,
                    periodo: '{{ $periodo }}',
                    contrato_id: contratoId
                },
                success: function(response) {
                    let icon = 'success';
                    if (response.eliminadas === 0) {
                        icon = 'info';
                    }
                    swal({
                        title: response.eliminadas > 0 ? '¡Éxito!' : 'Información',
                        text: response.message,
                        type: icon
                    }).then(() => {
                        if (response.eliminadas > 0) {
                            location.reload();
                        }
                    });
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error al eliminar duplicados';
                    swal({
                        title: 'Error',
                        text: message,
                        type: 'error'
                    });
                }
            });
        }
    });
}

/**
 * Eliminar TODOS los duplicados del ciclo
 */
function eliminarTodasDuplicadas() {
    swal({
        title: '¿Eliminar TODOS los duplicados del ciclo?',
        html: 'Se eliminarán <strong>todas</strong> las facturas duplicadas del ciclo actual, manteniendo solo la más reciente de cada contrato.<br><br><strong>Las facturas con pagos asociados no serán eliminadas.</strong>',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar todas',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        confirmButtonClass: 'btn-danger',
        cancelButtonClass: 'btn-secondary'
    }).then((result) => {
        if (result.value) {
            // Mostrar loading
            swal({
                title: 'Procesando...',
                text: 'Eliminando facturas duplicadas',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                onOpen: () => {
                    swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ route('grupos-corte.eliminar-masivo-duplicados') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    idGrupo: grupoId,
                    periodo: '{{ $periodo }}'
                },
                success: function(response) {
                    swal({
                        title: response.eliminadas > 0 ? '¡Éxito!' : 'Información',
                        html: `<div class="text-left">
                            <p><strong>${response.message}</strong></p>
                            <ul>
                                <li>Facturas eliminadas: <strong>${response.eliminadas}</strong></li>
                                ${response.no_pudieron_eliminar > 0 ? `<li class="text-warning">No eliminadas (con pagos): <strong>${response.no_pudieron_eliminar}</strong></li>` : ''}
                            </ul>
                        </div>`,
                        type: response.eliminadas > 0 ? 'success' : 'info'
                    }).then(() => {
                        if (response.eliminadas > 0) {
                            location.reload();
                        }
                    });
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error al eliminar duplicados';
                    swal({
                        title: 'Error',
                        text: message,
                        type: 'error'
                    });
                }
            });
        }
    });
}

/**
 * Eliminar facturación del ciclo
 */
function eliminarCiclo() {
    swal({
        title: '¿Eliminar facturación del ciclo?',
        html: 'Esta acción <strong>eliminará todas las facturas</strong> generadas para este ciclo y restaurará la numeración.<br><br><span class="text-danger font-weight-bold">¡Esta acción NO se puede deshacer!</span>',
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar todo',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar'
    }).then((result) => {
        if (result.value) {
            swal({
                title: 'Eliminando...',
                text: 'Por favor espere mientras eliminamos las facturas y restablecemos la numeración.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                onOpen: () => {
                    swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ route('grupos-corte.eliminar-ciclo') }}",
                method: 'POST',
                data: {
                    idGrupo: grupoId,
                    periodo: '{{ $periodo }}',
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    swal({
                        title: 'Éxito',
                        html: response.message,
                        type: 'success'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    let message = 'Error desconocido';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    swal({
                        title: 'Error',
                        html: message,
                        type: 'error'
                    });
                }
            });
        }
    });
}

/**
 * Refrescar análisis del ciclo (limpiar caché)
 */
function refrescarAnalisis() {
    swal({
        title: '¿Refrescar análisis?',
        text: 'Esto limpiará la caché y recalculará las estadísticas del ciclo.',
        type: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-sync-alt"></i> Sí, refrescar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar'
    }).then((result) => {
        if (result.value) {
            // Mostrar loading
            swal({
                title: 'Limpiando caché...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                onOpen: () => {
                    swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ route('grupos-corte.limpiar-cache-ciclo') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    idGrupo: grupoId,
                    periodo: '{{ $periodo }}'
                },
                success: function(response) {
                    swal({
                        title: '¡Éxito!',
                        text: 'Caché limpiado. Recargando página...',
                        type: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'No se pudo limpiar la caché';
                    swal({
                        title: 'Error',
                        text: message,
                        type: 'error'
                    });
                }
            });
        }
    });
}
</script>
@endsection
