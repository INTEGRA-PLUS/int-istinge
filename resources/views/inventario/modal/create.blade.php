<!-- Modal para crear producto mejorado -->
<div class="modal fade" id="modalproduct" tabindex="-1" role="dialog" aria-labelledby="modalProductLabel" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalProductLabel">Nuevo Producto</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulario del modal -->
                <form method="POST" action="{{ route('inventario.storeback') }}" style="padding: 2% 3%;" role="form" class="forms-sample" novalidate id="form" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    
                    <!-- Campo oculto para la fila -->
                    <input type="hidden" id="trFila" name="trFila" value="1">
                    
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label class="control-label">Nombre del Producto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="producto" id="producto" required="" maxlength="200" value="{{old('producto')}}">
                            <span class="help-block error">
                                <strong>{{ $errors->first('producto') }}</strong>
                            </span>
                        </div>
                        <div class="form-group col-md-4 modal-contact">
                            <label class="control-label">Referencia <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ref" id="ref" required="" maxlength="200" value="{{old('ref')}}">
                            <span class="help-block error">
                                <strong>{{ $errors->first('ref') }}</strong>
                            </span>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label">Categoria <span class="text-danger">*</span><a><i data-tippy-content="Selecciona la categoría en la que se registrarán los valores por venta del ítem" class="icono far fa-question-circle"></i></a></label>
                            <select class="form-control selectpicker" name="categoria" id="categoria" required="" title="Seleccione" data-live-search="true" data-size="5">
                                @foreach($categorias as $categoria)
                                    @if($categoria->estatus==1)
                                        <option {{old('categoria')==$categoria->id?'selected':( Auth::user()->empresa()->categoria_default==$categoria->id?'selected':'')}} {{$categoria->nombre == 'Activos' ? 'selected' : ''}} value="{{$categoria->id}}">{{$categoria->nombre}} - {{$categoria->codigo}}</option>
                                    @endif
                                    @foreach($categoria->hijos(true) as $categoria1)
                                        @if($categoria1->estatus==1)
                                            <option {{old('categoria')==$categoria1->id?'selected':( Auth::user()->empresa()->categoria_default==$categoria1->id?'selected':'')}} value="{{$categoria1->id}}">{{$categoria1->nombre}} - {{$categoria1->codigo}}</option>
                                        @endif
                                        @foreach($categoria1->hijos(true) as $categoria2)
                                            @if($categoria2->estatus==1)
                                                <option {{old('categoria')==$categoria2->id?'selected':( Auth::user()->empresa()->categoria_default==$categoria2->id?'selected':'')}} value="{{$categoria2->id}}">{{$categoria2->nombre}} - {{$categoria2->codigo}}</option>
                                            @endif
                                            @foreach($categoria2->hijos(true) as $categoria3)
                                                @if($categoria3->estatus==1)
                                                    <option {{old('categoria')==$categoria3->id?'selected':( Auth::user()->empresa()->categoria_default=$categoria3->id?'selected':'')}} value="{{$categoria3->id}}">{{$categoria3->nombre}} - {{$categoria3->codigo}}</option>
                                                @endif
                                                @foreach($categoria3->hijos(true) as $categoria4)
                                                    @if($categoria4->estatus==1)
                                                        <option {{old('categoria')==$categoria4->id?'selected':( Auth::user()->empresa()->categoria_default=$categoria4->id?'selected':'')}} value="{{$categoria4->id}}">{{$categoria4->nombre}} - {{$categoria4->codigo}}</option>
                                                    @endif
                                                @endforeach
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                @endforeach
                            </select>
                            <span class="help-block error">
                                <strong>{{ $errors->first('categoria') }}</strong>
                            </span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="form-group col-md-5">
                            <label class="control-label">Impuesto <span class="text-danger">*</span></label>
                            <select class="form-control selectpicker" name="impuesto" id="impuesto" required="" title="Seleccione">
                                @foreach($impuestos as $impuesto)
                                    <option {{old('impuesto')==$impuesto->id?'selected':''}} value="{{$impuesto->id}}">{{$impuesto->nombre}} - {{$impuesto->porcentaje}} %</option>
                                @endforeach
                            </select>
                            <span class="help-block error">
                                <strong>{{ $errors->first('impuesto') }}</strong>
                            </span>
                        </div>
                        <div class="form-group col-md-7">
                            <div class="row">
                                <div class="col-md-6 monetario">
                                    <label class="control-label">Precio de Venta</label>
                                    <input type="number" class="form-control" id="precio" name="precio" maxlength="24" value="{{old('precio')}}" placeholder="{{Auth::user()->empresa()->moneda}}" min="0" step="0.01">
                                    <span class="help-block error">
                                        <strong>{{ $errors->first('precio') }}</strong>
                                    </span>
                                    <span class="small text-muted">Use el punto (.) para colocar decimales</span>
                                </div>
                                <div class="col-md-6" style="padding-top: 6%;padding-left: 0;">
                                    <button type="button" class="btn btn-link" style="padding-left: 0;" onclick="agregarlista_precios();" @if(json_encode($listas)=='[]') title="Usted no tiene lista de precios registrada" @endif>
                                        <i class="fas fa-plus"></i> Agregar otra lista de precio
                                    </button>
                                </div>
                            </div>
                            <div class="row" id="lista_precios_inventario">
                                <div class="col-md-12">
                                    <table id="table_lista_precios">
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="form-group col-md-8">
                            <label class="control-label" for="descripcion">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="descripcion" rows="4" maxlength="500" value="{{old('descripcion')}}"></textarea>
                            <div class="help-block error with-errors"></div>
                            <span class="help-block error">
                                <strong>{{ $errors->first('descripcion') }}</strong>
                            </span>
                        </div>
                    </div>

                    @if(Auth::user()->empresa()->carrito==1)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group row">
                                <label for="publico" class="col-md-3 col-form-label">¿Estara el producto público en la web? <a><i data-tippy-content="Si eliges 'si' automaticamente al presionar guardar el producto irá a tu tienda online" class="icono far fa-question-circle"></i></a></label>
                                <div class="col-md-2">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-radio">
                                                <label class="form-check-label">
                                                    <input type="radio" class="form-check-input" name="publico" id="publico1" value="1"> Si
                                                    <i class="input-helper"></i>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-radio">
                                                <label class="form-check-label">
                                                    <input type="radio" class="form-check-input" name="publico" id="publico" value="0" checked=""> No
                                                    <i class="input-helper"></i>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="col-sm-12">
                        <hr>
                    </div>

                    {{-- Campos extras dinámicos mejorados --}}
                    @php $search=array(); @endphp
                    @if($extras->count() > 0)
                        <div class="row">
                            @foreach($extras as $campo)
                            <div class="form-group col-md-4">
                                <label class="control-label">{{$campo->nombre}} @if($campo->tipo==1) <span class="text-danger">*</span> @endif</label>
                                <a><i data-tippy-content="Edita el nombre del campo para categorizar mejor tus productos segun el tipo de negocio que tengas <a target='_blank' href='https://gestordepartes.net/empresa/configuracion/personalizar_inventario'>aquí</a>" class="icono far fa-question-circle"></i></a>
                                <input type="text" class="form-control" name="ext_{{$campo->campo}}" id="{{$campo->campo}}-autocomplete" @if($campo->tipo==1) required="" @endif @if($campo->varchar) maxlength="{{$campo->varchar}}" @endif value="{{$campo->default}}">
                                <small class="text-muted">{{$campo->descripcion}}</small>
                                <span class="help-block error"></span>
                            </div>
                            @if($campo->autocompletar==1)
                                @php $search[]=$campo->campo; @endphp
                                <input type="hidden" id="search{{$campo->campo}}" value="{{json_encode($campo->records())}}">
                            @endif
                            @endforeach
                        </div>
                        @if ($search) <input type="hidden" id="camposextra" value="{{json_encode($search)}}"> @endif
                    @endif

                    <small class="text-muted">Los campos marcados con <span class="text-danger">*</span> son obligatorios</small>
                    
                    <input type="hidden" name="tipo_producto" value="2">
                    <input type="hidden" name="desde_factura" value="1">
                </form>

                <!-- Inputs ocultos necesarios -->
                <input type="hidden" id="json_precios" value="{{json_encode($listas)}}">
                <input type="hidden" id="json_bodegas" value="{{json_encode($bodegas)}}">
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" id="cancelar">
                    Cancelar
                </button>
                <button type="button" class="btn btn-success" id="guardar">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Loader mejorado para mostrar durante la carga --}}
<div class="loader" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                          background: rgba(0,0,0,0.5); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <div class="mt-2">Guardando producto...</div>
        </div>
    </div>
</div>

{{-- Variables JavaScript necesarias --}}
<script>
// Variables que necesita el script
var routeInventarioAll = "{{ route('inventario.all') ?? '' }}";
var urlBase = "{{ url('/') }}";
var empresaMoneda = "{{ Auth::user()->empresa()->moneda ?? '$' }}";
</script>

<script>
$(document).ready(function() {
    // Variables globales
    let filaActual = 1;
    
    // Inicializar selectores
    $('.buscar').select2();
    $('.selectpicker').selectpicker();
    
    // Event listener para abrir modal desde tabla
    $('#table-form').on('click', '.modalTr', function() {
        filaActual = $(this).attr('tr') || 1;
        $('#trFila').val(filaActual);
        $('.modal-title').text('Nuevo Producto');
        
        // Limpiar formulario del modal
        limpiarFormulario();
        
        // Mostrar modal
        $('#modalproduct').modal('show');
    });
    
    // Prevenir submit por defecto del form
    $("#form").on('submit', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Función para guardar producto desde modal
    $("#guardar").on('click', function() {
        // Validar campos obligatorios
        if (!validarCamposObligatorios()) {
            return;
        }
        
        // Mostrar loader
        $(".loader").show();
        
        // Deshabilitar botón para evitar doble click
        $(this).prop('disabled', true);
        
        // Preparar datos
        let formData = new FormData($('#form')[0]);
        
        // Realizar petición AJAX
        $.ajax({
            url: $('#form').attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000, // 30 segundos de timeout
            success: function(response) {
                $(".loader").hide();
                $("#guardar").prop('disabled', false);
                
                if (response.status === 'OK' || response.success) {
                    // Producto creado exitosamente
                    agregarProductoASelect(response);
                    mostrarMensajeExito('Producto agregado correctamente');
                    cerrarModal();
                    
                    // Disparar evento change y rellenar datos si existe la función
                    $("#item" + filaActual).trigger('change');
                    if (typeof rellenar === 'function') {
                        rellenar(filaActual, response.id || response.producto_id);
                    }
                } else {
                    mostrarMensajeError(response.mensaje || response.message || 'Error al crear el producto');
                }
            },
            error: function(xhr, status, error) {
                $(".loader").hide();
                $("#guardar").prop('disabled', false);
                
                let mensaje = 'Error de conexión. Intente nuevamente.';
                
                if (xhr.status === 422) {
                    // Errores de validación
                    mostrarErroresValidacion(xhr.responseJSON.errors);
                    return;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensaje = xhr.responseJSON.message;
                } else if (status === 'timeout') {
                    mensaje = 'Tiempo de espera agotado. Intente nuevamente.';
                }
                
                console.error('Error AJAX:', error, xhr);
                mostrarMensajeError(mensaje);
            }
        });
    });
    
    // Función para validar campos obligatorios mejorada
    function validarCamposObligatorios() {
        let esValido = true;
        let primerError = null;
        
        // Limpiar errores previos
        $('.help-block.error').empty();
        $('.form-control').removeClass('is-invalid');
        $('.selectpicker').removeClass('is-invalid');
        
        // Validar campos básicos
        const camposRequeridos = [
            { id: 'producto', nombre: 'Nombre del producto' },
            { id: 'ref', nombre: 'Referencia' },
            { id: 'categoria', nombre: 'Categoría' },
            { id: 'impuesto', nombre: 'Impuesto' }
        ];
        
        camposRequeridos.forEach(function(campo) {
            let elemento = $('#' + campo.id);
            let valor = elemento.val();
            
            if (!valor || (typeof valor === 'string' && valor.trim() === '')) {
                elemento.addClass('is-invalid');
                elemento.siblings('.help-block.error').html('<strong>El campo ' + campo.nombre + ' es obligatorio</strong>');
                esValido = false;
                if (!primerError) primerError = elemento;
            }
        });
        
        // Validar precio si tiene valor
        let precio = $('#precio').val();
        if (precio && (isNaN(precio) || precio < 0)) {
            $('#precio').addClass('is-invalid');
            $('#precio').siblings('.help-block.error').html('<strong>Ingrese un precio válido</strong>');
            esValido = false;
            if (!primerError) primerError = $('#precio');
        }
        
        // Validar campos extras requeridos
        @if($extras->count() > 0)
            @foreach($extras as $campo)
                @if($campo->tipo==1)
                    let campo{{$campo->campo}} = $('#{{$campo->campo}}-autocomplete').val();
                    if (!campo{{$campo->campo}} || campo{{$campo->campo}}.trim() === '') {
                        $('#{{$campo->campo}}-autocomplete').addClass('is-invalid');
                        $('#{{$campo->campo}}-autocomplete').siblings('.help-block.error').html('<strong>El campo {{$campo->nombre}} es obligatorio</strong>');
                        esValido = false;
                        if (!primerError) primerError = $('#{{$campo->campo}}-autocomplete');
                    }
                @endif
            @endforeach
        @endif
        
        // Enfocar el primer campo con error
        if (!esValido && primerError) {
            primerError.focus();
        }
        
        return esValido;
    }
    
    // Función para mostrar errores de validación del servidor
    function mostrarErroresValidacion(errores) {
        $.each(errores, function(campo, mensajes) {
            let elemento = $('#' + campo);
            if (elemento.length) {
                elemento.addClass('is-invalid');
                elemento.siblings('.help-block.error').html('<strong>' + mensajes[0] + '</strong>');
            }
        });
    }
    
    // Función para agregar producto al select de la fila mejorada
    function agregarProductoASelect(producto) {
        let selectItem = $('#item' + filaActual);
        
        if (selectItem.length === 0) {
            console.warn('No se encontró el select para la fila:', filaActual);
            return;
        }
        
        // Crear el texto de la opción
        let textoOpcion = producto.producto || producto.nombre || 'Producto';
        if (producto.ref || producto.referencia) {
            textoOpcion += ' - (' + (producto.ref || producto.referencia) + ')';
        }
        
        // Agregar nueva opción al select
        let nuevaOpcion = `<option value="${producto.id}" selected>${textoOpcion}</option>`;
        
        // Buscar el optgroup de Ítems y agregar la opción, o agregarlo directamente si no hay optgroup
        let optgroupItems = selectItem.find('optgroup[label="Ítems"]');
        if (optgroupItems.length > 0) {
            optgroupItems.append(nuevaOpcion);
        } else {
            selectItem.prepend(nuevaOpcion);
        }
        
        // Refrescar el select
        if (selectItem.hasClass('selectpicker')) {
            selectItem.selectpicker('refresh');
        } else if (selectItem.hasClass('select2')) {
            selectItem.trigger('change');
        }
        
        // Limpiar campos relacionados de la fila
        limpiarCamposFila(filaActual);
    }
    
    // Función para limpiar campos de la fila
    function limpiarCamposFila(fila) {
        ['ref', 'precio', 'desc', 'descripcion', 'cant', 'total'].forEach(function(campo) {
            $('#' + campo + fila).val('');
        });
    }
    
    // Función para mostrar mensaje de éxito mejorada
    function mostrarMensajeExito(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¡Éxito!',
                text: mensaje,
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
        } else if (typeof swal !== 'undefined') {
            swal({
                title: "¡Éxito!",
                text: mensaje,
                type: "success",
                timer: 3000
            });
        } else if (typeof toastr !== 'undefined') {
            toastr.success(mensaje);
        } else {
            alert(mensaje);
        }
    }
    
    // Función para mostrar mensaje de error mejorada
    function mostrarMensajeError(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: mensaje,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        } else if (typeof swal !== 'undefined') {
            swal({
                title: "Error",
                text: mensaje,
                type: "error"
            });
        } else if (typeof toastr !== 'undefined') {
            toastr.error(mensaje);
        } else {
            alert('Error: ' + mensaje);
        }
    }
    
    // Función para limpiar formulario mejorada
    function limpiarFormulario() {
        $('#form')[0].reset();
        $('.help-block.error').empty();
        $('.form-control').removeClass('is-invalid');
        $('.selectpicker').removeClass('is-invalid').selectpicker('refresh');
        
        // Limpiar tabla de listas de precios
        $('#table_lista_precios tbody').empty();
        
        // Resetear campos extras a sus valores por defecto
        @if($extras->count() > 0)
            @foreach($extras as $campo)
                @if($campo->default)
                    $('#{{$campo->campo}}-autocomplete').val('{{$campo->default}}');
                @endif
            @endforeach
        @endif
    }
    
    // Función para cerrar modal y limpiar
    function cerrarModal() {
        $('#modalproduct').modal('hide');
        limpiarFormulario();
        $('#trFila').val('0');
        filaActual = 1;
    }
    
    // Event listener para botón cancelar
    $('#cancelar').on('click', function() {
        cerrarModal();
    });
    
    // Limpiar al cerrar modal
    $('#modalproduct').on('hidden.bs.modal', function() {
        limpiarFormulario();
        $("#guardar").prop('disabled', false);
    });
    
    // Autocompletar campos extras si existen
    inicializarAutocomplete();
    
    // Mantener funcionalidad original del contacto
    $('#contacto').click(function() {
        var url = '/empresa/contactos/contactosModal';
        var _token = $('meta[name="csrf-token"]').attr('content');
        
        $("#modal-titlec").html($(this).attr('title'));
        
        $.post(url, {_token: _token}, function(resul) {
            $("#modal-bodyc").html(resul);
            $('.selectpicker').selectpicker();
        });
        $('#contactoModal').modal("show");
    });
    
    // Manejar tipos de producto
    $('input[type=radio][name=tipo_producto]').change(function() {
        if (this.value == 1) {
            $('#inventariable').show();
            $('#precio').removeAttr('required');
            $('#precio').attr('disabled', '');
            $('#precio_unid').attr('required', '');
            $('#unidad').attr('required', '');
            $('#nro_unid').attr('required', '');
        } else {
            $('#inventariable').hide();
            $("#precio_unid").val('');
            $("#nro_unid").val('');
            $('#unidad').val('').trigger('change');
            $('#precio').attr('required');
            $('#precio').removeAttr('disabled');
            $('#unidad').removeAttr('required');
            $('#nro_unid').removeAttr('required');
        }
    });
    
    // Botón mostrar imagen
    $('#button_show_div_img').on('click', function() {
        if ($("#div_imagen").is(":visible")) {
            hidediv('div_imagen');
        } else {
            showdiv('div_imagen');
        }
    });
    
    // Validación en tiempo real para campos requeridos
    $('input[required], select[required]').on('blur', function() {
        let elemento = $(this);
        let valor = elemento.val();
        
        if (!valor || (typeof valor === 'string' && valor.trim() === '')) {
            elemento.addClass('is-invalid');
        } else {
            elemento.removeClass('is-invalid');
            elemento.siblings('.help-block.error').empty();
        }
    });
});

// Función para inicializar autocompletado de campos extras mejorada
function inicializarAutocomplete() {
    if ($('#camposextra').length > 0) {
        try {
            let camposExtra = JSON.parse($('#camposextra').val());
            
            camposExtra.forEach(function(campo) {
                let searchElement = $('#search' + campo);
                if (searchElement.length > 0) {
                    let searchData = JSON.parse(searchElement.val());
                    
                    $('#' + campo + '-autocomplete').autocomplete({
                        source: searchData,
                        minLength: 1,
                        select: function(event, ui) {
                            $(this).val(ui.item.value);
                            $(this).removeClass('is-invalid');
                        }
                    });
                }
            });
        } catch (e) {
            console.error('Error inicializando autocompletado:', e);
        }
    }
}

// Función helper para obtener datos del producto creado
function obtenerDatosProducto() {
    return {
        producto: $('#producto').val(),
        ref: $('#ref').val(),
        categoria: $('#categoria').val(),
        impuesto: $('#impuesto').val(),
        precio: $('#precio').val() || 0,
        descripcion: $('#descripcion').val(),
        publico: $('input[name="publico"]:checked').val() || 0,
        tipo_producto: $('input[name="tipo_producto"]').val() || 2
    };
}

// Función para agregar lista de precios (si no existe)
if (typeof agregarlista_precios !== 'function') {
    function agregarlista_precios() {
        console.log('Función agregarlista_precios no implementada');
        // Aquí se implementaría la lógica para agregar listas de precios adicionales
    }
}
</script>