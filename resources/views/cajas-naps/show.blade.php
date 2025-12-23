@extends('layouts.app')

@section('boton')
    @if(auth()->user()->modo_lectura())
	    <div class="alert alert-warning text-left" role="alert">
	        <h4 class="alert-heading text-uppercase">Integra Colombia: Suscripción Vencida</h4>
	       <p>Si desea seguir disfrutando de nuestros servicios adquiera alguno de nuestros planes.</p>
<p>Medios de pago Nequi: 3206909290 Cuenta de ahorros Bancolombia 42081411021 CC 1001912928 Ximena Herrera representante legal. Adjunte su pago para reactivar su membresía</p>
	    </div>
	@else
    <a href="{{route('caja.naps.index')}}" class="btn btn-outline-danger btn-sm"><i class="fas fa-backward"></i> Regresar</a>
    <?php if (isset($_SESSION['permisos']['712'])) { ?>
        <a href="{{route('caja.naps.edit', $caja_nap->id)}}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Editar</a>
    <?php } ?>
    @endif
@endsection

@section('style')
<style>
    .card-header {
        background-color: {{Auth::user()->rol > 1 ? Auth::user()->empresa()->color:''}};
        border-bottom: 1px solid {{Auth::user()->rol > 1 ? Auth::user()->empresa()->color:''}};
    }
    .bg-th{
        background: {{Auth::user()->rol > 1 ? Auth::user()->empresa()->color:''}} !important;
        border-color: {{Auth::user()->rol > 1 ? Auth::user()->empresa()->color:''}} !important;
        color: #fff !important;
    }
</style>
@endsection

@section('content')
    <div class="row card-description">
        <div class="col-md-12 mb-4">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-sm info">
                    <tbody>
                        <tr class="text-center">
                            <th class="bg-th text-center">Nombre</th>
                            <th class="bg-th text-center">Spliter Asociado</th>
                            <th class="bg-th text-center">Cantidad de Puertos</th>
                            <th class="bg-th text-center">Puertos Disponibles</th>
                            <th class="bg-th text-center">Ubicación</th>
                            <th class="bg-th text-center">Coordenadas</th>
                            <th class="bg-th text-center">Estado</th>
                            @if($caja_nap->updated_by)
                            <th class="bg-th text-center">Editado</th>
                            @endif
                        </tr>
                        <tr>
                            <td class="text-center">{{$caja_nap->nombre}}</td>
                            <td class="text-center">{{$spliter ? $spliter->nombre : 'N/A'}}</td>
                            <td class="text-center">{{$caja_nap->cant_puertos}}</td>
                            <td class="text-center">{{$caja_nap->contarPuertosDisponibles()}}</td>
                            <td class="text-center">{{$caja_nap->ubicacion}}</td>
                            <td class="text-center">{{$caja_nap->coordenadas}}</td>
                            <td class="text-center"><span class="text-{{$caja_nap->status('true')}}"><b>{{$caja_nap->status()}}</b></span></td>
                            @if($caja_nap->updated_by)
                            <td class="text-center">Por {{$caja_nap->updated_by()->nombres ?? 'N/A'}}<br>{{date('d-m-Y g:i:s A', strtotime($caja_nap->updated_at))}}</td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @if($caja_nap->descripcion)
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header text-white">
                    <h5 class="mb-0">Descripción</h5>
                </div>
                <div class="card-body">
                    <p>{{$caja_nap->descripcion}}</p>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection
