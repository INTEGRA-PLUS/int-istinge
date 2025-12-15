<div class="dropdown w-100">
    <button style="background-color: {{ $radicado->etiqueta ? $radicado->etiqueta->color : '' }} !important" class="btn btn-secondary dropdown-toggle w-100" type="button" id="etiqueta-drop-{{$radicado->id}}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        {{ $radicado->etiqueta ? $radicado->etiqueta->nombre : 'etiquetar' }}
    </button>
    <div class="dropdown-menu w-100" aria-labelledby="etiqueta-drop-{{$radicado->id}}" style="max-height:200px; overflow: auto">
        <a class="dropdown-item" href="javascript:cambiarEtiqueta(0, {{ $radicado->id }})">Eliminar etiqueta</a>
        @foreach($etiquetas as $etiqueta)
            <a class="dropdown-item" href="javascript:cambiarEtiqueta({{ $etiqueta->id }}, {{ $radicado->id }})">{{ $etiqueta->nombre }}</a>
        @endforeach
    </div>
</div>

<script>
    function cambiarEtiqueta(etiqueta, contacto){
        $.get('{{URL::to('/')}}/empresa/radicados/cambiar-etiqueta/'+etiqueta+'/'+contacto, function(response){
            let button = $('#etiqueta-drop-'+contacto);

            if (response.nombre && response.color) {
                button.html(response.nombre);
                button.css('background-color', response.color);
            } else {
                button.html('etiquetar');
                button.css('background-color', '#e5e5e5');
            }
        });
    }
</script>
