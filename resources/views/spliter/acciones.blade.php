@if($uso==0 && isset($cajas_disponible) && isset($num_cajas_naps) && $cajas_disponible == $num_cajas_naps)
    <form action="{{ route('spliter.destroy', $id) }}" method="post" class="delete_form" style="margin:  0;display: inline-block;" id="eliminar{{$id}}">
        @csrf
        <input name="_method" type="hidden" value="DELETE">
    </form>
@endif

@if(isset($session['713']))
    <a href="{{route('spliter.show', $id)}}" class="btn btn-outline-info btn-icons" title="Ver"><i class="far fa-eye"></i></a>
@endif
@if(isset($session['714']))
    <a href="{{route('spliter.edit', $id)}}" class="btn btn-outline-primary btn-icons" title="Editar"><i class="fas fa-edit"></i></a>
@endif
@if(isset($session['715']))
    @if($uso==0)
        <button type="button" class="btn btn-outline-danger btn-icons" type="submit" title="Eliminar" onclick="confirmar('eliminar{{$id}}', '¿Está seguro que desea eliminar el spliter?', 'Se borrará de forma permanente');"><i class="fas fa-times"></i></button>
    @endif
@endif