@extends('layouts.app')

@section('style')
    <style>
        /* Toggle Switch Styles */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: #28a745;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .slider.round {
            border-radius: 24px;
        }

        .slider.round:before {
            border-radius: 50%;
        }
    </style>
@endsection

@section('content')
<div class="alert alert-warning alert-dismissible fade show" role="alert">
	<a>Recuerda que si haces un cambio en la <strong>fecha de suspensión del grupo de corte,</strong> todas las facturas que tengan su
		<strong>fecha de vencimiento</strong> en el mismo mes que se realiza el cambio también cambiarán su fecha de vencimiento</a>
	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
		<span aria-hidden="true">&times;</span>
	</button>
</div>
	<form method="POST" action="{{ route('grupos-corte.update', $grupo->id) }}" style="padding: 2% 3%;" role="form" class="forms-sample" novalidate id="form-banco" >
	    @csrf
	    <input name="_method" type="hidden" value="PATCH">
	    <div class="row">
	        <div class="col-md-3 form-group">
	            <label class="control-label">Nombre <span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="nombre" name="nombre"  required="" value="{{$grupo->nombre}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('nombre') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Fecha de Factura <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="fecha_factura" id="fecha_factura" title="Seleccione" data-live-search="true" data-size="5">
	            	<option {{$grupo->fecha_factura==0?'selected':''}} value="0">No Aplica</option>
	            	@for ($i = 1; $i < 31; $i++)
	            	    <option {{$grupo->fecha_factura==$i?'selected':''}} value="{{$i}}">{{$i}}</option>
	            	@endfor
            	</select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('fecha_factura') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Fecha de Pago <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="fecha_pago" id="fecha_pago" title="Seleccione" data-live-search="true" data-size="5">
	            	<option {{$grupo->fecha_pago==0?'selected':''}} value="0">No Aplica</option>
	            	@for ($i = 1; $i < 31; $i++)
	            	    <option {{$grupo->fecha_pago==$i?'selected':''}} value="{{$i}}">{{$i}}</option>
	            	@endfor
            	</select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('fecha_pago') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Fecha Corte <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="fecha_corte" id="fecha_corte" title="Seleccione" data-live-search="true" data-size="5">
	            	<option {{$grupo->fecha_corte==0?'selected':''}} value="0">No Aplica</option>
	            	@for ($i = 1; $i < 31; $i++)
	            	    <option {{$grupo->fecha_corte==$i?'selected':''}} value="{{$i}}">{{$i}}</option>
	            	@endfor
            	</select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('fecha_corte') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Fecha Suspensión <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="fecha_suspension" id="fecha_suspension" title="Seleccione" data-live-search="true" data-size="5">
	            	<option {{$grupo->fecha_suspension==0?'selected':''}} value="0">No Aplica</option>
	            	@for ($i = 1; $i < 31; $i++)
	            	    <option {{$grupo->fecha_suspension==$i?'selected':''}} value="{{$i}}">{{$i}}</option>
	            	@endfor
            	</select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('fecha_suspension') }}</strong>
	            </span>
	        </div>
	        <!-- Alerta Mes Siguiente -->
	        <div class="col-md-12" id="alertaMesSiguienteContainer" style="display: none;">
	            <div class="alert alert-warning d-flex align-items-center mt-2 mb-2" role="alert">
	                <i class="fas fa-exclamation-triangle fa-2x mr-3 text-warning"></i>
	                <div>
	                    <strong>¡Atención!</strong> La fecha de suspensión está muy cerca de la fecha de factura.
	                    <br><span class="text-dark">Esto significa que la factura que se genere el día <strong id="diaFacturaTexto">X</strong> se suspenderá el día <strong id="diaSuspensionTexto">Y</strong> <strong>del mismo mes</strong>.</span>
	                </div>
	            </div>
	            <div class="form-group col-md-6">
	                <label class="control-label">¿Suspender en el mes siguiente en lugar del mismo mes?</label>
	                <div class="d-flex align-items-center">
	                    <label class="switch mb-0">
	                        <input type="hidden" name="mes_siguiente" value="0">
	                        <input type="checkbox" name="mes_siguiente" id="mes_siguiente" value="1" {{ $grupo->mes_siguiente == 1 ? 'checked' : '' }}>
	                        <span class="slider round"></span>
	                    </label>
	                    <span class="ml-2" id="mes_siguiente_label">{{ $grupo->mes_siguiente == 1 ? 'Si' : 'No' }}</span>
	                </div>
	            </div>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Hora de Suspensión desde:
                    <span class="text-danger">*</span>
                    <a><i data-tippy-content="Hora desde la que comienza la suspensión en el sistema. (Se hará en intervalos de 15 min por lotes de clientes)" class="icono far fa-question-circle"></i></a>
                </label>
	            <input type="text" class="timepicker form-control" id="hora_suspension" name="hora_suspension"  required="" value="{{$grupo->hora_suspension}}">
	            <span class="help-block error">
	                <strong>{{ $errors->first('hora_suspension') }}</strong>
	            </span>
	        </div>
            <div class="col-md-3 form-group">
	            <label class="control-label">Hora creción de factura desde:
                    <span class="text-danger">*</span>
                    <a><i data-tippy-content="Hora desde la que comienza la creación de facturas en el sistema. (Se hará en intervalos de 15 min por lotes de clientes)" class="icono far fa-question-circle"></i></a>
                </label>
	            <input type="text" class="timepicker-2 form-control" id="hora_creacion_factura" name="hora_creacion_factura"  required="" value="{{$grupo->hora_creacion_factura}}">
	            <span class="help-block error">
	                <strong>{{ $errors->first('hora_creacion_factura') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Estado <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="status" id="status" title="Seleccione" required="">
	                <option value="1" {{ ($grupo->status == 1) ? 'selected' : '' }}>Habilitado</option>
	                <option value="0" {{ ($grupo->status == 0) ? 'selected' : '' }}>Deshabilitado</option>
	            </select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('status') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Periodo de Facturación <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="periodo_facturacion" id="periodo_facturacion" title="Seleccione" required="">
	                <option value="1" {{ ($grupo->periodo_facturacion == 1) ? 'selected' : '' }}>Mes anticipado</option>
	                <option value="2" {{ ($grupo->periodo_facturacion == 2) ? 'selected' : '' }}>Mes vencido</option>
	                <option value="3" {{ ($grupo->periodo_facturacion == 3) ? 'selected' : '' }}>Mes actual</option>
	            </select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('periodo_facturacion') }}</strong>
	            </span>
	        </div>

            {{-- <div class="col-md-3 form-group">
	            <label class="control-label">Dias Prorroga suspensión TV <span class="text-danger">*</span></label>
                <a><i data-tippy-content="Si agregas un dia mayor a 0 se tomará en cuenta para darle un tiempo de espera con la ultima factura vencida para suspender la televisión." class="icono far fa-question-circle"></i></a>
                <input type="text" class="form-control"  id="prorroga_tv" name="prorroga_tv" value="{{$grupo->prorroga_tv}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('status') }}</strong>
	            </span>
	        </div> --}}
			<div class="col-md-3 form-group" id="swSuspension">
				<label class="control-label">Suspender al tener <span class="text-danger">*</span></label>
				<select class="form-control selectpicker" name="nro_factura_vencida" id="nro_factura_vencida" title="Seleccione" required="">
					<option value="0" {{ $grupo->nro_factura_vencida == 0 ? 'selected':'' }}>No aplica</option>
					<option value="1" {{ $grupo->nro_factura_vencida == 1 ? 'selected':'' }}>1 Factura Vencida</option>
					<option value="2" {{ $grupo->nro_factura_vencida == 2 ? 'selected':'' }}>2 Facturas Vencidas</option>
					<option value="3" {{ $grupo->nro_factura_vencida == 3 ? 'selected':'' }}>3 Facturas Vencidas</option>
					<option value="4" {{ $grupo->nro_factura_vencida == 4 ? 'selected':'' }}>4 Facturas Vencidas</option>
					<option value="5" {{ $grupo->nro_factura_vencida == 5 ? 'selected':'' }}>5 Facturas Vencidas</option>
					<option value="6" {{ $grupo->nro_factura_vencida == 6 ? 'selected':'' }}>6 Facturas Vencidas</option>
					<option value="7" {{ $grupo->nro_factura_vencida == 7 ? 'selected':'' }}>7 Facturas Vencidas</option>
					<option value="8" {{ $grupo->nro_factura_vencida == 8 ? 'selected':'' }}>8 Facturas Vencidas</option>
				</select>
				<span class="help-block error">
					<strong>{{ $errors->first('nro_factura_vencida') }}</strong>
				</span>
			</div>
	    </div>
	    <small>Los campos marcados con <span class="text-danger">*</span> son obligatorios</small>
	    <hr>
	    <div class="row" >
	        <div class="col-sm-12" style="text-align: right;  padding-top: 1%;">
	            <a href="{{route('grupos-corte.index')}}" class="btn btn-outline-secondary">Cancelar</a>
	            <button type="submit" id="submitcheck" onclick="submitLimit(this.id)" class="btn btn-success">Guardar</button>
	        </div>
	    </div>
	</form>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.timepicker').timepicker({
                locale: 'es-es',
                uiLibrary: 'bootstrap4',
            });

            $('.timepicker-2').timepicker({
                locale: 'es-es',
                uiLibrary: 'bootstrap4',
            });

            // Función para verificar diferencia de fechas
            function verificarDiferenciaFechas() {
                var fechaFactura = parseInt($('#fecha_factura').val()) || 0;
                var fechaSuspension = parseInt($('#fecha_suspension').val()) || 0;
                
                if (fechaFactura > 0 && fechaSuspension > 0) {
                    // Calcular diferencia considerando que puede ser mes siguiente
                    var diferencia;
                    if (fechaSuspension >= fechaFactura) {
                        diferencia = fechaSuspension - fechaFactura;
                    } else {
                        // Si suspensión es menor, podría ser mes siguiente (ej: factura 28, suspensión 2)
                        diferencia = (30 - fechaFactura) + fechaSuspension;
                    }
                    
                    // Mostrar alerta si diferencia es <= 5 días
                    if (diferencia <= 5 && diferencia >= 0) {
                        $('#diaFacturaTexto').text(fechaFactura);
                        $('#diaSuspensionTexto').text(fechaSuspension);
                        $('#alertaMesSiguienteContainer').slideDown();
                    } else {
                        $('#alertaMesSiguienteContainer').slideUp();
                        // NO descheckar automáticamente en edición para preservar valor guardado
                    }
                } else {
                    $('#alertaMesSiguienteContainer').slideUp();
                }
            }

            // Escuchar cambios en fecha_factura y fecha_suspension
            $('#fecha_factura, #fecha_suspension').on('changed.bs.select change', function() {
                verificarDiferenciaFechas();
            });

            // Handler para el switch mes_siguiente
            function updateMesSiguiente() {
                var isChecked = $('#mes_siguiente').is(':checked');
                $('#mes_siguiente_label').text(isChecked ? 'Si' : 'No');
            }
            $('#mes_siguiente').change(updateMesSiguiente);

            // Mostrar u ocultar el select según fecha_suspension
            $("#fecha_suspension").change(function(){
                let fechaSuspension = $("#fecha_suspension").val();
                if(fechaSuspension == 0){
                    $("#swSuspension").css('display','none');
                    $("#nro_factura_vencida").val(0);
                } else {
                    $("#swSuspension").css('display','block');
                    $("#nro_factura_vencida").trigger('change');
                }
            });

            // Ejecutar al cargar la página para que quede consistente
            $("#fecha_suspension").trigger('change');
            verificarDiferenciaFechas();
        });
    </script>
@endsection

