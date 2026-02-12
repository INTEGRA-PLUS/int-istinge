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

    <div class="row card-description">
        <div class="col-md-12">
            <div class="form-group">
                <label for="mikrotik_id">Seleccione Mikrotik</label>
                <select class="form-control selectpicker" id="mikrotik_id" name="mikrotik_id" data-live-search="true" title="Seleccione una opción">
                    @foreach($mikrotiks as $mikrotik)
                        <option value="{{ $mikrotik->id }}">{{ $mikrotik->nombre }} - {{ $mikrotik->ip }}</option>
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
						<th>Comentario</th>
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
				{data: 'comentario'},
				{data: 'fecha_creacion'}
			]
		});

        $('#mikrotik_id').on('change', function() {
            tabla.ajax.reload();
        });
    });
</script>
@endsection
