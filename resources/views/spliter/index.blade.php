@extends('layouts.app')

@section('styles')

@endsection

@section('boton')
    @if(auth()->user()->modo_lectura())
	    <div class="alert alert-warning text-left" role="alert">
	        <h4 class="alert-heading text-uppercase">Integra Colombia: Suscripción Vencida</h4>
	       <p>Si desea seguir disfrutando de nuestros servicios adquiera alguno de nuestros planes.</p>
<p>Medios de pago Nequi: 3026003360 Cuenta de ahorros Bancolombia 42081411021 CC 1001912928 Ximena Herrera representante legal. Adjunte su pago para reactivar su membresía</p>
	    </div>
	@else
    <a href="javascript:abrirFiltrador()" class="btn btn-info btn-sm my-1" id="boton-filtrar"><i class="fas fa-search"></i>Filtrar</a>
    <?php if (isset($_SESSION['permisos']['712'])) { ?>
        <a href="{{route('spliter.create')}}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Nuevo Spliter Optico</a>
    <?php } ?>
    @endif
@endsection

@section('content')

    @if(Session::has('success'))
        <div class="alert alert-success" style="margin-left: 2%;margin-right: 2%;">
	    {{Session::get('success')}}
        </div>
        <script type="text/javascript">
            setTimeout(function() {
                $('.alert').hide();
                $('.active_table').attr('class', ' ');
            }, 5000);
        </script>
    @endif

    @if(Session::has('danger'))
        <div class="alert alert-danger" style="margin-left: 2%;margin-right: 2%;">
	    {{Session::get('danger')}}
        </div>
        <script type="text/javascript">
            setTimeout(function() {
                $('.alert').hide();
                $('.active_table').attr('class', ' ');
            }, 5000);
        </script>
    @endif

	{{-- Filtro (opcional). Mantener estructura similar a otros módulos --}}
	<div class="container-fluid d-none" id="form-filter">
		<fieldset>
			<legend>Filtro de Búsqueda</legend>
			<div class="card shadow-sm border-0">
				<div class="card-body pb-3 pt-2" style="background: #f9f9f9;">
					<div class="row">
						<div class="col-md-4 pl-1 pt-1">
							<input type="text" placeholder="Nombre" id="nombre" class="form-control rounded">
						</div>
						<div class="col-md-3 pl-1 pt-1">
						    <select title="Estado" class="form-control rounded selectpicker" id="status">
						        <option value="">Todos</option>
						        <option value="1">Habilitado</option>
								<option value="0">Deshabilitado</option>
							</select>
						</div>
						<div class="col-md-2 pl-1 pt-1 text-left">
							<a href="javascript:cerrarFiltrador()" class="btn btn-icons ml-1 btn-outline-danger rounded btn-sm p-1 float-right" title="Limpiar parámetros de busqueda"><i class="fas fa-times"></i></a>
							<a href="javascript:void(0)" id="filtrar" class="btn btn-icons btn-outline-info rounded btn-sm p-1 float-right" title="Iniciar busqueda avanzada"><i class="fas fa-search"></i></a>
						</div>
					</div>
				</div>
			</div>
		</fieldset>
	</div>

	<div class="row card-description">
		@if(isset($_SESSION['permisos']['836']))
			<div class="col-md-12">
	    		<div class="container-filtercolumn form-inline">
	                @if(auth()->user()->modo_lectura())
	                @else
	                    <div class="dropdown mr-1">
	                    	<button class="btn btn-warning dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	                    		Acciones en Lote
	                    	</button>
	                    	<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
	                    		<a class="dropdown-item" href="javascript:void(0)" id="btn_enabled"><i class="fas fa-fw fa-power-off" style="margin-left:4px; "></i> Habilitar Nodos</a>
	                    		<a class="dropdown-item" href="javascript:void(0)" id="btn_disabled"><i class="fas fa-fw fa-power-off" style="margin-left:4px; "></i> Deshabilitar Nodos</a>
	                    		<a class="dropdown-item" href="javascript:void(0)" id="btn_destroy"><i class="fas fa-fw fa-times" style="margin-left:4px; "></i> Eliminar Nodos</a>
	                    	</div>
	                    </div>
	                @endif
				</div>
			</div>
		@endif
		<div class="col-md-12">
			<table class="table table-striped table-hover w-100" id="tabla-spliters">
				<thead class="thead-dark">
                    <tr>
                        <th>NOMBRE DEL SPLITER</th>
                        <th>UBICACIÓN</th>
                        <th>COORDENADAS</th>
                        <th>NUMERO DE SALIDAS</th>
                        <th>NUMERO DE CAJAS NAP</th>
                        <th>CAJAS DISPONIBLES</th>
                        <th>Estado</th>
                        <th>Acciones</th>
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
		tabla = $('#tabla-spliters').DataTable({
			responsive: true,
			serverSide: true,
			processing: true,
			searching: false,
			language: {
				'url': '/vendors/DataTables/es.json'
			},
			order: [
				[0, "desc"]
			],
			"pageLength": {{ Auth::user()->empresa()->pageLength }},
			ajax: '{{ route("spliter.datatable") }}',
			headers: {
				'X-CSRF-TOKEN': '{{csrf_token()}}'
			},
			columns: [
			    {data: 'nombre', name: 'nombre'},
			    {data: 'ubicacion', name: 'ubicacion'},
				{data: 'coordenadas', name: 'coordenadas'},
				{data: 'num_salida', name: 'num_salida'},
				{data: 'num_cajas_naps', name: 'num_cajas_naps'},
				{data: 'cajas_disponible', name: 'cajas_disponible'},
				{data: 'status', name: 'status'},
				{data: 'acciones', name: 'acciones', orderable: false, searchable: false},
			],
		});

        tabla.on('preXhr.dt', function(e, settings, data) {
            data.nombre = $('#nombre').val();
            data.status = $('#status').val();
            data.filtro = true;
        });

        $('#filtrar').on('click', function(e) {
            getDataTable();
            return false;
        });

        $('#form-filter').on('keypress',function(e) {
            if(e.which == 13) {
                getDataTable();
                return false;
            }
        });

        $('#nombre').on('keyup',function(e) {
        	if(e.which > 32 || e.which == 8) {
        		getDataTable();
        		return false;
        	}
        });

        $('#status').on('change',function() {
        	getDataTable();
        	return false;
        });

        $('#btn_enabled').click( function () {
            states('enabled');
        });

        $('#btn_disabled').click( function () {
            states('disabled');
        });

        $('#btn_destroy').click( function () {
            destroy();
        });
    });

	function getDataTable() {
		tabla.ajax.reload();
	}

	function abrirFiltrador() {
		if ($('#form-filter').hasClass('d-none')) {
			$('#boton-filtrar').html('<i class="fas fa-times"></i> Cerrar');
			$('#form-filter').removeClass('d-none');
		} else {
			$('#boton-filtrar').html('<i class="fas fa-search"></i> Filtrar');
			cerrarFiltrador();
		}
	}

	function cerrarFiltrador() {
		$('#nombre').val('');
		$('#status').val('').selectpicker('refresh');
		$('#form-filter').addClass('d-none');
		$('#boton-filtrar').html('<i class="fas fa-search"></i> Filtrar');
		getDataTable();
	}

	function states(state){
        var nodos = [];

        var table = $('#tabla-spliters').DataTable();
        var nro = table.rows('.selected').data().length;

        if(nro<=1){
            swal({
                title: 'ERROR',
                html: 'Para ejecutar esta acción, debe al menos seleccionar dos nodos',
                type: 'error',
            });
            return false;
        }

        for (i = 0; i < nro; i++) {
            nodos.push(table.rows('.selected').data()[i]['id']);
        }

        if(state === 'enabled'){
            var states = 'habilitar';
        }else{
            var states = 'deshabilitar';
        }

        swal({
            title: '¿Desea '+states+' '+nro+' nodos en lote?',
            text: 'Al Aceptar, no podrá cancelar el proceso',
            type: 'question',
            showCancelButton: true,
            confirmButtonColor: '#00ce68',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Aceptar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.value) {
                cargando(true);

                if (window.location.pathname.split("/")[1] === "software") {
                    var url = `/software/empresa/nodos/`+nodos+`/`+state+`/state_lote`;
                }else{
                    var url = `/empresa/nodos/`+nodos+`/`+state+`/state_lote`;
                }

                $.ajax({
                    url: url,
                    method: 'GET',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(data) {
                        cargando(false);
                        if(data.state === 'enabled'){
                        	var states = 'habilitados';
                        }else{
                        	var states = 'deshabilitados';

                        }
                        swal({
                            title: 'PROCESO REALIZADO',
                            html: '<strong>'+data.correctos+' nodos '+states+'</strong><br><strong>'+data.fallidos+' nodos no '+states+'</strong>',
                            type: 'success',
                            showConfirmButton: true,
                            confirmButtonColor: '#1A59A1',
                            confirmButtonText: 'ACEPTAR',
                        });
                        getDataTable();
                    }
                })
            }
        })
    }

    function destroy(){
        var nodos = [];

        var table = $('#tabla-spliters').DataTable();
        var nro = table.rows('.selected').data().length;

        if(nro<=1){
            swal({
                title: 'ERROR',
                html: 'Para ejecutar esta acción, debe al menos seleccionar dos nodos',
                type: 'error',
            });
            return false;
        }

        for (i = 0; i < nro; i++) {
            nodos.push(table.rows('.selected').data()[i]['id']);
        }

        swal({
            title: '¿Desea eliminar '+nro+' nodos en lote?',
            text: 'Al Aceptar, no podrá cancelar el proceso',
            type: 'question',
            showCancelButton: true,
            confirmButtonColor: '#00ce68',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Aceptar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.value) {
                cargando(true);

                if (window.location.pathname.split("/")[1] === "software") {
                    var url = `/software/empresa/nodos/`+nodos+`/destroy_lote`;
                }else{
                    var url = `/empresa/nodos/`+nodos+`/destroy_lote`;
                }

                $.ajax({
                    url: url,
                    method: 'GET',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(data) {
                        cargando(false);
                        swal({
                            title: 'PROCESO REALIZADO',
                            html: '<strong>'+data.correctos+' nodos '+data.state+'</strong><br><strong>'+data.fallidos+' nodos no '+data.state+' por estar en uso</strong>',
                            type: 'success',
                            showConfirmButton: true,
                            confirmButtonColor: '#1A59A1',
                            confirmButtonText: 'ACEPTAR',
                        });
                        getDataTable();
                    }
                })
            }
        })
    }
</script>
@endsection
