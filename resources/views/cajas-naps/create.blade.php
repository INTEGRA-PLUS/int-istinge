@extends('layouts.app')
@section('content')
	<form method="POST" action="{{ route('caja.naps.store') }}" style="padding: 2% 3%;" role="form" class="forms-sample" novalidate id="form-banco" >
	    @csrf
	    <div class="row">
	        <div class="col-md-4 form-group">
	            <label class="control-label">Nombre de la caja <span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="nombre" name="nombre"  required="" value="{{old('nombre')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('nombre') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-4 form-group">
                <label class="control-label">Spliter Asociado <span class="text-danger">*</span></label>
                <select class="form-control" id="spliter_asociado" name="spliter_asociado" required="">
                    <option value="">Selecciona un Spliter</option>
                    @foreach($spliters as $splitter)
                        <option value="{{ $splitter->id }}" {{ old('spliter_asociado') == $splitter->id ? 'selected' : '' }}>
                            {{ $splitter->nombre }} {{-- Ajusta el atributo seg®≤n el nombre real en tu modelo --}}
                        </option>
                    @endforeach
                </select>
                <span class="help-block error">
                    <strong>{{ $errors->first('spliter_asociado') }}</strong>
                </span>
            </div>
	        <div class="col-md-4 form-group">
	            <label class="control-label">Nombre de la caja <span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="nombre" name="nombre"  required="" value="{{old('nombre')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('nombre') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-4 form-group">
	            <label class="control-label">Cantidad de Puertos <span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="cant_puertos" name="cant_puertos"  required="" value="{{old('cant_puertos')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('cant_puertos') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-4 form-group">
	            <label class="control-label">Ubicaci®Æn <span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="ubicacion" name="ubicacion"  required="" value="{{old('ubicacion')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('ubicacion') }}</strong>
	            </span>
	        </div>
	        
	         <div class="col-md-4 form-group">
	            <label class="control-label">Coordenadas<span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="coordenadas" name="coordenadas"  required="" value="{{old('coordenadas')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('coordenadas') }}</strong>
	            </span>
	        </div>
	        
	          <div class="col-md-4 form-group">
	            <label class="control-label">Cantidad de Puertos disponibles<span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="caja_naps_disponible" name="caja_naps_disponible"  required="" value="{{old('caja_naps_disponible')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('caja_naps_disponible') }}</strong>
	            </span>
	        </div>
	        
	        <div class="col-md-4 form-group">
	            <label class="control-label">Estado <span class="text-danger">*</span></label>
	            <select class="form-control selectpicker" name="status" id="status" title="Seleccione" required="">
	                <option value="1" selected>Habilitado</option>
	                <option value="0">Deshabilitado</option>
	            </select>
	            <span class="help-block error">
	                <strong>{{ $errors->first('status') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-12 form-group">
	            <label class="control-label">Descripci√≥n</label>
	            <textarea  class="form-control form-control-sm" name="descripcion" rows="3">{{old('descripcion')}}</textarea>
	        </div>
	    </div>
	    <small>Los campos marcados con <span class="text-danger">*</span> son obligatorios</small>
	    <hr>
	    <div class="row" >
	        <div class="col-sm-12" style="text-align: right;  padding-top: 1%;">
	            <a href="{{route('nodos.index')}}" class="btn btn-outline-secondary">Cancelar</a>
	            <button type="submit" id="submitcheck" onclick="submitLimit(this.id)" class="btn btn-success">Guardar</button>
	        </div>
	    </div>
	</form>
@endsection