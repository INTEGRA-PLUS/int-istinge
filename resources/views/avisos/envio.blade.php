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
			}, 10000);
		</script>
	@endif
	<form method="POST" action="{{ route('avisos.envio_aviso') }}" style="padding: 2% 3%;" role="form" class="forms-sample" novalidate id="form-retencion">
	    @csrf
	    <input type="hidden" value="{{$opcion}}" name="type">
	    <div class="row">
			<div class="col-md-3 form-group">
				@if(!request()->vencimiento)
					<label>Facturas vencidas (opcional)</label>
					<input type="text" class="form-control datepicker"  id="vencimiento" value="" name="vencimiento">
				@else
				<a href="{{ url()->current() }}">
				<button type="button" class="btn btn-primary position-relative">
					Vencidas: {{ request()->vencimiento }}
					<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
					  X
					</span>
				</button>
				</a>
				@endif
			</div>

	        <div class="col-md-3 form-group">
	            <label class="control-label">Plantilla <span class="text-danger">*</span></label>

				<!-- UN SOLO SELECT DINÁMICO -->
				<select name="plantilla" id="plantilla_dinamico" class="form-control selectpicker" title="Seleccione" data-live-search="true" data-size="5" required onchange="cargarPlantillaSeleccionada()">
					<!-- Las opciones se cargarán dinámicamente con JavaScript -->
        	        @foreach($plantillas as $plantilla)
        	        <option {{old('plantilla')==$plantilla->id?'selected':''}} value="{{$plantilla->id}}" data-tipo="{{$plantilla->tipo == 3 ? 'meta' : 'normal'}}">{{$plantilla->title}}</option>
        	        @endforeach
        	    </select>

        	    <span class="help-block error">
        	        <strong>{{ $errors->first('plantilla') }}</strong>
        	    </span>
        	</div>

			@if(isset($servidores))
			<div class="col-md-3 form-group">
	            <label class="control-label">Servidor<span class="text-danger"></span></label>
        	    <select name="servidor" id="servidor" class="form-control selectpicker " onchange="refreshClient()" title="Seleccione" data-live-search="true" data-size="5">
        	        @foreach($servidores as $servidor)
        	        <option {{old('servidor')==$servidor->id?'selected':''}} value="{{$servidor->id}}">{{$servidor->nombre}}</option>
        	        @endforeach
        	    </select>
        	    <span class="help-block error">
        	        <strong>{{ $errors->first('servidor') }}</strong>
        	    </span>
        	</div>
			@endif

			@if(isset($gruposCorte))
			<div class="col-md-3 form-group">
	            <label class="control-label">Grupo corte<span class="text-danger"></span></label>
        	    <select name="corte" id="corte" class="form-control selectpicker" onchange="refreshClient()" title="Seleccione" data-live-search="true" data-size="5">
        	        @foreach($gruposCorte as $corte)
        	        <option {{old('corte')==$corte->id?'selected':''}} value="{{$corte->id}}">{{$corte->nombre}}</option>
        	        @endforeach
        	    </select>
        	    <span class="help-block error">
        	        <strong>{{ $errors->first('corte') }}</strong>
        	    </span>
        	</div>
			@endif

        	<div class="col-md-3 form-group">
	            <label class="control-label">Barrio</label>
        	    <input class="form-control" type="text" name="barrio" id="barrio">
        	    <span class="help-block error">
        	        <strong>{{ $errors->first('barrio') }}</strong>
        	    </span>
        	</div>

            <div class="col-md-3 form-group">
	            <label class="control-label">ESTADO CLIENTE<span class="text-danger"></span></label>
        	    <select name="options" id="options" class="form-control selectpicker" onchange="chequeo()" title="Seleccione" data-live-search="true" data-size="5">
        	        <option {{old('options')==1?'selected':''}} value="1" id='radio_1'>HABILITADOS</option>
        	        <option {{old('options')==2?'selected':''}} value="2" id='radio_2'>DESHABILITADOS</option>
        	        <option {{old('options')==3?'selected':''}} value="3" id='radio_3'>MANUAL</option>
        	    </select>
        	    <span class="help-block error">
        	        <strong>{{ $errors->first('options') }}</strong>
        	    </span>
        	</div>

            <div class="col-md-3 form-group">
                <label class="control-label">OPCIONES SALDO<span class="text-danger"></span></label>
                <select name="opciones_saldo" id="opciones_saldo" class="form-control selectpicker" onchange="refreshClient()" title="Seleccione" data-live-search="true" data-size="5">
                    <option {{old('opciones_saldo')=='mayor_a'?'selected':''}} value="mayor_a">SALDO MAYOR A</option>
                    <option {{old('opciones_saldo')=='mayor_igual'?'selected':''}} value="mayor_igual">SALDO MAYOR O IGUAL A</option>
                    <option {{old('opciones_saldo')=='igual_a'?'selected':''}} value="igual_a">SALDO IGUAL A</option>
                    <option {{old('opciones_saldo')=='menor_a'?'selected':''}} value="menor_a">SALDO MENOR A</option>
                    <option {{old('opciones_saldo')=='menor_igual'?'selected':''}} value="menor_igual">SALDO MENOR IGUAL A</option>
                </select>
                <span class="help-block error">
        	        <strong>{{ $errors->first('options') }}</strong>
        	    </span>
            </div>

            <div class="col-md-3 form-group">
                <label class="control-label">Corregimiento / Vereda</label>
                <input class="form-control" type="text" name="vereda" id="vereda" autocomplete="off">
                <span class="help-block error">
        	        <strong>{{ $errors->first('vereda') }}</strong>
        	    </span>
            </div>

            <div class="col-md-3 form-group">
                <label class="control-label">Valor Saldo</label>
                <input class="form-control" type="text" name="valor_saldo" id="valor_saldo"  oninput="refreshClient()">
                <span class="help-block error">
        	        <strong>{{ $errors->first('barrio') }}</strong>
        	    </span>
            </div>

        	<div class="col-md-3 form-group" id="seleccion_manual">
	            <label class="control-label">Selección manual de clientes</label>
        	    <select name="contrato[]" id="contrato_sms" class="form-control selectpicker" title="Seleccione" data-live-search="true" data-size="5" required multiple data-actions-box="true" data-select-all-text="Todos" data-deselect-all-text="Ninguno">
        	        @php $estados=\App\Contrato::tipos();@endphp
        	        @foreach($estados as $estado)
        	        <optgroup label="{{$estado['nombre']}}">
        	            @foreach($contratos as $contrato)
        	                @if($contrato->state==$estado['state'])
        	                    <option class="{{$contrato->state}}
									grupo-{{ $contrato->grupo_corte()->id ?? 'no' }}
									servidor-{{ $contrato->servidor()->id ?? 'no' }}
									factura-{{ $contrato->factura_id != null ?  'si' : 'no'}}
                                    vereda-{{ $contrato->vereda != null ? $contrato->vereda : 'no' }}
                                    "
									value="{{$contrato->id}}" {{$contrato->client_id==$id?'selected':''}}
                                        data-saldo="<?php echo e($contrato->factura_total); ?>">
									{{$contrato->c_nombre}} {{ $contrato->c_apellido1 }}
									{{ $contrato->c_apellido2 }} - {{$contrato->c_nit}}
									(contrato: {{ $contrato->nro }})
								</option>

        	                @endif
        	            @endforeach
        	        </optgroup>
        	        @endforeach
        	    </select>
        	    <span class="help-block error">
        	        <strong>{{ $errors->first('cliente') }}</strong>
        	    </span>
        	</div>


			<div class="col-md-3">
				<div class="form-check form-check-inline d-flex p-3">
					<input class="form-check-input" type="checkbox" id="isAbierta" name="isAbierta" value="true" onclick="refreshClient()">
					<label class="form-check-label" for="isAbierta"  style="font-weight:bold">Solo facturas abiertas</label>
				</div>
			</div>

			<!-- Sección de parámetros para plantillas Meta -->
			<div class="col-md-12" id="parametros-meta" style="display: none;">
				<hr class="my-4">
				<h5><i class="fa fa-sliders"></i> Configuración de Parámetros Dinámicos</h5>
				<div id="inputs-parametros">
					<!-- Los inputs se generarán dinámicamente aquí -->
				</div>
			</div>

			<!-- Preview del mensaje -->
			<div class="col-md-12" id="preview-mensaje-meta" style="display: none;">
				<!-- Aquí se mostrará la vista previa dinámicamente -->
			</div>
       </div>

	   <small>Los campos marcados con <span class="text-danger">*</span> son obligatorios</small>

	   <hr>

	   <div class="row" >
	       <div class="col-sm-12" style="text-align: right;  padding-top: 1%;">
	           <a href="{{route('avisos.index')}}" class="btn btn-outline-secondary">Cancelar</a>
	           <button type="submit" id="submitcheck" onclick="submitLimit(this.id); alert_swal();" class="btn btn-success">Guardar</button>
	       </div>
	   </div>
    </form>
@endsection

@section('scripts')
<script type="text/javascript">
	// ============================================================
	// VARIABLES GLOBALES PARA PLANTILLAS META
	// ============================================================
	let plantillaMetaActual = null;
	let bodyTextValues = [];

	@include('includes.campos-dinamicos')

	$(document).ready(function() {
		const plantillaId = $('#plantilla_dinamico').val();
		if (plantillaId) {
			cargarPlantillaSeleccionada();
		}
	});

	function cargarPlantillaSeleccionada() {
		const plantillaId = $('#plantilla_dinamico').val();
		const tipo = $('#plantilla_dinamico option:selected').data('tipo');

		if (tipo === 'meta') {
			cargarPlantillaMeta(plantillaId);
		} else {
			$('#parametros-meta').hide();
			$('#preview-mensaje-meta').hide();
			plantillaMetaActual = null;
			bodyTextValues = [];
		}
	}

	function cargarPlantillaMeta(plantillaId) {
		if (!plantillaId) return;

		var url = '{{ route("avisos.get-plantilla-meta", ":id") }}'.replace(':id', plantillaId);

		$.ajax({
			url: url,
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			method: 'get',
			success: function(data) {
				if (data.error) {
					console.error('Error al cargar plantilla:', data.error);
					$('#parametros-meta').hide();
					$('#preview-mensaje-meta').hide();
					return;
				}

				plantillaMetaActual = data;

				// Procesar body_text para obtener los parámetros
				if (data.body_text && Array.isArray(data.body_text) && data.body_text.length > 0) {
					bodyTextValues = Array.isArray(data.body_text[0]) ? data.body_text[0] : [];
				} else {
					bodyTextValues = [];
				}

				// Cargar body_dinamic si existe
				let bodyDinamicValues = [];
				if (data.body_dinamic) {
					try {
						let parsedData = data.body_dinamic;

						// Si es string, parsearlo
						if (typeof parsedData === 'string') {
							parsedData = JSON.parse(parsedData);
						}

						// Verificar que sea un array con la estructura correcta [["valor1", "valor2", ...]]
						if (Array.isArray(parsedData) && parsedData.length > 0) {
							// Tomar el primer elemento que es el array de parámetros
							if (Array.isArray(parsedData[0])) {
								bodyDinamicValues = parsedData[0];
							} else {
								// Si no tiene la estructura anidada, usar directamente
								bodyDinamicValues = parsedData;
							}

							// Convertir valores antiguos de { } a [ ] si existen
							bodyDinamicValues = bodyDinamicValues.map(function(val) {
								if (typeof val === 'string') {
									return val.replace(/\{/g, '[').replace(/\}/g, ']');
								}
								return val;
							});
						}
					} catch(e) {
						console.error('Error parsing body_dinamic:', e);
						console.error('Data recibida:', data.body_dinamic);
					}
				}

				// Generar inputs dinámicos
				generarInputsParametros(bodyDinamicValues);

				// Mostrar preview inicial
				actualizarPreview();
			},
			error: function(xhr) {
				console.error('Error al cargar plantilla Meta:', xhr);
				$('#parametros-meta').hide();
				$('#preview-mensaje-meta').hide();
			}
		});
	}

	function generarInputsParametros(valoresDinamicos = []) {
		const $container = $('#inputs-parametros');
		$container.empty();

		if (bodyTextValues.length === 0) {
			$('#parametros-meta').hide();
			return;
		}

		// Generar un input por cada parámetro
		bodyTextValues.forEach(function(valorEjemplo, index) {
			const numeroParam = index + 1;
			const valorDinamico = valoresDinamicos[index] || '';

			// Crear contenedor principal con mejor diseño
			const $paramGroup = $('<div class="parametro-meta-group mb-4 p-3 border rounded"></div>');

			// Label
			const $label = $('<label class="control-label d-block mb-2"><strong>Parámetro ' + numeroParam + '</strong> <small class="text-muted">(ejemplo: ' + valorEjemplo + ')</small></label>');

			// Contenedor del input con botones
			const $inputWrapper = $('<div class="input-group mb-2"></div>');

			// Input principal
			const $input = $('<input>', {
				type: 'text',
				class: 'form-control parametro-meta-input',
				name: 'body_dinamic_params[]',
				'data-param-index': index,
				placeholder: 'Escriba texto o use campos dinámicos',
				value: valorDinamico
			});

			// Botón dropdown para agregar campos
			const $dropdownBtn = $('<button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-plus"></i> Campos</button>');
			const $dropdownMenu = $('<ul class="dropdown-menu dropdown-menu-right"></ul>');

			// Agregar opciones al dropdown
			Object.keys(camposDinamicos).forEach(function(categoria) {
				const $categoriaHeader = $('<li><h6 class="dropdown-header">' + categoria.charAt(0).toUpperCase() + categoria.slice(1) + '</h6></li>');
				$dropdownMenu.append($categoriaHeader);

				Object.keys(camposDinamicos[categoria]).forEach(function(campo) {
					const campoKey = '[' + categoria + '.' + campo + ']';
					const $item = $('<li><a class="dropdown-item" href="#" data-campo="' + campoKey + '" data-param-index="' + index + '">' + camposDinamicos[categoria][campo] + ' <code>' + campoKey + '</code></a></li>');
					$dropdownMenu.append($item);
				});
			});

			// Agregar event listener para insertar campos
			$dropdownMenu.on('click', 'a', function(e) {
				e.preventDefault();
				const campo = $(this).data('campo');
				const paramIndex = $(this).data('param-index');
				const $targetInput = $('.parametro-meta-input[data-param-index="' + paramIndex + '"]');
				const cursorPos = $targetInput[0].selectionStart || $targetInput.val().length;
				const textBefore = $targetInput.val().substring(0, cursorPos);
				const textAfter = $targetInput.val().substring(cursorPos);
				$targetInput.val(textBefore + campo + textAfter);
				$targetInput.focus();
				$targetInput[0].setSelectionRange(cursorPos + campo.length, cursorPos + campo.length);
				actualizarPreview();
			});

			// Botón para limpiar
			const $clearBtn = $('<button class="btn btn-outline-danger" type="button" title="Limpiar"><i class="fa fa-times"></i></button>');
			$clearBtn.on('click', function() {
				$input.val('');
				actualizarPreview();
			});

			$inputWrapper.append($input);
			$inputWrapper.append($dropdownBtn);
			$inputWrapper.append($dropdownMenu);
			$inputWrapper.append($clearBtn);

			// Event listener para actualizar preview
			$input.on('input keyup', function() {
				actualizarPreview();
			});

			// Información adicional
			const $info = $('<small class="text-muted d-block mt-2"><i class="fa fa-info-circle"></i> Puede escribir texto libre y agregar campos dinámicos desde el menú</small>');

			$paramGroup.append($label);
			$paramGroup.append($inputWrapper);
			$paramGroup.append($info);
			$container.append($paramGroup);
		});

		$('#parametros-meta').show();
	}

	function actualizarPreview() {
		if (!plantillaMetaActual || !plantillaMetaActual.contenido) {
			$('#preview-mensaje-meta').hide();
			return;
		}

		let contenido = plantillaMetaActual.contenido;

		// Obtener valores de los inputs
		const valoresParametros = [];
		$('.parametro-meta-input').each(function() {
			let valor = $(this).val() || '';
			// Reemplazar placeholders con valores de ejemplo (solo para preview)
			valor = valor.replace(/\[contacto\.nombre\]/g, 'Juan');
			valor = valor.replace(/\[contacto\.apellido1\]/g, 'Pérez');
			valor = valor.replace(/\[contacto\.apellido2\]/g, 'González');
			valor = valor.replace(/\[factura\.fecha\]/g, '01/01/2024');
			valor = valor.replace(/\[factura\.vencimiento\]/g, '15/01/2024');
			valor = valor.replace(/\[factura\.total\]/g, '$100.000');
			valor = valor.replace(/\[factura\.porpagar\]/g, '$50.000');
			valor = valor.replace(/\[empresa\.nombre\]/g, 'Mi Empresa S.A.S.');
			valor = valor.replace(/\[empresa\.nit\]/g, '900123456-1');
			valoresParametros.push(valor);
		});

		// Reemplazar placeholders {{1}}, {{2}}, etc.
		valoresParametros.forEach(function(valor, index) {
			const numeroParam = index + 1;
			const placeholderText = '{{' + numeroParam + '}}';
			// Si el valor está vacío, mantener el placeholder original como {{1}}, {{2}}, etc.
			if (!valor || valor.trim() === '') {
				// No reemplazar, mantener el placeholder original
			} else {
				contenido = contenido.replace(new RegExp('\\{\\{' + numeroParam + '\\}\\}', 'g'), valor);
			}
		});

		// Mostrar preview con mejor diseño
		const $preview = $('#preview-mensaje-meta');
		$preview.html(`
			<hr class="my-4">
			<div class="alert alert-info">
				<strong><i class="fa fa-eye"></i> Vista Previa del Mensaje:</strong>
				<div class="mt-3 p-3 bg-white rounded border" style="white-space: pre-wrap; font-family: monospace;">
					${contenido.replace(/\n/g, '<br>')}
				</div>
			</div>
		`).show();
	}

	// Guardar body_dinamic antes de enviar
	$('#form-retencion').on('submit', function(e) {
		if (plantillaMetaActual && plantillaMetaActual.tipo == 3) {
			const bodyDinamicValues = [];
			$('.parametro-meta-input').each(function() {
				bodyDinamicValues.push($(this).val() || '');
			});

			// Crear input hidden con el JSON
			$('#body_dinamic_json').remove();
			$('<input>').attr({
				type: 'hidden',
				id: 'body_dinamic_json',
				name: 'body_dinamic',
				value: JSON.stringify([bodyDinamicValues])
			}).appendTo(this);
		}
	});

	// ============================================================
	// EVENT LISTENER PARA CAMBIO DE PLANTILLA
	// ============================================================
	$(document).on('change', '#plantilla_dinamico', function() {
		cargarPlantillaSeleccionada();
	});

	// ============================================================
	// CORREGIR DOBLE SCROLLBAR EN SELECTPICKER
	// ============================================================
	function corregirScrollbarSelects() {
		$('.bootstrap-select').each(function() {
			var $dropdown = $(this).find('.dropdown-menu');
			if ($dropdown.length && !$dropdown.closest('#parametros-meta').length) {
				// Remover overflow del contenedor externo - forzar con !important usando attr
				$dropdown.attr('style', function(i, style) {
					return (style || '') + ' overflow: visible !important; max-height: none !important; overflow-x: visible !important; overflow-y: visible !important;';
				});
			}
		});
	}

	$(document).ready(function() {
		// Esperar a que bootstrap-select se inicialice
		setTimeout(corregirScrollbarSelects, 200);
		// También corregir después de un tiempo adicional
		setTimeout(corregirScrollbarSelects, 500);
	});

	// También corregir cuando se abre un select
	$(document).on('shown.bs.select', '.bootstrap-select', function() {
		var $dropdown = $(this).find('.dropdown-menu');
		if ($dropdown.length && !$dropdown.closest('#parametros-meta').length) {
			// Forzar con attr para asegurar que se aplique
			$dropdown.attr('style', function(i, style) {
				return (style || '') + ' overflow: visible !important; max-height: none !important; overflow-x: visible !important; overflow-y: visible !important;';
			});
		}
	});

	// Corregir después de refresh de selectpicker
	$(document).on('refreshed.bs.select', '.bootstrap-select', function() {
		setTimeout(corregirScrollbarSelects, 50);
	});
</script>

<style>
	.parametro-meta-group {
		background-color: #f8f9fa;
		transition: all 0.3s ease;
	}

	.parametro-meta-group:hover {
		background-color: #e9ecef;
		box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	}

	.parametro-meta-input {
		font-family: 'Courier New', monospace;
	}

	.input-group .btn {
		border-left: none;
	}

	.input-group .form-control:focus {
		z-index: 3;
	}

	/* Solo aplicar overflow al dropdown de parámetros meta, no a los selectpicker */
	#parametros-meta .dropdown-menu {
		max-height: 300px;
		overflow-y: auto;
	}

	.dropdown-item code {
		background-color: #f8f9fa;
		padding: 2px 4px;
		border-radius: 3px;
		font-size: 0.85em;
		margin-left: 5px;
	}

	#parametros-meta {
		margin-top: 20px;
		margin-bottom: 20px;
	}

	#parametros-meta h5 {
		margin-bottom: 20px;
		color: #495057;
		font-weight: 600;
	}

	/* Eliminar doble scrollbar en selectpicker - el dropdown-menu externo NO debe tener overflow */
	.bootstrap-select .dropdown-menu {
		overflow: visible !important;
		max-height: none !important;
		overflow-x: visible !important;
		overflow-y: visible !important;
	}
</style>

<script type="text/javascript">

	// ============================================================
	// RESTO DEL CÓDIGO ORIGINAL (sin cambios)
	// ============================================================
	var ultimoVencimiento = null;

	window.addEventListener('load', function() {

		$('#vencimiento').on('change', function(){
			if($(this).val() == ultimoVencimiento){

			}else{
				ultimoVencimiento = $(this).val();
				window.location.href =  window.location.pathname + '?' + 'vencimiento=' + ultimoVencimiento;
			}
		});

        //Buscar barrio
		$('#barrio').on('keyup',function(e) {
        	if(e.which > 32 || e.which == 8) {
        		if($('#barrio').val().length > 3){
        		if (window.location.pathname.split("/")[1] === "software") {
        				var url = '/software/getContractsBarrio/'+$('#barrio').val();
        			}else{
        				var url = '/getContractsBarrio/'+$('#barrio').val();
        			}

        			cargando(true);

        			$.ajax({
        				url: url,
        				headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        				method: 'get',
        				success: function (data) {
        					console.log(data);
        					cargando(false);

        					var $select = $('#contrato_sms');
        					$select.empty();
        					$.each(data.data,function(key, value){
        						var apellidos = '';
        						if(value.apellido1){
        							apellidos += ' '+value.apellido1;
        						}
        						if(value.apellido2){
        							apellidos += ' '+value.apellido2;
        						}
        						$select.append('<option value='+value.id+' class="'+value.state+'">'+value.nombre+' '+apellidos+' - '+value.nit+'</option>');
        					});
        					$select.selectpicker('refresh');
							refreshClient();
        				},
        				error: function(data){
        					cargando(false);
        				}
        			});
        		}
        		return false;
        	}
        });

        //Buscar vereda
        $('#vereda').on('keyup',function(e) {
        	if(e.which > 32 || e.which == 8) {
        		if($('#vereda').val().length > 3){
        			if (window.location.pathname.split("/")[1] === "software") {
        				var url = '/software/getContractsVereda/'+$('#vereda').val();
        			}else{
        				var url = '/getContractsVereda/'+$('#vereda').val();
        			}

        			cargando(true);

        			$.ajax({
                    url: url,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    method: 'get',
                    success: function (data) {
                        console.log(data);
                        cargando(false);

                        var $select = $('#contrato_sms');
                        $select.empty();
                        $.each(data.data, function (key, value) {
                            var apellidos = '';
                            if (value.apellido1) {
                                apellidos += ' ' + value.apellido1;
                            }
                            if (value.apellido2) {
                                apellidos += ' ' + value.apellido2;
                            }

                            var grupoCorte = value.grupo_corte && value.grupo_corte.id ? 'grupo-' + value.grupo_corte.id : 'grupo-no';
                            var servidor = value.server_configuration_id && value.server_configuration_id ? 'servidor-' + value.server_configuration_id : 'servidor-no';
                            var factura = value.factura_id != null ? 'factura-si' : 'factura-no';
                            var vereda = value.vereda != null ? 'vereda-' + value.vereda : 'vereda-no';

                            var clasesExtra = value.state + ' ' + grupoCorte + ' ' + servidor + ' ' + factura + ' ' + vereda;

                            $select.append('<option value="' + value.id + '" class="' + clasesExtra + '">' + value.nombre + apellidos + ' - ' + value.nit + '</option>');
                        });
                        $select.selectpicker('refresh');
                        refreshClient();
                    },
                    error: function (data) {
                        cargando(false);
                    }
                });
        		}
        		return false;
        	}
        });


    });

    function chequeo(){
        if($("#radio_1").is(":selected")){
            $(".enabled").attr('selected','selected');
            $(".disabled").removeAttr("selected");

			refreshClient('enabled',1);
        }else if($("#radio_2").is(":selected")){
            $(".disabled").attr('selected','selected');
            $(".enabled").removeAttr("selected");

			refreshClient('disabled',1);
        }else if($("#radio_3").is(":selected")){

        }
        $("#contrato").selectpicker('refresh');
    }

    function alert_swal(){
    	Swal.fire({
    		type: 'info',
    		title: 'ENVIANDO NOTIFICACIONES',
    		text: 'Este proceso puede demorar varios minutos',
    		showConfirmButton: false,
    	})
    }

	function refreshClient(estadoCliente = null, disabledEstado = null){

		let grupoCorte = $('#corte').val();
		let servidor = $('#servidor').val();
		let factAbierta = $('#isAbierta').is(":checked");
        let tipoSaldo = $('#opciones_saldo').val();
        let valorSaldo = parseFloat($('#valor_saldo').val());

		if(estadoCliente){

			if(grupoCorte && servidor){
				options = $(`.servidor-${servidor}.grupo-${grupoCorte}.${estadoCliente}`);
			}else{
				if(servidor){
					options = $(`.servidor-${servidor}.${estadoCliente}`);
				}
				if(grupoCorte){
					options = $(`.grupo-${servidor}.${estadoCliente}`);
				}
			}

			if(factAbierta && grupoCorte && servidor){
			options=$(`.servidor-${servidor}.grupo-${grupoCorte}.${estadoCliente}.factura-si`);
			}else if(factAbierta && grupoCorte){
				options=$(`.grupo-${grupoCorte}.${estadoCliente}.factura-si`);
			}else if(factAbierta && servidor){
				options=$(`.servidor-${servidor}.${estadoCliente}.factura-si`);
			}else if(factAbierta){
				options=`${estadoCliente}.factura-si`;
			}

		}else{

			if(grupoCorte && servidor){
				options = $(`.servidor-${servidor}.grupo-${grupoCorte}`);
			}else{
				if(servidor){
					options = $(`#contrato_sms option[class*="servidor-${servidor}"]`);
				}
				if(grupoCorte){
					 options = $(`#contrato_sms option[class*="grupo-${grupoCorte}"]`);
				}
			}

			if(factAbierta && grupoCorte && servidor){
			options=$(`.servidor-${servidor}.grupo-${grupoCorte}.factura-si`);
			}else if(factAbierta && grupoCorte){
				options=$(`.grupo-${grupoCorte}.factura-si`);
			}else if(factAbierta && servidor){
				options=$(`.servidor-${servidor}.factura-si`);
			}else if(factAbierta){
				options=`.factura-si`;
			}
		}

        if (tipoSaldo && !isNaN(valorSaldo)) {
            options = options.filter(function() {
                let saldo = parseFloat($(this).data('saldo'));
                saldo = Math.round(saldo);
                switch (tipoSaldo) {
                    case 'mayor_a':
                        return saldo > valorSaldo;
                    case 'mayor_igual':
                        return saldo >= valorSaldo;
                    case 'igual_a':
                        return saldo === valorSaldo;
                    case 'menor_a':
                        return saldo < valorSaldo;
                    case 'menor_igual':
                        return saldo <= valorSaldo;
                    default:
                        return true;
                }
            });
        }

        if((grupoCorte || servidor) && disabledEstado == null ){
            $("#options option:selected").prop("selected", false);
            $("#options").selectpicker('refresh');
        }

		$("#contrato_sms option:selected").prop("selected", false);
		$("#contrato_sms option:selected").removeAttr("selected");

		options.attr('selected', true);
		options.prop('selected', true);

		$('#contrato_sms').selectpicker('refresh');

	}

</script>
@endsection
