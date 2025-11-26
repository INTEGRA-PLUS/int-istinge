@if(Auth::user()->modo_lectura())
@else
    <a href="{{route('notascredito.show',$nota->nro)}}"  class="btn btn-outline-info btn-icons" title="Ver"><i class="far fa-eye"></i></i></a>
    <a href="{{route('notascredito.imprimir.nombre',['id' => $nota->nro, 'name'=> 'Nota Credito No. '.$nota->nro.'.pdf'])}}" target="_blank" class="btn btn-outline-primary btn-icons" title="Imprimir"><i class="fas fa-print"></i></a>

    @if (($nota->emitida == 0 && $empresa->estado_dian == 1))

        @if($empresa->proveedor == 1 || $empresa->proveedor == null)
        <a onclick="confirmSendDian('{{ route('xml.notacredito', $nota->id, true) }}','{{ $nota->nro }}')"
            href="#" class="btn btn-outline-primary btn-icons"
            title="Emitir nota crédito {{ $nota->nro }}">
            <i class="fas fa-sitemap"></i>
        </a>
        @elseif($empresa->proveedor == 2)
        <a onclick="confirmSendDian('{{ route('json.dian-notacredito', $nota->id, true) }}','{{ $nota->nro }}')"
            href="#" class="btn btn-outline-primary btn-icons"
            title="Emitir nota crédito {{ $nota->nro }}">
            <i class="fas fa-sitemap"></i>
        </a>
        @endif

    @endif

    @if($nota->emitida !=1)
        <a href="{{route('notascredito.edit',$nota->nro)}}"  class="btn btn-outline-primary btn-icons" title="Editar"><i class="fas fa-edit"></i></a>
    @endif
    <form action="{{ route('notascredito.destroy',$nota->id) }}" method="post" class="delete_form" style="margin:  0;display: inline-block;" id="eliminar-notascredito{{$nota->id}}">
    	{{ csrf_field() }}
    	<input name="_method" type="hidden" value="DELETE">
    </form>
    @if($nota->emitida !=1)
        <button class="btn btn-outline-danger  btn-icons negative_paging" type="submit" title="Eliminar" onclick="confirmar('eliminar-notascredito{{$nota->id}}', '¿Estas seguro que deseas eliminar nota de crédito?', 'Se borrara de forma permanente');"><i class="fas fa-times"></i></button>
    @endif
    <a href="{{route('notascredito.showmovimiento',$nota->id)}}" class="btn btn-outline-info btn-icons" title="Ver movimientos"><i class="far fa-sticky-note"></i></a>
@endif