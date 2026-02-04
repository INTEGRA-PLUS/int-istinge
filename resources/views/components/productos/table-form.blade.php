<input type="hidden" id="is-venta" value="{{ $venta ?? false }}">
<style>
    .columna-bodega {
        display: table-cell !important;
    }
    @media (max-width: 768px) {
        .columna-bodega {
            display: table-cell !important;
        }
    }
</style>
<div style="overflow-x: auto; width: 100%;">
<table class="table table-striped table-sm" id="table-form" width="100%" style="min-width: 1200px;">
    <thead class="thead-dark">
    <th width="18%">Categoría/Ítem</th>
    <th width="8%">Referencia</th>
    <th width="8%" class="columna-marca">Marca</th>
    <th width="7%">Precio</th>
    <th width="5%">Desc %</th>
    <th width="10%">Impuesto</th>
    <th width="11%">Descripción</th>
    <th width="6%">Cantidad</th>
    <th width="8%" class="columna-bodega" style="display: table-cell !important;">Bodega</th>
    <th width="8%">Total</th>
    <th width="2%"></th>
    </thead>
    <tbody>

        @if(isset($items) && isset($impuestos))
            @if($items)
                @foreach($items as $item)
                    <tr id="{{ $loop->iteration }}">
                        <td class="no-padding">
                            <div class="resp-item">
                                <input
                                    type="text"
                                    name="productos[]"
                                    class="form-control"
                                    id="item{{ $loop->iteration }}"
                                    autocomplete="off"
                                    placeholder="Ingrese el item"
                                    value="{{
                                        $item->tipo_item == 1
                                            ? ($item->nombreProducto ?? $item->inventario->producto ?? $item->nombreCat)
                                            : ($item->producto())
                                    }}"
                                >
                                <input
                                    value="{{ $item->producto }}"
                                    type="hidden"
                                    name="item[]"
                                    id="items-{{ $loop->iteration }}"
                                >

                                <input
                                    value="{{ $item->tipo_item === 1 ? "inv" : "cat" }}"
                                    type="hidden"
                                    name="type[]"
                                    id="type{{ $loop->iteration }}"
                                >
                                <p class="text-left nomargin">
                                    <a
                                        href=""
                                        data-toggle="modal"
                                        data-target="#modalproduct"
                                        class="modalTr"
                                        tr="1"
                                    >
                                        <i class="fas fa-plus"></i> Nuevo Producto
                                    </a>
                                </p>
                            </div>

                            <div class="row content-lista" id="lista-{{ $loop->iteration }}">
                                <div class="col-md-12">
                                    <div class="list-group">
                                        <ul class="inner" aria-expanded="true"
                                            style="max-height: 324px; overflow-y: auto;  min-height: 0px; list-style: none"
                                            id="content-{{ $loop->iteration }}">
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <input
                                value="{{ $item->ref }}"
                                type="text"
                                class="form-control"
                                name="ref[]"
                                id="ref{{ $loop->iteration }}"
                            >
                        </td>
                        <td class="columna-marca">
                        <input
                        type="text"
                        class="form-control form-control-sm"
                        id="marca-estatica{{ $loop->iteration }}"
                        name="marcas_estaticas[]"
                        placeholder="Marca"
                        value="{{ $item->marca }}">
                        </td>
                        <td class="monetario">
                            <div class="resp-precio">
                                <input
                                    type="number"
                                    class="form-control form-control-sm"
                                    id="precio{{$loop->iteration}}"
                                    name="precio[]"
                                    placeholder="Precio Unitario"
                                    onkeyup="total({{$loop->iteration}})"
                                    required
                                    value="{{ App\Funcion::precision($item->precio)}}"
                                >
                            </div>
                        </td>
                        <td>
                            <input
                                type="text"
                                class="form-control form-control-sm"
                                id="desc{{$loop->iteration}}"
                                name="desc[]"
                                placeholder="%"
                                onkeyup="total({{$loop->iteration}})"
                                value="{{App\Funcion::precision($item->desc)}}"
                                onkeypress="return event.charCode >= 46 && event.charCode <=57"
                                min="0"
                                max="100"
                            >
                        </td>
                        <td>
                            <select
                                class="form-control form-control-sm selectpicker"
                                name="impuesto{{$loop->iteration}}[]"
                                id="impuesto{{$loop->iteration}}"
                                title="Impuesto"
                                onchange="impuestoFacturaDeVenta(this.id); totalall();"
                                required
                                multiple
                            >
                                @foreach($impuestos as $impuesto)
                                    <option
                                        value="{{round($impuesto->id)}}"
                                        porc="{{round($impuesto->porcentaje)}}"
                                        {{$item->id_impuesto==$impuesto->id
                                            && $item->id_impuesto !== NULL ?'selected':''}}
                                        {{$item->id_impuesto_1==$impuesto->id
                                            && $item->id_impuesto_1 !== NULL ?'selected':''}}
                                        {{$item->id_impuesto_2==$impuesto->id
                                            && $item->id_impuesto_2 !== NULL ?'selected':''}}
                                        {{$item->id_impuesto_3==$impuesto->id
                                            && $item->id_impuesto_3 !== NULL ?'selected':''}}
                                        {{$item->id_impuesto_4==$impuesto->id
                                            && $item->id_impuesto_4 !== NULL ?'selected':''}}
                                        {{$item->id_impuesto_5==$impuesto->id
                                            && $item->id_impuesto_5 !== NULL ?'selected':''}}
                                        {{$item->id_impuesto_6==$impuesto->id
                                            && $item->id_impuesto_6 !== NULL ?'selected':''}}
                                        {{$item->id_impuesto_7==$impuesto->id
                                            && $item->id_impuesto_7 !== NULL ?'selected':''}}
                                    >
                                        {{$impuesto->nombre}} - {{round($impuesto->porcentaje)}}%
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td style="padding-top: 1% !important;">
                            <div class="resp-descripcion">
                                <textarea
                                    class="form-control form-control-sm"
                                    id="descripcion{{$loop->iteration}}"
                                    name="descripcion[]"
                                    placeholder="Descripción">
                                    {{$item->descripcion}}
                                </textarea>
                            </div>
                        </td>
                        <td width="5%">
                            <input
                                type="number"
                                class="form-control form-control-sm"
                                id="cant{{$loop->iteration}}"
                                name="cant[]"
                                placeholder="Cantidad"
                                step="0.01"
                                min="0.01"
                                onchange="total(1);"
                                required
                                value="{{round($item->cant, 3)}}"
                            >
                        </td>
                        <td class="columna-bodega" style="display: table-cell !important;">
                            <select
                                class="form-control form-control-sm"
                                name="bodega_item[]"
                                id="bodega_item{{$loop->iteration}}"
                            >
                                @if(isset($bodegas))
                                    @php
                                        $bodegaSeleccionada = isset($item->bodega_id) && $item->bodega_id ? $item->bodega_id : null;
                                    @endphp
                                    @foreach($bodegas as $bodega)
                                        <option value="{{ $bodega->id }}" {{ $bodegaSeleccionada == $bodega->id ? 'selected' : '' }}>
                                            {{ $bodega->bodega }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </td>
                        <td>
                            <div class="resp-total">
                                <input
                                    type="text"
                                    class="form-control form-control-sm text-right"
                                    id="total{{$loop->iteration}}"
                                    value="{{App\Funcion::Parsear($item->total())}}"
                                    disabled
                                >
                            </div>
                        </td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-icons"
                                onclick="Eliminar({{$loop->iteration}});"
                                style="color:#E13130"
                            >
                                X
                            </button>
                        </td>
                    </tr>
                @endforeach
            @endif
        @endif
    </tbody>
</table>
</div>

@section("scripts")
    <script>
        // Función global para cargar bodegas (disponible incluso si los módulos ES6 fallan)
        window.cargarBodegasEnSelectGlobal = function(selectElement) {
            if (!selectElement) return;

            // Verificar si ya tiene opciones
            if (selectElement.querySelectorAll('option').length > 0) {
                return;
            }

            const bodegasInput = document.querySelector("#bodegas_json");
            if (!bodegasInput || !bodegasInput.value) {
                setTimeout(() => window.cargarBodegasEnSelectGlobal(selectElement), 100);
                return;
            }

            try {
                const bodegas = JSON.parse(bodegasInput.value);
                if (bodegas && Array.isArray(bodegas) && bodegas.length > 0) {
                    selectElement.innerHTML = '';
                    bodegas.forEach(bodega => {
                        const option = document.createElement("option");
                        option.value = bodega.id;
                        option.textContent = bodega.bodega || bodega.nombre || `Bodega ${bodega.id}`;
                        selectElement.appendChild(option);
                    });

                    // Establecer valor por defecto
                    const bodegaPrincipal = document.querySelector("#bodegas");
                    if (bodegaPrincipal && bodegaPrincipal.value) {
                        const optionExists = Array.from(selectElement.options).some(opt => opt.value == bodegaPrincipal.value);
                        if (optionExists) {
                            selectElement.value = bodegaPrincipal.value;
                        } else if (selectElement.options.length > 0) {
                            selectElement.value = selectElement.options[0].value;
                        }
                    } else if (selectElement.options.length > 0) {
                        selectElement.value = selectElement.options[0].value;
                    }
                }
            } catch (error) {
                console.error('Error al cargar bodegas:', error);
            }
        };
    </script>
    <script type="module" defer>
        import {createNewRowProduct, delay} from "{{ asset("lowerScripts/ordenes/ordenes.js") }}";
        import {listarProductos} from "{{ asset("lowerScripts/ordenes/producto.js") }}";

        document.querySelector("#agregarLinea").addEventListener("click", () => {
            agregarLineaNueva();
        });

        window.agregarLineaNueva = agregarLineaNueva;

        function agregarLineaNueva() {
            let table = document.querySelector("#table-form tbody");
            let num = document.querySelectorAll("#table-form tbody tr").length + 1;
            if (document.querySelector(`[id="${num}"]`)) {
                for (let i = 1; i <= num; i++) {
                    if (!document.querySelector(`[id='${i}']`)) {
                        num = i;
                        break;
                    }
                }
            }
            createNewRowProduct(num, table);
            return num;
        }
        function establecerBodegaPorDefecto(num) {
            const bodegaPrincipal = document.querySelector("#bodegas");
            const bodegaSelect = document.querySelector(`#bodega_item${num}`);

            if (bodegaPrincipal && bodegaSelect && !bodegaSelect.value) {
                bodegaSelect.value = bodegaPrincipal.value;
            }
        }

        function inicializarBodegasPorDefecto() {
            const bodegaPrincipal = document.querySelector("#bodegas");
            if (bodegaPrincipal) {
                document.querySelectorAll('select[name="bodega_item[]"]').forEach(select => {
                    // Asegurar que el select tenga opciones cargadas primero
                    const numId = select.id.replace('bodega_item', '');
                    if (select.querySelectorAll('option').length === 0) {
                        asegurarBodegasEnSelect(numId);
                    }
                    // Luego establecer el valor por defecto
                    if (!select.value && select.options.length > 0) {
                        select.value = bodegaPrincipal.value || select.options[0].value;
                    }
                });
            }
        }

        // Inicializar cuando el DOM esté listo
        function inicializarBodegas() {
            // Esperar a que el input bodegas_json esté disponible
            const bodegasInput = document.querySelector("#bodegas_json");
            if (!bodegasInput) {
                // Si no está disponible, intentar de nuevo después de un momento
                setTimeout(inicializarBodegas, 100);
                return;
            }

            inicializarBodegasPorDefecto();
        }

        // Intentar inicializar inmediatamente y también cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarBodegas);
        } else {
            // DOM ya está listo
            inicializarBodegas();
        }

        // Función para asegurar que las bodegas estén cargadas en un select
        function asegurarBodegasEnSelect(num) {
            // Si num es un string con el ID completo, extraer solo el número
            const numId = typeof num === 'string' && num.includes('bodega_item') ? num.replace('bodega_item', '') : num;
            const bodegaSelect = typeof num === 'object' ? num : document.querySelector(`#bodega_item${numId}`);

            if (!bodegaSelect) {
                console.warn('No se encontró el select de bodega para:', numId || num);
                return;
            }

            // Verificar si ya tiene opciones
            if (bodegaSelect.querySelectorAll('option').length > 0) {
                return; // Ya tiene opciones, no hacer nada
            }

            const bodegasInput = document.querySelector("#bodegas_json");
            if (!bodegasInput) {
                console.warn('No se encontró el input #bodegas_json');
                return;
            }

            try {
                const bodegasValue = bodegasInput.value;
                if (!bodegasValue || bodegasValue.trim() === '') {
                    console.warn('El input #bodegas_json está vacío');
                    return;
                }

                const bodegas = JSON.parse(bodegasValue);
                const bodegaPrincipal = document.querySelector("#bodegas");

                if (bodegas && Array.isArray(bodegas) && bodegas.length > 0) {
                    // Limpiar el select primero
                    bodegaSelect.innerHTML = '';

                    bodegas.forEach(bodega => {
                        const option = document.createElement("option");
                        option.value = bodega.id;
                        option.textContent = bodega.bodega || bodega.nombre || `Bodega ${bodega.id}`;
                        bodegaSelect.appendChild(option);
                    });

                    // Establecer valor por defecto
                    const bodegaPrincipalValue = bodegaPrincipal ? bodegaPrincipal.value : null;
                    if (bodegaPrincipalValue) {
                        const optionExists = Array.from(bodegaSelect.options).some(opt => opt.value == bodegaPrincipalValue);
                        if (optionExists) {
                            bodegaSelect.value = bodegaPrincipalValue;
                        } else if (bodegaSelect.options.length > 0) {
                            bodegaSelect.value = bodegaSelect.options[0].value;
                        }
                    } else if (bodegaSelect.options.length > 0) {
                        bodegaSelect.value = bodegaSelect.options[0].value;
                    }
                } else {
                    console.warn('No se encontraron bodegas en el JSON o el array está vacío');
                }
            } catch (error) {
                console.error('Error al cargar bodegas:', error);
                console.error('Valor del input:', bodegasInput.value);
            }
        }

        @if(isset($items))
            const content = document.querySelectorAll("#table-form tbody tr");
            content.forEach(item => {
                const inputBusqueda = item.querySelector(`#item${item.id}`);
                const num = item.id;
                inputBusqueda.addEventListener("keyup", delay((e) => {
                    listarProductos(inputBusqueda, num);
                }, 1000));

                // Asegurar que las bodegas estén cargadas
                asegurarBodegasEnSelect(num);
                establecerBodegaPorDefecto(num);
            });
        @else
            window.onload = (event) => {
            agregarLineaNueva();
        }
        @endif
    </script>

    {{-- Script adicional para asegurar que las bodegas se carguen incluso si los módulos ES6 fallan --}}
    <script>
        // Observador para detectar cuando se agregan nuevas filas
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.tagName === 'TR') {
                        const bodegaSelect = node.querySelector('select[name="bodega_item[]"]');
                        if (bodegaSelect && bodegaSelect.querySelectorAll('option').length === 0) {
                            // Usar la función global si está disponible
                            if (typeof window.cargarBodegasEnSelectGlobal === 'function') {
                                window.cargarBodegasEnSelectGlobal(bodegaSelect);
                            } else {
                                // Si no está disponible, intentar después de un momento
                                setTimeout(() => {
                                    if (typeof window.cargarBodegasEnSelectGlobal === 'function') {
                                        window.cargarBodegasEnSelectGlobal(bodegaSelect);
                                    }
                                }, 200);
                            }
                        }
                    }
                });
            });
        });

        // Observar cambios en el tbody de la tabla
        const tableBody = document.querySelector("#table-form tbody");
        if (tableBody) {
            observer.observe(tableBody, {
                childList: true,
                subtree: true
            });
        }

        // También verificar todas las filas existentes después de que todo esté cargado
        setTimeout(function() {
            document.querySelectorAll('select[name="bodega_item[]"]').forEach(function(select) {
                if (select.querySelectorAll('option').length === 0) {
                    if (typeof window.cargarBodegasEnSelectGlobal === 'function') {
                        window.cargarBodegasEnSelectGlobal(select);
                    }
                }
            });
        }, 500);
    </script>
@endsection
