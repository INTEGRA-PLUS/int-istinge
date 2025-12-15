@extends('layouts.app')
@section('content')
	<form method="POST" action="{{ route('caja.naps.update', $caja_nap->id) }}" style="padding: 2% 3%;" role="form" class="forms-sample" novalidate id="form-banco" >
	    @csrf
	    <input name="_method" type="hidden" value="PATCH">
	    <div class="row">
	        <div class="col-md-4 form-group">
	            <label class="control-label">Nombre de la caja <span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="nombre" name="nombre"  required="" value="{{$caja_nap->nombre}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('nombre') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-4 form-group">
                <label class="control-label">Spliter Asociado <span class="text-danger">*</span></label>
                <select class="form-control selectpicker" id="spliter_asociado" name="spliter_asociado" required="" title="Seleccione" data-live-search="true" data-size="5">
                    <option value="">Selecciona un Spliter</option>
                    @foreach($spliters as $splitter)
                        <option value="{{ $splitter->id }}" {{ $caja_nap->spliter_asociado == $splitter->id ? 'selected' : '' }}>
                            {{ $splitter->nombre }}
                        </option>
                    @endforeach
                </select>
                <span class="help-block error">
                    <strong>{{ $errors->first('spliter_asociado') }}</strong>
                </span>
            </div>
	        <div class="col-md-4 form-group">
	            <label class="control-label">Cantidad de Puertos <span class="text-danger">*</span></label>
	            <input type="number" class="form-control"  id="cant_puertos" name="cant_puertos"  required="" value="{{$caja_nap->cant_puertos}}" min="1">
	            <span class="help-block error">
	                <strong>{{ $errors->first('cant_puertos') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-4 form-group">
	            <label class="control-label">Ubicación <span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="ubicacion" name="ubicacion"  required="" value="{{$caja_nap->ubicacion}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('ubicacion') }}</strong>
	            </span>
	        </div>

	         <div class="col-md-4 form-group">
	            <label class="control-label">Coordenadas<span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="coordenadas" name="coordenadas"  required="" value="{{$caja_nap->coordenadas}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('coordenadas') }}</strong>
	            </span>
	        </div>

	          <div class="col-md-4 form-group">
	            <label class="control-label">Cantidad de Puertos disponibles<span class="text-danger">*</span></label>
	            <input type="number" class="form-control"  id="caja_naps_disponible" name="caja_naps_disponible"  required="" value="{{$caja_nap->caja_naps_disponible}}" min="0">
	            <span class="help-block error">
	                <strong>{{ $errors->first('caja_naps_disponible') }}</strong>
	            </span>
	        </div>

	        <div class="col-md-4 form-group">
	            <label class="control-label">Estado <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="status" id="status" title="Seleccione" required="">
	                <option value="1" {{ ($caja_nap->status == 1) ? 'selected' : '' }}>Habilitado</option>
	                <option value="0" {{ ($caja_nap->status == 0) ? 'selected' : '' }}>Deshabilitado</option>
	            </select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('status') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-12 form-group">
	            <label class="control-label">Descripción</label>
	            <textarea  class="form-control form-control-sm" name="descripcion" rows="3">{{$caja_nap->descripcion}}</textarea>
	        </div>
	    </div>
	    <small>Los campos marcados con <span class="text-danger">*</span> son obligatorios</small>
	    <hr>
	    <div class="row" >
	        <div class="col-sm-12" style="text-align: right;  padding-top: 1%;">
	            <a href="{{route('caja.naps.index')}}" class="btn btn-outline-secondary">Cancelar</a>
	            <button type="submit" id="submitcheck" onclick="submitLimit(this.id)" class="btn btn-success">Guardar</button>
	        </div>
	    </div>
	</form>
@endsection
