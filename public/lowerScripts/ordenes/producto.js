export const rowTemplate = (num) => `
    <tr id="${num}">
        <td class="no-padding">
            <div class="resp-item">
                <input type="text" class="form-control mt-4"
                    placeholder="Digite el item" name="productos[]"
                    autocomplete="off" id="item${num}" required>
                <input type="hidden" name="item[]" id="items-${num}">
                <input type="hidden" name="type[]" id="type${num}" value="inv">
            </div>
            <div class="row content-lista d-none" id="lista-${num}">
                <div class="col-md-12">
                    <div class="list-group">
                        <ul class="inner" aria-expanded="true" style="max-height: 324px;
                        overflow-y: auto; min-height: 0px; list-style: none;"
                        id="content-${num}">
                        </ul>
                    </div>
                </div>
            </div>
            <a href="" style="padding: 10px;" data-toggle="modal" data-target="#modalproduct" class="modalTr" tr="${num}"><i class="fas fa-plus"></i> Nuevo Producto</a>
            <a href="" style="margin-left: 12px; display: none; padding: 10px;" class="datos-adicionalesToggle" onclick="seInputRowData(${num})" data-toggle="modal" data-target="#datos-adicionales" class="modalTr" tr="${num}"><i class="fas fa-plus"></i> Datos Adicionales</a>
            </p>

        </td>
        <td>
            <input placeholder="Referencia" type="text" class="form-control" name="ref[]" id="ref${num}">
        </td>
        <td class="columna-marca">
        <input
        type="text"
        class="form-control form-control-sm"
        id="marca-estatica${num}"
        name="marcas_estaticas[]"
        placeholder="Marca">
        </td>
        <td class="monetario">
            <input type="number" class="form-control form-control-sm"
            id="precio${num}" maxlength="24" min="0" name="precio[]"
             placeholder="Precio Unitario" onkeyup="total(${num})" required="">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm"
            id="desc${num}" name="desc[]" maxlength="5"
            placeholder="%" onkeypress="return event.charCode >= 46 && event.charCode <=57"
            onkeyup="total(${num})" max="100" min="0">
        </td>
        <td class="td-impuesto">
            <select class="form-control form-control-sm selectpicker"
                name="impuesto${num}[]"
                id="impuesto${num}"
                required
                title="Impuesto"
                onchange="impuestoFacturaDeVenta('impuesto${num}'); total(${num});checkImp(${num});"
                multiple data-live-search="true" data-size="10">

            </select>
        </td>
        <td  style="padding-top: 1% !important;">
            <textarea  class="form-control form-control-sm" id="descripcion${num}"
                name="descripcion[]" placeholder="Descripción"></textarea>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm"
                id="cant${num}" name="cant[]" placeholder="Cantidad"
                maxlength="24" onchange="total(${num});" min="1" required="">
            <p class="text-danger nomargin" id="pcant${num}"></p>
        </td>
        <td class="columna-bodega" style="display: table-cell !important;">
            <select class="form-control form-control-sm" name="bodega_item[]" id="bodega_item${num}">
            </select>
        </td>
        <td>
            <input type="text"
                class="form-control form-control-sm text-right" id="total${num}"
                value="0" disabled="">
        </td>
        <td>
            <button type="button" onclick="Eliminar(${num});"
                class="btn btn-outline-danger btn-icons"
                style="color:#E13130">
                X
            </button>
        </td>
    </tr>
`;

const productTemplate = (item) => `
    <li data-original-index="1"
    title="${item.producto ? item.producto : ''} - ${item.ref ? item.ref : ''}">
        <a onclick=""
            tabindex="0"
            data-tokens="null"
            role="option"
            aria-disabled="false"
            aria-selected="false"
            id="enlace-${item.id}"
            class="list-group-item list-group-item-action border-1"
            style="cursor: pointer;"
            value="${item.id}"
            data-bodega-id="${item.bodega_id || ''}">
            <span class="text">
                ${item.producto ? item.producto.substring(0, 26) : ''}
                - ${item.ref ? item.ref.toString().substring(0, 26) : ""}
                ${item.item_referencias ? item.item_referencias.map(r => r.ref) : ''}
            </span>
            <input type="hidden" value="${item.type}">
        </a>
    </li>
`;

const searchProducts = async (data) => {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: "/facturas/productos",
            data: data,
            method: "GET",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf_token"]').attr("content")
            },
            success: (response) => {
                resolve(response);
            }
        })
    });
}

export const listarProductos = async (input, num) => {
    const value = input.value;
    const isVenta = document.querySelector("#is-venta");
    const content = document.querySelector(`#content-${num}`);
    const bodega = document.querySelector("select#bodegas");
    const bodegaValue = bodega ? bodega.value : null;
    const isVentaValue = isVenta ? isVenta.value : null
    const data = {
        producto: value,
        bodega: bodegaValue,
        isVenta: isVentaValue
    }

    if (value === "") {
        buscador.cerrar(num);
        return;
    }

    buscador.abrir(num);

    let listado = "";

    const res = await searchProducts(data)

    res.data.map((item) => {
        listado += productTemplate(item);
    });

    content.innerHTML = listado;

    document.querySelectorAll(`#content-${num} a`).forEach(item => {
        item.addEventListener("click", async (evt) => {
            const target = evt.currentTarget;
            const value = target.getAttribute("value");
            const type = target.querySelector("input").value;
            console.log(type);
            //console.log(target.innerText);
            rellenar(num, value, false, false, type);
            document.querySelector(`#items-${num}`).value = value;
            input.value = target.innerText;
            buscador.cerrar(num);

            // Obtener la bodega asociada al item seleccionado desde los datos del item
            // Solo si es tipo inventario (inv) y tiene bodega_id en los datos
            if ((type === 'inv' || type === '1') && target.dataset.bodegaId) {
                establecerBodegaDesdeDatos(target.dataset.bodegaId, num);
            } else if (type === 'inv' || type === '1') {
                // Fallback: si no viene en los datos, hacer la llamada AJAX (compatibilidad hacia atrás)
                await obtenerBodegaPorItem(value, num, 0);
            }
        });
    });

    /*$(document).on("click", `#content-${num} a`, function () {
        let valor = $(this).attr("value").trim();

        rellenar(num, valor);
        input.value = this.
        $(input.id).val($(this).text().trim());
        buscador.cerrar(num);
    });*/
}

const buscador = {
    abrir: (num) => {
        document.querySelector(`#lista-${num}`)
            .classList.remove("d-none");
    },
    cerrar: (num) => {
        document.querySelector(`#lista-${num}`)
            .classList.add("d-none");
        document.querySelector(`#content-${num}`).innerHTML = "";
    }
}

// Función para establecer la bodega desde los datos del item (evita llamadas AJAX)
const establecerBodegaDesdeDatos = (bodegaId, num) => {
    if (!bodegaId) return;

    const bodegaSelect = document.querySelector(`#bodega_item${num}`);
    if (!bodegaSelect) return;

    // Asegurar que el select tenga opciones cargadas
    if (bodegaSelect.querySelectorAll('option').length === 0) {
        const bodegasInput = document.querySelector("#bodegas_json");
        if (bodegasInput) {
            try {
                const bodegas = JSON.parse(bodegasInput.value);
                bodegas.forEach(bodega => {
                    const option = document.createElement("option");
                    option.value = bodega.id;
                    option.textContent = bodega.bodega || bodega.nombre || `Bodega ${bodega.id}`;
                    bodegaSelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error al cargar bodegas:', error);
                return;
            }
        } else {
            return;
        }
    }

    // Establecer la bodega si existe en las opciones
    const optionExists = Array.from(bodegaSelect.options).some(opt => opt.value == bodegaId);
    if (optionExists) {
        bodegaSelect.value = bodegaId;
        bodegaSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
};

// Función para obtener la bodega asociada a un item (fallback para compatibilidad)
const obtenerBodegaPorItem = async (itemId, num, empresa) => {
    try {
        // Si empresa es 0 o null, el backend usará auth()->user()->empresa
        const empresaParam = empresa && empresa !== 0 ? empresa : 0;
        const url = `/inventario/getBodegaByItem/${itemId}/${empresaParam}`;

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                              document.querySelector('meta[name="csrf_token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            }
        });

        if (response.ok) {
            const data = await response.json();
            if (data.status === 200 && data.bodega) {
                // Obtener el select de bodega
                const bodegaSelect = document.querySelector(`#bodega_item${num}`);
                if (bodegaSelect) {
                    const establecerBodega = () => {
                        const optionExists = Array.from(bodegaSelect.options).some(opt => opt.value == data.bodega);
                        if (optionExists) {
                            bodegaSelect.value = data.bodega;
                            bodegaSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        } else {
                            const bodegasInput = document.querySelector("#bodegas_json");
                            if (bodegasInput && bodegaSelect.querySelectorAll('option').length === 0) {
                                const bodegas = JSON.parse(bodegasInput.value);
                                bodegas.forEach(bodega => {
                                    const option = document.createElement("option");
                                    option.value = bodega.id;
                                    option.textContent = bodega.bodega;
                                    bodegaSelect.appendChild(option);
                                });
                                if (Array.from(bodegaSelect.options).some(opt => opt.value == data.bodega)) {
                                    bodegaSelect.value = data.bodega;
                                    bodegaSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            }
                        }
                    };

                    establecerBodega();

                    setTimeout(() => {
                        if (bodegaSelect.value != data.bodega) {
                            establecerBodega();
                        }
                    }, 100);
                }
            }
        }
    } catch (error) {
        console.error('Error al obtener bodega por item:', error);
    }
}

export const guardarIdentificador = (itemId, num) => {
    document.querySelector(`#items-${num}`).value = itemId;
}

