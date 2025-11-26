function validateDianNomina(id, rutasuccess, codigo,tipo) {

    let messageTipo = "";
    if(tipo == 1){
        messageTipo = "¿Emitir Nómina a la Dian?"
    }else if(tipo == 2){
        messageTipo = "¿Emitir Ajuste de Nómina?"
    }else if(tipo==3){
        messageTipo = "¿Emitir Nómina de cancelación?"
    }

    $titleswal = codigo + '<br>'+ messageTipo;
    $textswal = "No podrás retroceder esta acción";
    $confirmswal = "Si, emitir";

    Swal.fire({
        title: $titleswal,
        text: $textswal,
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: $confirmswal,
    }).then((result) => {
        if (result.value) {

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-center',
                showConfirmButton: false,
                timer: 1000000000,
                timerProgressBar: true,
                onOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            })

            Toast.fire({
                type: 'success',
                title: 'Emitiendo nomina a la DIAN...',
            })

            $.ajax({
                url: '/empresa/nominadian/validatedian',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                method: 'get',
                dataType: 'json',
                data: { id: id,
                tipo: tipo },
                success: function(response) {

                    console.log(response);

                    // Si la respuesta viene envuelta en un objeto con 'original', extraer los datos
                    if (response && response.original) {
                        response = response.original;
                    }

                    if (response == 'nomina-vencida') {
                        $mensaje = "Para emitir a la Dian se debe tener un inicio en la numeración de la factura.";
                        $footer = "<a target='_blank' href='/empresa/configuracion/numeraciones_nomina_electronica/lista'>Configura tus numeraciones</a>";
                        $img = "gif-tuerca.gif";
                        messageValidateDian($mensaje, $footer, $img);
                    } else if (response == 'nomina-consecutivo-limite') {
                        $mensaje = "La numeración ha superado el limite de consecutivos";
                        $footer = "<a target='_blank' href='/empresa/configuracion/numeraciones_nomina_electronica/lista'>Configura tus numeraciones</a>";
                        $img = "gif-tuerca.gif";
                        messageValidateDian($mensaje, $footer, $img);
                    } else if (response == 'plazo-vencido') {
                        $mensaje = "El plazo de 10 días ha caducado para emitir nóminas electrónicas";
                        $footer = "";
                        $img = "gif-tuerca.gif";
                        messageValidateDian($mensaje, $footer, $img);
                    } else if (response == 'mucha-solicitud') {
                        $mensaje = "Hay demasiadas solicitudes en la Dian, por favor intentalo más tarde.";
                        $footer = "";
                        $img = "gif-tuerca.gif";
                        messageValidateDian($mensaje, $footer, $img);
                    }else if (response == 'error-numeracion') {
                        $mensaje = "Se debe escoger una numeración para poder emitir.";
                        $footer = "";
                        $img = "gif-tuerca.gif";
                        messageValidateDian($mensaje, $footer, $img);
                    }else if(response == 'eventos-rapidos'){
                        $mensaje = "Ya se emitió un evento hace muy poco tiempo, intente nuevamente.";
                        $footer = "";
                        $img = "gif-tuerca.gif";
                        messageValidateDian($mensaje, $footer, $img);
                    }else if (response.statusCode == 409 || response.statusCode == 400 || response.statusCode == 500) {

                        motivo = "";
                        i = 1;
                        response.warnings.reverse().forEach(e => {
                            motivo += `<p style="color:red;font-size:16px;">${i}. ${e}</p> <br>`;
                            i++;
                        });

                        Swal.fire({
                            type: 'error',
                            title: 'Error',
                            html: "No se pudo emitir la nomina Documento con errores en campos mandatorios. <br>" + motivo,
                        })
                    } else if (response.statusCode == 504) {

                        if (response.errorMessage) {
                            msgError = response.errorMessage
                        } else {
                            msgError = "Error interno de la Dian, porfavor vuelve a intentarlo en unos minutos."
                        }

                        Swal.fire({
                            type: 'error',
                            title: 'Error',
                            html: msgError,
                        })

                    } else if (response.statusCode == 422 && response.th && response.th.status == 'error') {
                        // Manejo de error con formato statusCode, errorMessage y th
                        let msgError = response.errorMessage || 'Error al realizar la petición';
                        let detailMsg = '';

                        // Priorizar mostrar errorMessages si están disponibles
                        if (response.th && response.th.errorMessages && Array.isArray(response.th.errorMessages) && response.th.errorMessages.length > 0) {
                            detailMsg = '<br><br><strong>Errores:</strong><ul>';
                            response.th.errorMessages.forEach(error => {
                                detailMsg += '<li>' + error + '</li>';
                            });
                            detailMsg += '</ul>';
                        } else if (response.th && response.th.error) {
                            // Si hay un campo error con HTML, usarlo
                            detailMsg = '<br><br>' + response.th.error;
                        } else if (response.th && response.th.message) {
                            // Fallback al mensaje simple
                            detailMsg = '<br><br><strong>Detalle:</strong><br>' + response.th.message;
                        }

                        Swal.fire({
                            type: 'error',
                            title: 'Error',
                            html: msgError + detailMsg,
                        })

                    } else if (response == 'codigo-repetido') {
                        $mensaje = "Error al emitir nomina repetida, por favor intente nuevamente";
                        $footer = "";
                        $img = "gif-tuerca.gif";
                        messageValidateDian($mensaje, $footer, $img);
                    } else if (response && typeof response === 'object' && response.success === false) {
                        // Manejo de errores de validación de BTW
                        const errorMsg = (response.data && response.data.error)
                            ? response.data.error
                            : (response.mesagge || response.message || 'Error al procesar la nómina');

                        Swal.fire({
                            type: 'error',
                            title: 'Error en validaciones',
                            html: errorMsg,
                            confirmButtonText: 'OK'
                        })
                    } else if (response && typeof response === 'object' && response.success === true) {
                        //-- /Validaciones para la factura --//

                        Swal.fire({
                            type: 'success',
                            title: '¡Éxito!',
                            text: response.mesagge || 'La nómina fue emitida satisfactoriamente',
                            confirmButtonText: 'OK',
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            // Refrescar la página para mostrar el cambio de estado
                            window.location.reload();
                        });

                    } else {
                        // Caso por defecto: si no coincide con ninguna condición anterior
                        // pero la respuesta es un objeto, intentar refrescar
                        if (response && typeof response === 'object') {
                            Swal.fire({
                                type: 'info',
                                title: 'Procesando...',
                                text: 'La nómina está siendo procesada',
                                timer: 2000
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    }

                }
            })
        }
    })
}

function messageValidateDian($mensaje, $footer, $img) {

    var confirmButtonText = "OK";

    if ($img == "contrato.png") {
        confirmButtonText = '<a target="_blank" href="/empresa/configuracion" style="color:#fff">OK</a>';
    }
    Swal.fire({
        //type: 'error',
        title: 'Oops...',
        text: $mensaje,
        imageUrl: '/images/Documentacion/validaciones/' + $img,
        imageWidth: '25%',
        imageHeight: '100%',
        imageAlt: 'Custom image',
        confirmButtonText: confirmButtonText,
        footer: $footer
    })
}
