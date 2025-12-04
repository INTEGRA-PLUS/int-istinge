@extends('layouts.app')
@section('content')
	<form method="POST" action="{{ route('spliter.store') }}" style="padding: 2% 3%;" role="form" class="forms-sample" novalidate id="form-banco" >
	    @csrf
	    <div class="row">
	        <div class="col-md-4 form-group">
	            <label class="control-label">Nombre del Spliter<span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="nombre" name="nombre"  required="" value="{{old('nombre')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('nombre') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-4 form-group">
	            <label class="control-label">Ubicaci®Æn<span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="ubicacion" name="ubicacion"  required="" value="{{old('ubicacion')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('nombre') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-4 form-group">
	            <label class="control-label">Coordenadas<span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="coordenadas" name="coordenadas"  required="" value="{{old('coordenadas')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('nombre') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-4 form-group">
	            <label class="control-label">Numero de Salida<span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="num_salida" name="num_salida"  required="" value="{{old('num_salida')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('num_salida') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-4 form-group">
	            <label class="control-label">Numero de Cajas Naps<span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="num_cajas_naps" name="num_cajas_naps"  required="" value="{{old('num_cajas_naps')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('num_cajas_naps') }}</strong>
	            </span>
	        </div>
	        <div class="col-md-4 form-group">
	            <label class="control-label">Caja Disponibles<span class="text-danger">*</span></label>
	            <input type="text" class="form-control"  id="cajas_disponible" name="cajas_disponible"  required="" value="{{old('cajas_disponible')}}" maxlength="200">
	            <span class="help-block error">
	                <strong>{{ $errors->first('cajas_disponible') }}</strong>
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