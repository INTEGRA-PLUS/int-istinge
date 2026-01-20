export const loadImpuestos = (impuestosJson, num) => {
    var impuestos = JSON.parse($('#impuestos').val());
    $.each(impuestos, function (key, value) {
        $('#impuesto' + num).append($('<option>', {
            value: value.id,
            text: value.nombre + "-" + value.porcentaje + "%"
        }));
    })
    /*impuestosJson.forEach((impuesto) => {
        const option = document.createElement("option");
        option.value = impuesto.id;
        option.text = `${impuesto.nombre}-${impuesto.porcentaje}%`
        document.querySelector(`#impuesto${num}`).append(option);
    })*/
}
