@extends('layouts.app')

@section('boton')
    @if(auth()->user()->modo_lectura())
	    <div class="alert alert-warning text-left" role="alert">
	        <h4 class="alert-heading text-uppercase">Integra Colombia: Suscripción Vencida</h4>
	       <p>Si desea seguir disfrutando de nuestros servicios adquiera alguno de nuestros planes.</p>
<p>Medios de pago Nequi: 3206909290 Cuenta de ahorros Bancolombia 42081411021 CC 1001912928 Ximena Herrera representante legal. Adjunte su pago para reactivar su membresía</p>
	    </div>
	@else
    @if(isset($_SESSION['permisos']['52']))
        <a href="{{route('notascredito.create')}}" class="btn btn-primary btn-sm" ><i class="fas fa-plus"></i> Nueva Nota de Crédito</a>
    @endif
    <a href="javascript:abrirFiltrador()" class="btn btn-info btn-sm my-1" id="boton-filtrar"><i class="fas fa-search"></i>Filtrar</a>
    @endif
@endsection

@section('content')
@if (Session::has('success'))
<div class="alert alert-success" style="margin-left: 2%;margin-right: 2%;">
	{{ Session::get('success') }}
</div>
@endif

@if (Session::has('message_success'))
<div class="alert alert-success" style="margin-left: 2%;margin-right: 2%;">
	{{ Session::get('message_success') }}
</div>
@endif

@if (session('message_denied_btw'))
<div class="alert alert-danger">
    {!! session('message_denied_btw') !!}
</div>
@endif

@if (Session::has('message_denied'))
<div class="alert alert-danger" role="alert">
    @if (Session::get('statusCode') == 400)
        {{ 'La Dian está presentando problemas en este momento. Por favor intenta más tarde' }}
    @else
        {{ Session::get('message_denied') }}
        @if (Session::has('errorReason'))
            <br> <strong>Razon(es): <br></strong>
            @php
                $errorReason = Session::get('errorReason');
            @endphp
            @if (is_array($errorReason) && count($errorReason) > 0)
                @php $cont = 0 @endphp
                @foreach ($errorReason as $error)
                    @php $cont = $cont + 1; @endphp
                    {{ $cont }} - {{ $error }} <br>
                @endforeach
            @else
                {{ $errorReason }}
            @endif
        @endif
    @endif
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

	<div class="container-fluid d-none" id="form-filter">
		<fieldset>
			<legend>Filtro de Búsqueda</legend>
			<div class="card shadow-sm border-0">
				<div class="card-body pb-3 pt-2" style="background: #f9f9f9;">
					<div class="row">
						<div class="col-md-3 pl-1 pt-1">
							<input type="text" placeholder="Nro" id="nro" class="form-control rounded">
						</div>
						<div class="col-md-3 pl-1 pt-1">
							<input type="text" placeholder="Cliente" id="nombre" class="form-control rounded">
						</div>
						<div class="col-md-3 pl-1 pt-1">
							<input type="text" placeholder="Fecha" id="fecha" class="form-control rounded">
						</div>
						<div class="col-md-3 pl-1 pt-1">
						    <select title="Estado Emisión" class="form-control rounded selectpicker" id="emitida">
						        <option value="1">Emitida a la DIAN</option>
								<option value="A">No emitida a la DIAN</option>
							</select>
						</div>
						<div class="col-md-12 pl-1 pt-2 text-center">
							<a href="javascript:cerrarFiltrador()" class="btn btn-icons ml-1 btn-outline-danger rounded btn-sm p-1" title="Limpiar parámetros de busqueda"><i class="fas fa-times"></i></a>
							<a href="javascript:void(0)" id="filtrar" class="btn btn-icons btn-outline-info rounded btn-sm p-1" title="Iniciar busqueda avanzada"><i class="fas fa-search"></i></a>
						</div>
					</div>
				</div>
			</div>
		</fieldset>
	</div>

	<div class="container-fluid">
    	<div class="row card-description" style="padding: 1% 1%; margin-bottom: 0;">
    		<div class="col-md-12 text-right">
    			@if(isset($_SESSION['permisos']['750']))
    			<a href="{{route('campos.organizar', 18)}}" class="btn btn-warning btn-sm my-1"><i class="fas fa-table"></i> Organizar Tabla</a>
    			@endif
    			@if(isset($_SESSION['permisos']['830']))
    			<div class="dropdown d-inline-block">
                    <button class="btn btn-warning dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Acciones en Lote
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="javascript:void(0)" id="btn_emitir"><i class="fas fa-server"></i> Emitir Notas de Crédito en Lote</a>
                    </div>
                </div>
                @endif
    	</div>
    </div>

	<div class="row card-description">
		<div class="col-md-12">
			<table class="table table-striped table-hover w-100" id="tabla-notac">
				<thead class="thead-dark">
					<tr>
					    @foreach($tabla as $campo)
    					    <th>{{$campo->nombre}}</th>
    					@endforeach
						<th>Acciones</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
@endsection


@section('scripts')
<script>
	$(document).ready(function () {
		$.ajax({
			url: 'notascredito/validatetime/emicion',
			headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
			method: 'POST',
			success: function(factura){
				if(factura.length > 0){
					var text = "";
					$.each(factura,function(index,value){
						text = text + `${value.nro} <br>`;
					})
					console.log(text);
					Swal.fire({
						type: 'warning',
						title: 'DIAN',
						html: `Tienes Notas Credito realizadas hace 24h sin emitir <br>` + text,
					})
				}
			}
		});
	})

	var tabla = null;
    window.addEventListener('load',
    function() {
		$('#tabla-notac').DataTable({
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
			ajax: '{{url("lnotascredito")}}',
			headers: {
				'X-CSRF-TOKEN': '{{csrf_token()}}'
			},
			@if(isset($_SESSION['permisos']['830']))
			select: true,
            select: {
                style: 'multi',
            },
            dom: 'Blfrtip',
            buttons: [{
                text: '<i class="fas fa-check"></i> Seleccionar todos',
                action: function() {
                    $('#tabla-notac').DataTable().rows({
                        page: 'current'
                    }).select();
                }
            },
            {
                text: '<i class="fas fa-times"></i> Deseleccionar todos',
                action: function() {
                    $('#tabla-notac').DataTable().rows({
                        page: 'current'
                    }).deselect();
                }
            }],
            @endif
			columns: [
			    @foreach($tabla as $campo)
                    {data: '{{$campo->campo}}'},
                @endforeach
				{data: 'acciones'},
			]
		});

        tabla = $('#tabla-notac').DataTable();

        $('#tabla-notac').on('preXhr.dt', function(e, settings, data) {
			data.nro     = $('#nro').val();
			data.nombre  = $('#nombre').val();
			data.fecha   = $('#fecha').val();
			data.emitida = $('#emitida').val();
			data.filtro  = true;
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

        $('#nro, #nombre').on('keyup',function(e) {
            if(e.which > 32 || e.which == 8) {
                getDataTable();
                return false;
            }
        });

        $('#emitida, #fecha').on('change',function() {
            getDataTable();
            return false;
        });

        $('#tabla-notac tbody').on('click', 'tr', function () {
			var table = $('#tabla-notac').DataTable();
			var nro = table.rows('.selected').data().length;

			if(table.rows('.selected').data().length >= 0){
				$("#btn_emitir").removeClass('disabled d-none');
			}else{
				$("#btn_emitir").addClass('disabled d-none');
			}
        });

        $('#btn_emitir').on('click', function(e) {
            var table = $('#tabla-notac').DataTable();
            var nro = table.rows('.selected').data().length;

            if (nro <= 0) {
                swal({
                    title: 'ERROR',
                    html: 'Para ejecutar esta acción, debe al menos seleccionar una nota de crédito',
                    type: 'error',
                });
                return false;
            }

            var notas = [];
            for (i = 0; i < nro; i++) {
                notas.push(table.rows('.selected').data()[i]['id']);
            }

            swal({
                title: '¿Desea realizar la emisión de ' + nro + ' notas de crédito electrónicas?',
                text: 'Esto puede demorar unos minutos. Al Aceptar, no podrá cancelar el proceso',
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#00ce68',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.value) {
                    cargando(true);

                    var url = window.location.pathname.split("/")[1] === "software" ?
                        `/software/empresa/notascredito/emisionmasivaxml/` + notas :
                        `/empresa/notascredito/emisionmasivaxml/` + notas;

                    $.ajax({
                        url: url,
                        method: 'GET',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function(data) {
                            cargando(false);
                            swal({
                                title: 'PROCESO REALIZADO',
                                html: data.text,
                                type: 'success',
                                showConfirmButton: true,
                                confirmButtonColor: '#1A59A1',
                                confirmButtonText: 'ACEPTAR',
                            });
                            getDataTable();
                        },
                        error: function(xhr) {
                            cargando(false);
                            if (xhr.status === 500) {
                                swal({
                                    title: 'INFO',
                                    html: 'Se han emitido algunas notas de crédito, vuelve a emitir otro lote si quedan notas pendientes.',
                                    type: 'info',
                                    showConfirmButton: true,
                                    confirmButtonColor: '#d33',
                                    confirmButtonText: 'Recargar Página',
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        }
                    });
                }
            });
            console.log(notas);
        });
    });

	function getDataTable() {
		$('#tabla-notac').DataTable().ajax.reload();
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
		$('#nro').val('');
		$('#nombre').val('');
		$('#fecha').val('');
		$('#emitida').val('').selectpicker('refresh');
		$('#form-filter').addClass('d-none');
		$('#boton-filtrar').html('<i class="fas fa-search"></i> Filtrar');
		getDataTable();
	}
</script>
@endsection
