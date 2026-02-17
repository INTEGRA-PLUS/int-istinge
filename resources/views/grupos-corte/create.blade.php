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
	<form method="POST" action="{{ route('grupos-corte.store') }}" style="padding: 2% 3%;" role="form" class="forms-sample" novalidate id="form-banco">
	    @csrf
	    <div class="row">
	        <div class="col-md-3 form-group">
	            <label class="control-label">Nombre <span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="nombre" name="nombre"  required="" value="{{old('nombre')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('nombre') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Fecha de Factura <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="fecha_factura" id="fecha_factura" title="Seleccione" data-live-search="true" data-size="5">
	            	<option {{old('fecha_factura')==0?'selected':''}} value="0">No Aplica</option>
	            	@for ($i = 1; $i < 31; $i++)
	            	    <option {{old('fecha_factura')==$i?'selected':''}} value="{{$i}}">{{$i}}</option>
	            	@endfor
            	</select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('fecha_factura') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Fecha de Pago <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="fecha_pago" id="fecha_pago" title="Seleccione" data-live-search="true" data-size="5">
	            	<option {{old('fecha_pago')==0?'selected':''}} value="0">No Aplica</option>
	            	@for ($i = 1; $i < 31; $i++)
	            	    <option {{old('fecha_pago')==$i?'selected':''}} value="{{$i}}">{{$i}}</option>
	            	@endfor
            	</select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('fecha_pago') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Fecha de Corte <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="fecha_corte" id="fecha_corte" title="Seleccione" data-live-search="true" data-size="5">
	            	<option {{old('fecha_corte')==0?'selected':''}} value="0">No Aplica</option>
	            	@for ($i = 1; $i < 31; $i++)
	            	    <option {{old('fecha_corte')==$i?'selected':''}} value="{{$i}}">{{$i}}</option>
	            	@endfor
            	</select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('fecha_corte') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Fecha de Suspensión <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="fecha_suspension" id="fecha_suspension" title="Seleccione" data-live-search="true" data-size="5">
	            	<option {{old('fecha_suspension')==0?'selected':''}} value="0">No Aplica</option>
	            	@for ($i = 1; $i < 31; $i++)
	            	    <option {{old('fecha_suspension')==$i?'selected':''}} value="{{$i}}">{{$i}}</option>
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
	                        <input type="checkbox" name="mes_siguiente" id="mes_siguiente" value="1">
	                        <span class="slider round"></span>
	                    </label>
	                    <span class="ml-2" id="mes_siguiente_label">No</span>
	                </div>
	            </div>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Hora de Suspensión desde:
                    <span class="text-danger">*</span>
                    <a><i data-tippy-content="Hora desde la que comienza la suspension en el sistema. (Se hara en intervalos de 15 min por lotes de clientes)" class="icono far fa-question-circle"></i></a>
                </label>
	            <input type="text" class="timepicker form-control" id="hora_suspension" name="hora_suspension"  required="" value="{{old('hora_suspension', '00:00')}}">
	            <span class="help-block error">
	                <strong>{{ $errors->first('hora_suspension') }}</strong>
	            </span>
	        </div>
            <div class="col-md-3 form-group">
	            <label class="control-label">Hora creción de factura desde:
                    <span class="text-danger">*</span>
                    <a><i data-tippy-content="Hora desde la que comienza la creación de facturas en el sistema. (Se hará en intervalos de 15 min por lotes de clientes)" class="icono far fa-question-circle"></i></a>
                </label>
	            <input type="text" class="timepicker-2 form-control" id="hora_creacion_factura" name="hora_creacion_factura"  required="" value="{{old('hora_crecion_factura', '00:00')}}">
	            <span class="help-block error">
	                <strong>{{ $errors->first('hora_creacion_factura') }}</strong>
	            </span>
	        </div>

	        <div class="col-md-3 form-group">
	            <label class="control-label">Estado <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="status" id="status" title="Seleccione" required="">
	                <option value="1" selected>Habilitado</option>
	                <option value="0">Deshabilitado</option>
	            </select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('status') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-3 form-group">
	            <label class="control-label">Periodo de Facturación <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="periodo_facturacion" id="periodo_facturacion" title="Seleccione" required="">
	                <option value="1" {{old('periodo_facturacion')==1?'selected':''}}>Mes anticipado</option>
	                <option value="2" {{old('periodo_facturacion')==2?'selected':''}}>Mes vencido</option>
	                <option value="3" {{old('periodo_facturacion')==3?'selected':''}}>Mes actual</option>
	            </select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('periodo_facturacion') }}</strong>
	            </span>
	        </div>

            {{-- <div class="col-md-3 form-group">
	            <label class="control-label">Dias Prorroga suspensión TV <span class="text-danger">*</span></label>
                <a><i data-tippy-content="Si agregas un dia mayor a 0 se tomará en cuenta para darle un tiempo de espera con la ultima factura vencida para suspender la televisión." class="icono far fa-question-circle"></i></a>
                <input type="text" class="form-control"  id="prorroga_tv" name="prorroga_tv" value="{{old('prorroga_tv')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('status') }}</strong>
	            </span>
	        </div> --}}
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
                        $('#mes_siguiente').prop('checked', false);
                    }
                } else {
                    $('#alertaMesSiguienteContainer').slideUp();
                    $('#mes_siguiente').prop('checked', false);
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
            updateMesSiguiente();

            // Verificar al cargar la página
            verificarDiferenciaFechas();
        });
    </script>
@endsection
