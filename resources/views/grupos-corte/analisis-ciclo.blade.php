@extends('layouts.app')

@section('boton')
<a href="{{ route('grupos-corte.show', $grupo->id) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al Grupo</a>
@endsection

@section('styles')
<style>
.stat-card {
    border-left: 4px solid;
    transition: transform 0.2s;
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
}
.reason-card:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.chart-container {
    position: relative;
    height: 300px;
}

@media (max-width: 768px) {
    .chart-container {
        height: 250px;
    }
}
</style>
@endsection

@section('content')

<!-- Header con Selector de Período -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0"><i class="fas fa-chart-bar text-primary"></i> {{ $grupo->nombre }}</h4>
                        <small class="text-muted">Análisis de Ciclos de Facturación</small>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label for="periodoSelector"><strong>Seleccionar Período:</strong></label>
                            <input type="month" id="periodoSelector" class="form-control" value="{{ $periodo }}" max="{{  Carbon\Carbon::now()->format('Y-m') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cards de Estadísticas Generales -->
<div class="row mb-4">
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card info h-100">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Contratos</h6>
                <h2 class="mb-0 text-info" id="totalContratos">{{ $cycleStats['total_contratos'] ?? 0 }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card success h-100">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Generadas</h6>
                <h2 class="mb-0 text-success" id="facturasGeneradas">{{ $cycleStats['facturas_generadas'] ?? 0 }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card primary h-100">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Esperadas</h6>
                <h2 class="mb-0 text-primary" id="facturasEsperadas">{{ $cycleStats['facturas_esperadas'] ?? 0 }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card danger h-100">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Faltantes</h6>
                <h2 class="mb-0 text-danger" id="facturasFaltantes">{{ $cycleStats['facturas_faltantes'] ?? 0 }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card warning h-100">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Tasa de Éxito</h6>
                <h2 class="mb-0 text-warning" id="tasaExito">{{ $cycleStats['tasa_exito'] ?? 0 }}%</h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card info h-100">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Promedio</h6>
                <h2 class="mb-0 text-info" id="promedioFacturas">{{ $promedioFacturas }}</h2>
            </div>
        </div>
    </div>
</div>

<!-- Gráficas -->
<div class="row mb-4">
    <div class="col-md-8 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Facturas Generadas vs Esperadas (Últimos 6 Meses)</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Distribución de Razones</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="doughnutChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Métricas Comparativas -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Variación vs Mes Anterior</h6>
                <h3 class="{{ $variacionMesAnterior >= 0 ? 'text-success' : 'text-danger' }}">
                    <i class="fas fa-{{ $variacionMesAnterior >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                    {{ abs($variacionMesAnterior) }}%
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">vs Promedio General</h6>
                <h3 id="vsPromedio" class="text-info">
                    @php
                        $diff = $cycleStats && $promedioFacturas > 0 ? (($cycleStats['facturas_generadas'] - $promedioFacturas) / $promedioFacturas) * 100 : 0;
                    @endphp
                    <i class="fas fa-{{ $diff >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                    {{ abs(round($diff, 2)) }}%
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Análisis de Facturas Faltantes -->
@if($cycleStats && isset($cycleStats['missing_reasons']) && count($cycleStats['missing_reasons']) > 0)
<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-3"><i class="fas fa-exclamation-triangle text-warning"></i> Análisis de Facturas Faltantes</h4>
    </div>
    
    @foreach($cycleStats['missing_reasons'] as $reason)
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card reason-card border-{{ $reason['color'] }}" data-reason-code="{{ $reason['code'] }}" onclick="showReasonDetails('{{ $reason['code'] }}')">
            <div class="card-body text-center">
                <span class="badge badge-{{ $reason['color'] }} mb-2">{{ $reason['count'] }} contratos</span>
                <h6 class="mb-0">{{ $reason['title'] }}</h6>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<!-- Tabla de Facturas Generadas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Facturas Generadas en el Ciclo</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped table-hover w-100" id="facturasTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Contrato</th>
                            <th>Fecha</th>
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
                                    <a href="{{ route('facturas.show', $factura->id) }}" target="_blank" class="btn btn-sm btn-info">
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
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reasonModalTitle">Detalles</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-striped" id="reasonDetailsTable">
                    <thead>
                        <tr>
                            <th>Contrato</th>
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

@endsection

@section('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let barChart, doughnutChart;
const historicalData = @json($historicalData);
const cycleStats = @json($cycleStats);
const grupoId = {{ $grupo->id }};

// Inicializar gráficas
function initCharts() {
    // Gráfica de Barras
    const barCtx = document.getElementById('barChart').getContext('2d');
    const labels = historicalData.map(d => d.periodo_label);
    const generadas = historicalData.map(d => d.generadas);
    const esperadas = historicalData.map(d => d.esperadas);
    
    barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Generadas',
                data: generadas,
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }, {
                label: 'Esperadas',
                data: esperadas,
                backgroundColor: 'rgba(0, 123, 255, 0.7)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfica de Dona
    if (cycleStats && cycleStats.missing_reasons && cycleStats.missing_reasons.length > 0) {
        const doughnutCtx = document.getElementById('doughnutChart').getContext('2d');
        const reasonLabels = cycleStats.missing_reasons.map(r => r.title);
        const reasonCounts = cycleStats.missing_reasons.map(r => r.count);
        const reasonColors = cycleStats.missing_reasons.map(r => {
            const colorMap = {
                'info': 'rgba(23, 162, 184, 0.7)',
                'warning': 'rgba(255, 193, 7, 0.7)',
                'primary': 'rgba(0, 123, 255, 0.7)',
                'danger': 'rgba(220, 53, 69, 0.7)',
                'success': 'rgba(40, 167, 69, 0.7)',
                'secondary': 'rgba(108, 117, 125, 0.7)'
            };
            return colorMap[r.color] || 'rgba(108, 117, 125, 0.7)';
        });
        
        doughnutChart = new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: reasonLabels,
                datasets: [{
                    data: reasonCounts,
                    backgroundColor: reasonColors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }
}

// Cargar datos de un período  specific
function loadCycleData(periodo) {
    fetch(`/empresa/grupos-corte/api/${grupoId}/cycle-data/${periodo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar cards
                document.getElementById('totalContratos').textContent = data.cycleStats.total_contratos;
                document.getElementById('facturasGeneradas').textContent = data.cycleStats.facturas_generadas;
                document.getElementById('facturasEsperadas').textContent = data.cycleStats.facturas_esperadas;
                document.getElementById('facturasFaltantes').textContent = data.cycleStats.facturas_faltantes;
                document.getElementById('tasaExito').textContent = data.cycleStats.tasa_exito + '%';
                document.getElementById('promedioFacturas').textContent = data.metricas.promedio_facturas;
                
                // Actualizar gráficas
                updateCharts(data.historicalData, data.cycleStats);
                
                // Recargar tabla (mejor recargar la página completa para simplicidad)
                location.href = `/empresa/grupos-corte/analisis-ciclo/${grupoId}/${periodo}`;
            }
        })
        .catch(error => console.error('Error cargando datos:', error));
}

// Actualizar gráficas
function updateCharts(historical, stats) {
    // Actualizar gráfica de barras
    if (barChart) {
        barChart.data.labels = historical.map(d => d.periodo_label);
        barChart.data.datasets[0].data = historical.map(d => d.generadas);
        barChart.data.datasets[1].data = historical.map(d => d.esperadas);
        barChart.update();
    }
    
    // Actualizar gráfica de dona
    if (doughnutChart && stats.missing_reasons) {
        doughnutChart.data.labels = stats.missing_reasons.map(r => r.title);
        doughnutChart.data.datasets[0].data = stats.missing_reasons.map(r => r.count);
        doughnutChart.update();
    }
}

// Mostrar detalles de razón
function showReasonDetails(reasonCode) {
    const reason = cycleStats.missing_reasons.find(r => r.code === reasonCode);
    const details = cycleStats.missing_details.filter(d => d.razon_code === reasonCode);
    
    document.getElementById('reasonModalTitle').textContent = reason.title + ` (${reason.count} contratos)`;
    
    const tbody = document.getElementById('reasonDetailsBody');
    tbody.innerHTML = '';
    
    details.forEach(detail => {
        const row = `
            <tr>
                <td><a href="/empresa/contratos/${detail.contrato_id}" target="_blank">${detail.contrato_nro}</a></td>
                <td><a href="/empresa/contactos/${detail.cliente_id}" target="_blank">${detail.cliente_nombre}</a></td>
                <td>${detail.razon_description}</td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
    
    $('#reasonDetailsModal').modal('show');
}

// Document ready
$(document).ready(function() {
    // Inicializar gráficas
    initCharts();
    
    // Inicializar DataTable
    $('#facturasTable').DataTable({
        responsive: true,
        language: {
            'url': '/vendors/DataTables/es.json'
        },
        order: [[3, "desc"]]
    });
    
    // Selector de período
    $('#periodoSelector').on('change', function() {
        const periodo = $(this).val();
        if (periodo) {
            loadCycleData(periodo);
        }
    });
});
</script>
@endsection
