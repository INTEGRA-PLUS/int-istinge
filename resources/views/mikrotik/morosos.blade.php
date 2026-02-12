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
    window.addEventListener('load',
    function() {
		tabla = $('#tabla-morosos').DataTable({
			responsive: true,
			serverSide: false, // Client side processing since data is from API list
			processing: true,
			searching: true,
			language: {
				'url': '/vendors/DataTables/es.json'
			},
			order: [
				[0, "desc"]
			],
			ajax: {
                url: '{{ url("/api/morosos") }}',
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
    });
</script>
@endsection
