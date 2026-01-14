import {rowTemplate, listarProductos} from "./producto.js";
import {loadImpuestos} from "./impuesto.js";

export const delay = (callback, ms) => {
    let timer = 0;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(callback.bind(this, ...args), ms || 0);
    }
}

export const createNewRowProduct = (num, table) => {
    let template = rowTemplate(num);
    let row = document.createElement("tr");
    row.setAttribute("id", num);
    row.innerHTML = template;
    table.append(row);
    //$("#table-form tbody").append(template);
    const impuestos = JSON.parse(document.querySelector("#impuestos").value);
    loadImpuestos(impuestos, num);

    // Verificar el tipo de bodega seleccionado
    const tipoBodega = document.querySelector("#tipo_bodega");
    const tipoBodegaValue = tipoBodega ? tipoBodega.value : 'general';
    const columnaBodega = row.querySelector('.columna-bodega');
    const bodegaSelect = row.querySelector('select[name="bodega_item[]"]');

    // Cargar bodegas en el select - usar función global si está disponible, sino cargar directamente
    const cargarBodegasEnSelect = () => {
        if (!bodegaSelect) {
            return;
        }

        // Si ya tiene opciones, no hacer nada
        if (bodegaSelect.querySelectorAll('option').length > 0) {
            return;
        }

        // Intentar usar la función global primero (más confiable)
        if (typeof window.cargarBodegasEnSelectGlobal === 'function') {
            window.cargarBodegasEnSelectGlobal(bodegaSelect);
            return;
        }

        // Si no está disponible la función global, cargar directamente
        const bodegasInput = document.querySelector("#bodegas_json");
        if (!bodegasInput) {
            setTimeout(cargarBodegasEnSelect, 50);
            return;
        }

        try {
            const bodegasValue = bodegasInput.value;
            if (!bodegasValue || bodegasValue.trim() === '') {
                setTimeout(cargarBodegasEnSelect, 50);
                return;
            }

            const bodegas = JSON.parse(bodegasValue);

            // Limpiar el select primero
            bodegaSelect.innerHTML = '';

            // Cargar todas las bodegas
            if (bodegas && Array.isArray(bodegas) && bodegas.length > 0) {
                bodegas.forEach(bodega => {
                    const option = document.createElement("option");
                    option.value = bodega.id;
                    option.textContent = bodega.bodega || bodega.nombre || `Bodega ${bodega.id}`;
                    bodegaSelect.appendChild(option);
                });

                // Establecer el valor por defecto después de cargar las opciones
                const bodegaPrincipal = document.querySelector("#bodegas");
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
            }
        } catch (error) {
            console.error('Error al cargar bodegas en createNewRowProduct:', error);
            setTimeout(cargarBodegasEnSelect, 100);
        }
    };

    // Intentar cargar inmediatamente y también después de un breve delay
    cargarBodegasEnSelect();
    setTimeout(cargarBodegasEnSelect, 50);

    if (columnaBodega) {
        // Asegurar que la columna siempre esté visible
        columnaBodega.style.display = 'table-cell';

        if (tipoBodegaValue === 'individual') {
            if (bodegaSelect) {
                bodegaSelect.setAttribute('required', 'required');
            }
        } else {
            if (bodegaSelect) {
                bodegaSelect.removeAttribute('required');
            }
        }
    }

    setTimeout(() => {
        const tipoBodegaActual = document.querySelector("#tipo_bodega");
        if (tipoBodegaActual && columnaBodega) {
            const modoActual = tipoBodegaActual.value;
            // Asegurar que la columna siempre esté visible
            columnaBodega.style.display = 'table-cell';

            if (bodegaSelect) {
                if (modoActual === 'individual') {
                    bodegaSelect.setAttribute('required', 'required');
                } else {
                    bodegaSelect.removeAttribute('required');
                }
            }
        }

        // Verificar nuevamente que las opciones estén cargadas
        if (bodegaSelect && bodegaSelect.querySelectorAll('option').length === 0) {
            const bodegasInput = document.querySelector("#bodegas_json");
            if (bodegasInput) {
                try {
                    const bodegas = JSON.parse(bodegasInput.value);
                    if (bodegas && Array.isArray(bodegas) && bodegas.length > 0) {
                        bodegas.forEach(bodega => {
                            const option = document.createElement("option");
                            option.value = bodega.id;
                            option.textContent = bodega.bodega;
                            bodegaSelect.appendChild(option);
                        });
                    }
                } catch (error) {
                    console.error('Error al cargar bodegas en timeout:', error);
                }
            }
        }

        const bodegaPrincipal = document.querySelector("#bodegas");
        if (bodegaPrincipal && bodegaSelect && bodegaPrincipal.value) {
            const bodegaPrincipalValue = bodegaPrincipal.value;
            const optionExists = Array.from(bodegaSelect.options).some(opt => opt.value == bodegaPrincipalValue);
            if (optionExists) {
                bodegaSelect.value = bodegaPrincipalValue;
            } else if (bodegaSelect.options.length > 0) {
                bodegaSelect.value = bodegaSelect.options[0].value;
            }
        } else if (bodegaSelect && bodegaSelect.options.length > 0) {
            // Si no hay bodega principal, usar la primera disponible
            bodegaSelect.value = bodegaSelect.options[0].value;
        }
    }, 100);

    const inputBusqueda = document.querySelector(`#item${num}`);
    inputBusqueda
        .addEventListener("keyup", delay((e) => {
            listarProductos(inputBusqueda, num).then();
        }, 1000))

    $('#item' + num).selectpicker();
    $('#impuesto' + num).selectpicker();
}
