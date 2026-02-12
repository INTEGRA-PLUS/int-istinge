@extends('layouts.app')

@section('content')
	@if(Session::has('success'))
		<div class="alert alert-success" >
			{{Session::get('success')}}
		</div>
		<script type="text/javascript">
			setTimeout(function(){
			    $('.alert').hide();
			    $('.active_table').attr('class', ' ');
			}, 5000);
		</script>
	@endif
	
	@if(Session::has('danger'))
		<div class="alert alert-danger" >
			{{Session::get('danger')}}
		</div>
		<script type="text/javascript">
			setTimeout(function(){
			    $('.alert').hide();
			    $('.active_table').attr('class', ' ');
			}, 5000);
		</script>
	@endif

    <div class="alert alert-info" role="alert">
        <h4 class="alert-heading"><i class="fas fa-info-circle"></i> Informe de Discrepancias en Morosos</h4>
        <p>Este reporte cruza la información obtenida directamente de la lista de morosos configurada en su Mikrotik con la base de datos del sistema.</p>
        <p class="mb-0">Si observa un cliente marcado como <span class="badge badge-success">PAGADA (Discrepancia)</span>, significa que su última factura en el sistema ya ha sido pagada, pero su IP sigue en la lista de morosos del Mikrotik. Esto puede deberse a una interrupción en la comunicación con el router al momento del pago o una sobrecarga momentánea. <strong>No es un error crítico</strong>, pero le sugerimos verificar el estado del servicio del cliente.</p>
    </div>

    <div class="row card-description">
        <div class="col-md-12">
            <div class="form-group">
                <label for="mikrotik_id">Seleccione Mikrotik</label>
                <select class="form-control selectpicker" id="mikrotik_id" name="mikrotik_id" data-live-search="true" title="Seleccione una opción">
                    @foreach($mikrotiks as $mikrotik)
                        <option value="{{ $mikrotik->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $mikrotik->nombre }} - {{ $mikrotik->ip }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

	<div class="row card-description">
		<div class="col-md-12">
			<table class="table table-striped table-hover w-100" id="tabla-morosos">
				<thead class="thead-dark">
					<tr>
						<th>IP</th>
						<th>Cliente (Sistema)</th>
						<th>Comentario Mikrotik</th>
						<th>Estado Sistema</th>
						<th>Fecha Creación</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
@endsection

@section('scripts')
<script>
    var tabla = null;

    $(document).ready(function() {
        // Initialize DataTable with empty data initially or wait for selection
		tabla = $('#tabla-morosos').DataTable({
			responsive: true,
			serverSide: false,
			processing: true,
			searching: true,
            "pageLength": 50,
			language: {
				'url': '/vendors/DataTables/es.json'
			},
			order: [
				[0, "desc"]
			],
			ajax: {
                url: '{{ route("morosos.listar") }}',
                data: function (d) {
                    d.mikrotik_id = $('#mikrotik_id').val();
                },
                dataSrc: function (json) {
                    if (!json.success) {
                        return [];
                    }
                    return json.data;
                }
            },
			columns: [
				{data: 'ip'},
                {
                    data: 'contrato',
                    render: function(data, type, row) {
                        if (data) {
                            return '<a href="/empresa/contratos/'+data.id+'" target="_blank">' + data.nombre_cliente + ' (Contrato: ' + data.nro + ')</a>';
                        }
                        return '<span class="text-muted">No asociado</span>';
                    }
                },
				{
                    data: 'comentario',
                    render: function(data) {
                        return data ? data : '<span class="text-muted">N/A</span>';
                    }
                },
                {
                    data: 'estado_sistema',
                    render: function(data, type, row) {
                        if (row.tiene_discrepancia) {
                            return '<span class="badge badge-success" data-toggle="tooltip" title="' + row.mensaje_discrepancia + '">PAGADA (Discrepancia) <i class="fas fa-exclamation-triangle"></i></span>';
                        } else if (data == 'En Mora') {
                            return '<span class="badge badge-danger">En Mora</span>';
                        } else if (data == 'Sin Facturas') {
                            return '<span class="badge badge-secondary">Sin Facturas</span>';
                        } else {
                            return '<span class="badge badge-light">' + data + '</span>';
                        }
                    }
                },
				{data: 'fecha_creacion'}
			]
		});

        $('#mikrotik_id').on('change', function() {
            tabla.ajax.reload();
        });
    });
</script>
@endsection
