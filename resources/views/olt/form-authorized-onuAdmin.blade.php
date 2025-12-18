@extends('layouts.app')
@section('content')
    <form method="POST" action="{{ route('olt.authorized-onus') }}" style="padding: 2% 3%;" role="form" class="forms-sample"
        novalidate id="form-retencion">
        {{ csrf_field() }}
        <div class="row">
            <div class="col-md-3 form-group">
                <label class="control-label">OLT <span class="text-danger">*</span></label>
                <select class="form-control selectpicker" data-live-search="true" data-size="5" name="olt_id_disabled"
                    id="olt_id" disabled>
                    @foreach ($olts as $olt)
                        <option value="{{ $olt['id'] }}" {{ $oltDefault == $olt['id'] ? 'selected' : '' }}>
                            {{ $olt['name'] }}</option>
                    @endforeach
                </select>

                <input type="hidden" name="olt_id" value="{{ $oltDefault }}">
                <span class="help-block error">
                    <strong>{{ $errors->first('olt_id') }}</strong>
                </span>
            </div>

            <div class="col-md-3 form-group">
                <label class="control-label">Pon Type <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="pon_type" id="pon_type" value="{{ $request->ponType }}"
                    readonly>
                <span class="help-block error">
                    <strong>{{ $errors->first('pon_type') }}</strong>
                </span>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">Board <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="board" id="board" value="{{ $request->board }}"
                    readonly>
                <span class="help-block error">
                    <strong>{{ $errors->first('board') }}</strong>
                </span>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">Port <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="port" id="port" value="{{ $request->port }}"
                    readonly>
                <span class="help-block error">
                    <strong>{{ $errors->first('port') }}</strong>
                </span>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">Sn <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="sn" id="sn" value="{{ $request->sn }}"
                    readonly>
                <span class="help-block error">
                    <strong>{{ $errors->first('sn') }}</strong>
                </span>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">ONU TYpe <span class="text-danger">*</span></label>
                <select class="form-control selectpicker" data-live-search="true" data-size="5" name="onu_type"
                    id="onu_type">
                    @foreach ($onu_types as $type)
                        <option value="{{ $type['onu_type'] }}">{{ $type['onu_type'] }}</option>
                    @endforeach
                </select>
                <span class="help-block error">
                    <strong>{{ $errors->first('onu_type') }}</strong>
                </span>
            </div>

            <div class="col-md-3 form-group">
                <label class="control-label">ONU mode </label>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-radio">
                            <label class="form-check-label">
                                <input type="radio" class="form-check-input" name="onu_mode" id="routing"
                                    value="Routing" checked> Routing
                                <i class="input-helper"></i><i class="input-helper"></i></label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-radio">
                            <label class="form-check-label">
                                <input type="radio" class="form-check-input" name="onu_mode" id="bridging"
                                    value="Bridging"> Bridging
                                <i class="input-helper"></i><i class="input-helper"></i></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">User VLAN-ID <span class="text-danger">*</span></label>
                <select class="form-control selectpicker" data-live-search="true" data-size="5" name="user_vlan_id"
                    id="user_vlan_id">
                    <option value="">Cargando VLANs...</option>
                </select>

                <span class="help-block error">
                    <strong>{{ $errors->first('user_vlan_id') }}</strong>
                </span>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">Zone<span class="text-danger">*</span></label>
                <select class="form-control selectpicker" data-live-search="true" data-size="5" name="zone"
                    id="zone">
                    {{-- if (isset($zones) && is_array($zones)){
                    @foreach ($zones as $zone)
                        <option value="{{ $zone['name'] }}" {{ $default_zone == $zone['id'] ? 'selected' : '' }}>
                            {{ $zone['name'] }}</option>
                    @endforeach
                    } --}}

                </select>
                <span class="help-block error">
                    <strong>{{ $errors->first('zone') }}</strong>
                </span>
            </div>

            <div class="col-md-3 form-group">
                <label class="control-label">ODB (Splitter)</label>
                <select class="form-control selectpicker" data-live-search="true" data-size="5" name="odb_splitter"
                    id="odb_splitter">
                    <option value="0">None</option>
                    {{-- @foreach ($odbList as $odbSplitter)
                        @if ($odbSplitter['nr_of_ports'] == null)
                            <option value="{{ $odbSplitter['name'] }}">{{ $odbSplitter['name'] }}</option>
                        @endif
                    @endforeach --}}
                </select>
                <span class="help-block error">
                    <strong>{{ $errors->first('odb_splitter') }}</strong>
                </span>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">ODB Port</label>
                <select class="form-control selectpicker" data-live-search="true" data-size="5" name="odb_port"
                    id="odb_port">
                    <option value="0">None</option>
                    {{-- @foreach ($odbList as $odbPort)
                        @if ($odbPort['nr_of_ports'] != null)
                            <option value="{{ $odbPort['id'] }}">{{ $odbPort['name'] }}</option>
                        @endif
                    @endforeach --}}
                </select>
                <span class="help-block error">
                    <strong>{{ $errors->first('odb_port') }}</strong>
                </span>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">Download speed<span class="text-danger">*</span></label>
                <select class="form-control selectpicker" data-live-search="true" data-size="5" name="download_speed"
                    id="download_speed">
                    {{-- @foreach ($speedProfiles as $speedDownload)
                        @if ($speedDownload['direction'] == 'download')
                            <option value="{{ $speedDownload['name'] }}">{{ $speedDownload['name'] }} -
                                {{ $speedDownload['speed'] }}</option>
                        @endif
                    @endforeach --}}
                </select>
                <span class="help-block error">
                    <strong>{{ $errors->first('download_speed') }}</strong>
                </span>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">Upload speed<span class="text-danger">*</span></label>
                <select class="form-control selectpicker" data-live-search="true" data-size="5" name="upload_speed"
                    id="upload_speed">
                    {{-- @foreach ($speedProfiles as $speedUpload)
                        @if ($speedUpload['direction'] == 'upload')
                            <option value="{{ $speedUpload['name'] }}">{{ $speedUpload['name'] }} -
                                {{ $speedUpload['speed'] }}</option>
                        @endif
                    @endforeach --}}
                </select>
                <span class="help-block error">
                    <strong>{{ $errors->first('upload_speed') }}</strong>
                </span>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">Name<span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="name">
                <span class="help-block error">
                    <strong>{{ $errors->first('upload_speed') }}</strong>
                </span>
            </div>
            <div class="col-md-6 form-group">
                <label class="control-label">Address or comment </label>
                <input type="text" name="address_comment" id="address_comment" class="form-control">
                <span class="help-block error">
                    <strong>{{ $errors->first('address_comment') }}</strong>
                </span>
            </div>
            <div class="col-md-3 form-group">
                <label class="control-label">ONU external ID <span class="text-danger">*</span></label>
                <input type="text" name="onu_external_id" id="onu_external_id" class="form-control"
                    value="{{ $request->sn }}">
                <span class="help-block error">
                    <strong>{{ $errors->first('onu_external_id') }}</strong>
                </span>
            </div>
        </div>

        <small>Los campos marcados con <span class="text-danger">*</span> son obligatorios</small>
        <hr>
        <div class="row">
            <div class="col-sm-12" style="text-align: right;  padding-top: 1%;">
                <a href="{{ route('mikrotik.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" id="submitcheck" onclick="submitLimit(this.id)"
                    class="btn btn-success">Guardar</button>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    <input id="WEBSOCKET_URI" type="hidden" value="wss://{{ $ws_host }}/ws/">

    {{-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> --}}
    <script type="text/javascript" src="https://adminolt.com/static/js/ws4redis.js"></script>
    <script type="text/javascript" src="https://adminolt.com/static/js/websocket-adminolt.js"></script>

    <script>
        window.ADMINOLT_FACILITY_VLANS = "{{ $vlans_facility }}";

        function populateVlansSelect(vlans) {
            var sel = document.getElementById('user_vlan_id');

            if (!sel) return;
            // Limpia opciones
            while (sel.options.length > 0) sel.remove(0);

            sel.add(new Option('-- Selecciona una VLAN --', ''));


            if (!vlans || !vlans.length) {
                sel.options.length = 0;
                sel.add(new Option('No hay VLANs disponibles', ''));
                if (window.jQuery && $.fn && $.fn.selectpicker) {
                    $('#user_vlan_id').selectpicker('refresh');
                }
                return;
            }

            for (var i = 0; i < vlans.length; i++) {
                var v = vlans[i] || {};

                // En tu payload viene id y description (NO vlan)
                var vlanId = (v.id !== undefined && v.id !== null) ? String(v.id) : '';
                var desc = (v.description !== undefined && v.description !== null) ? String(v.description) : '';

                if (!vlanId) continue;

                sel.add(new Option(desc ? (vlanId + ' - ' + desc) : vlanId, vlanId));
            }

            console.log('[VLAN] options count:', sel.options.length);

            // Refresca SOLO este select (si bootstrap-select está cargado)
            if (window.jQuery && $('#user_vlan_id').selectpicker) {
                $('#user_vlan_id').selectpicker('refresh');
            }
        }
        // websocket-adminolt.js llamará esto cuando llegue data
        window.task_custom_done = function(data) {
            console.log('[AdminOLT] VLAN WS data:', data);

            var vlans = [];

            // Caso 1: viene array directo
            if (Array.isArray(data)) {
                vlans = data;
            }
            // Caso 2: viene { vlans: [...] }
            else if (data && Array.isArray(data.vlans)) {
                vlans = data.vlans;
            }
            // Casos alternos por si cambia el wrapper
            else if (data && Array.isArray(data.data)) {
                vlans = data.data;
            } else if (data && Array.isArray(data.results)) {
                vlans = data.results;
            }

            // console.log('[AdminOLT] Parsed vlans:', vlans);
            populateVlansSelect(vlans);
        };

        window.task_custom_message = function() {};
        window.task_custom_error = function(err) {
            console.log('[AdminOLT] WS error:', err);
            populateVlansSelect([]);
        };

        $(document).ready(function() {


            if (!window.ADMINOLT_FACILITY_VLANS) {
                populateVlansSelect([]);
                return;
            }

            if (typeof task_adminolt_ajax !== 'function') {
                console.error('[AdminOLT] task_adminolt_ajax no existe. No cargó websocket-adminolt.js');
                populateVlansSelect([]);
                return;
            }

            task_adminolt_ajax(window.ADMINOLT_FACILITY_VLANS);
        });
    </script>
@endsection
