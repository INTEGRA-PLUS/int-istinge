<center>
    <a href="{{route('crm.show',$id)}}" class="btn btn-outline-info btn-icons" title="Ver"><i class="fas fa-eye"></i></i></a>
    {{-- @if($estado == 0 || $estado == 2 || $estado == 3 || $estado == 4 || $estado == 5 || $estado == 6) --}}
    @if (isset($session['746']))
        <a href="javascript:gestionar({{$c_id}}, {{$id}});" class="btn btn-outline-success btn-icons" title="Llamar"><i class="fas fa-phone"></i></i></a>
    @endif
    {{-- @endif --}}
    @if($estado == 4)
        @if (isset($session['747']))
            <a href="javascript:cambiarRetiroTotal('{{$id}}');" title="" class="btn btn-outline-danger btn-icons"><i class="fas fa-times"></i></a>
        @endif
    @endif

    @if (isset($session['847']))
        <a href="{{route('crm.log', $id)}}" title="Ve Log del CRM" class="btn btn-outline-warning btn-icons"><i class="fas fa-clipboard-list"></i></a>
    @endif

    <form action="{{ route('crm.destroy', $id) }}" method="post" class="delete_form" style="margin: 0; display: inline-block;" id="eliminar-crm-{{$id}}">
        @csrf
        <input name="_method" type="hidden" value="DELETE">
    </form>
    <button class="btn btn-outline-danger btn-icons" type="submit" title="Eliminar" onclick="confirmar('eliminar-crm-{{$id}}', '¿Está seguro que desea eliminar este registro CRM?', 'Se borrará de forma permanente');"><i class="fas fa-times"></i></button>
</center>