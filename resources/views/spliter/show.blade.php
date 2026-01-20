@extends('layouts.app')

@section('boton')
    @if(auth()->user()->modo_lectura())
	    <div class="alert alert-warning text-left" role="alert">
	        <h4 class="alert-heading text-uppercase">Integra Colombia: Suscripción Vencida</h4>
	       <p>Si desea seguir disfrutando de nuestros servicios adquiera alguno de nuestros planes.</p>
           <p>Medios de pago Nequi: 3026003360 Cuenta de ahorros Bancolombia 42081411021 CC 1001912928 Ximena Herrera representante legal. Adjunte su pago para reactivar su membresía</p>
	    </div>
	@else
    <a href="{{route('spliter.index')}}" class="btn btn-outline-danger btn-sm"><i class="fas fa-backward"></i> Regresar</a>
    @endif
@endsection

@section('content')
	<style>
		.bg-th{
	        background: {{Auth::user()->rol > 1 ? Auth::user()->empresa()->color:''}} !important;
	        border-color: {{Auth::user()->rol > 1 ? Auth::user()->empresa()->color:''}} !important;
	        color: #fff !important;
	    }
	</style>

    <div class="row card-description">
		<div class="col-md-12">
			<div class="table-responsive">
				<table class="table table-striped table-bordered table-sm info">
					<tbody>
						<tr>
							<th class="bg-th text-center" colspan="2" style="font-size: 1em;"><strong>INFORMACIÓN DEL SPLITER</strong></th>
						</tr>
                        <tr>
                            <th width="20%">Nombre del Spliter</th>
                            <td>{{ $spliter->nombre }}</td>
                        </tr>
                        @if($spliter->ubicacion)
                        <tr>
                            <th>Ubicación</th>
                            <td>{{ $spliter->ubicacion }}</td>
                        </tr>
                        @endif
                        @if($spliter->coordenadas)
                        <tr>
                            <th>Coordenadas</th>
                            <td>{{ $spliter->coordenadas }}</td>
                        </tr>
                        @endif
                        @if($spliter->num_salida)
                        <tr>
                            <th>Número de Salidas</th>
                            <td>{{ $spliter->num_salida }}</td>
                        </tr>
                        @endif
                        @if($spliter->num_cajas_naps)
                        <tr>
                            <th>Número de Cajas NAPs</th>
                            <td>{{ $spliter->num_cajas_naps }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Cajas Disponibles</th>
                            <td>{{ $spliter->cajas_disponible }}</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>
                                <strong class="text-{{$spliter->status('true')}}">{{$spliter->status()}}</strong>
                            </td>
                        </tr>
                        @if($spliter->descripcion)
                        <tr>
                            <th>Descripción</th>
                            <td>{{ $spliter->descripcion }}</td>
                        </tr>
                        @endif
                        @if($spliter->created_by)
                        <tr>
                            <th>Registrado por</th>
                            <td>{{ optional($spliter->created_by())->name }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Fecha de Registro</th>
                            <td>{{date('d-m-Y g:i:s A', strtotime($spliter->created_at))}}</td>
                        </tr>
                        @if($spliter->updated_by)
                        <tr>
                            <th>Última Actualización por</th>
                            <td>{{ optional($spliter->updated_by())->name }}</td>
                        </tr>
                        @endif
                        @if($spliter->updated_at)
                        <tr>
                            <th>Fecha de Última Actualización</th>
                            <td>{{date('d-m-Y g:i:s A', strtotime($spliter->updated_at))}}</td>
                        </tr>
                        @endif
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection
