
/*seleccionar checked o no*/
$(document).ready(function(){

    $("#equivalente").change(function (){

        if( $('#equivalente').prop('checked') ) {
          $(".loader").show();
          $.ajax({
          url: '/empresa/facturasp/docequivalente',
          headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
          method:'post',
          // data:{status:$("#docEquivalente").val()},
          success: function(data)
          {

            if(data != 0)
            {
                if(data.error){
                    alert("Estamos presentando problemas al consultar la numeración, porfavor comuniquese con soporte");
                  }else{
                    $("#cod_dian").show();
                    $("#codigo_dian").val(data.prefijo + data.inicio);
                  }
            }else{
                $("#equivalente").prop("checked", false);
                alert("Debe escoger una numeracion como preferida o activarala");
            }

            $(".loader").hide();
          }
        });
      }else{
        $("#cod_dian").hide();
      }
    });

    if($("#dian").val())
    {
        $("#cod_dian").show();
        $("#equivalente").prop("checked", true);
        $("#codigo_dian").val($("#dian").val());
    }
});
