<a href="{{route('caja.naps.show', $id)}}" class="btn btn-outline-info btn-icons" title="Ver">
    <i class="fas fa-eye"></i>
</a>
<?php if (isset($_SESSION['permisos']['712'])) { ?>
    <a href="{{route('caja.naps.edit', $id)}}" class="btn btn-primary btn-icons" title="Editar">
        <i class="fas fa-edit"></i>
    </a>
    @if($caja_naps_disponible == $cant_puertos)
    <form action="{{route('caja.naps.destroy', $id)}}" method="POST" style="display: inline-block;" onsubmit="return confirm('¿Está seguro que desea eliminar esta caja nap?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-icons" title="Eliminar">
            <i class="fas fa-trash"></i>
        </button>
    </form>
    @endif
<?php } ?>
