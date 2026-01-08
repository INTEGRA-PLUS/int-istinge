@extends('layouts.app')

@section('boton')
    <a href="{{ route('contratos.show',$contrato->id )}}"  class="btn btn-primary" title="Regresar al Detalle"><i class="fas fa-step-backward"></i></i> Regresar al Detalle</a>
@endsection

@section('style')
    <style>
        .bg-th{
            text-align: center;
        }
        .info th {
            text-align: center;
        }
    </style>
@endsection

@section('content')
    <div class="row card-description">
    	<div class="col-md-12 mb-4">
    		<div class="table-responsive">
    			<table class="table table-striped table-bordered table-sm info">
    				<tbody>
    					<tr>
    						<th class="bg-th">CONTRATO</th>
    						<th class="bg-th">CLIENTE</th>
    						<th class="bg-th">DIRECCIÓN IP</th>
    						<th class="bg-th">INTERFAZ</th>
    						<th class="bg-th">SERVIDOR ASOCIADO</th>
    						<th class="bg-th">CONEXIÓN</th>
    						{{--<th class="bg-th">PLAN</th>--}}
    					</tr>
    					<tr class="text-center">
    						<td>{{ $contrato->nro }}</td>
    						<td>{{ $contrato->cliente()->nombre }} {{ $contrato->cliente()->apellido1 }} {{ $contrato->cliente()->apellido2 }}</td>
    						<td>{{ $contrato->ip }}</td>
    						<td>{{ $contrato->interfaz }}</td>
    						<td>{{ $contrato->servidor()->nombre }}</td>
    						<td>{{ $contrato->conexion() }}</td>
    						{{--<td>{{ $contrato->plan()->name }}</td>--}}
    					</tr>
    				</tbody>
    			</table>
    		</div>
    	</div>

        <div class="col-md-3 text-center">
            <a href="{{ route('contratos.grafica_proxy', [$contrato->id, 'daily']) }}" target="_blank" class="btn btn-system mb-4">
                <h5 class="pb-0 mb-0 font-weight-bold">GRÁFIO DIARIO</h5><p class="mb-0">(promedio de 5 minutos)</p>
            </a>
        </div>
        <div class="col-md-3 text-center">
            <a href="{{ route('contratos.grafica_proxy', [$contrato->id, 'weekly']) }}" target="_blank" class="btn btn-system mb-4">
                <h5 class="pb-0 mb-0 font-weight-bold">GRÁFIO SEMANAL</h5><p class="mb-0">(promedio de 30 minutos)</p>
            </a>
        </div>
        <div class="col-md-3 text-center">
            <a href="{{ route('contratos.grafica_proxy', [$contrato->id, 'monthly']) }}" target="_blank" class="btn btn-system">
                <h5 class="pb-0 mb-0 font-weight-bold">GRÁFIO MENSUAL</h5><p class="mb-0">(promedio de 2 horas)</p>
            </a>
        </div>
        <div class="col-md-3 text-center">
            <a href="{{ route('contratos.grafica_proxy', [$contrato->id, 'yearly']) }}" target="_blank" class="btn btn-system">
                <h5 class="pb-0 mb-0 font-weight-bold">GRÁFIO ANUAL</h5><p class="mb-0">(promedio de 1 día)</p>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 text-center">
            <a href="{{ route('contratos.grafica', $contrato->id) }}" target="_blank" class="btn btn-system mb-4">
                <h5 class="pb-0 mb-0 font-weight-bold">GRÁFIO TIEMPO REAL</h5><p class="mb-0">(descarga y carga)</p>
            </a>
        </div>
    </div>

@endsection

@section('scripts')
<script>
	// Filtro para ocultar errores de extensiones del navegador y recursos de terceros
	(function() {
		const originalError = console.error;
		const originalWarn = console.warn;

		// Errores que debemos ignorar (provenientes de extensiones o recursos de terceros)
		const ignoredErrors = [
			'whatsapp',
			'wa-mms',
			'media.feoh',
			'media-bog',
			'media.cdn.wha',
			'dit.whatsapp.net',
			'multiple-uim-roots',
			'installHook.js'
		];

		function shouldIgnoreError(message) {
			if (!message) return false;
			const msgStr = typeof message === 'string' ? message : (message.toString ? message.toString() : '');
			return ignoredErrors.some(pattern =>
				msgStr.toLowerCase().includes(pattern.toLowerCase())
			);
		}

		console.error = function(...args) {
			const message = args[0];
			if (!shouldIgnoreError(message)) {
				originalError.apply(console, args);
			}
		};

		console.warn = function(...args) {
			const message = args[0];
			if (!shouldIgnoreError(message)) {
				originalWarn.apply(console, args);
			}
		};

		// También filtrar errores de red en la consola de recursos fallidos
		window.addEventListener('error', function(e) {
			const src = e.target?.src || e.filename || '';
			if (src && (
				src.includes('whatsapp') ||
				src.includes('wa-mms') ||
				src.includes('media.feoh') ||
				src.includes('media-bog') ||
				src.includes('installHook.js')
			)) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
		}, true);
	})();
</script>
@endsection
