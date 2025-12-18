@extends('layouts.app')

@section('boton')
    <a href="javascript:abrirFiltrador()" class="btn btn-info btn-sm my-1" id="boton-filtrar"><i
            class="fas fa-search"></i>Filtrar</a>
@endsection

@section('content')
    @if (Session::has('success'))
        <div class="alert alert-success">
            {{ Session::get('success') }}
        </div>
        <script type="text/javascript">
            setTimeout(function() {
                $('.alert').hide();
                $('.active_table').attr('class', ' ');
            }, 5000);
        </script>
    @endif

    @if (Session::has('error'))
        <div class="alert alert-danger">
            {{ Session::get('error') }}
        </div>

        <script type="text/javascript">
            setTimeout(function() {
                $('.alert').hide();
                $('.active_table').attr('class', ' ');
            }, 8000);
        </script>
    @endif

    @if (Session::has('danger'))
        <div class="alert alert-danger">
            {{ Session::get('danger') }}
        </div>
        <script type="text/javascript">
            setTimeout(function() {
                $('.alert').hide();
                $('.active_table').attr('class', ' ');
            }, 5000);
        </script>
    @endif

    @if (Session::has('message_denied'))
        <div class="alert alert-danger" role="alert">
            {{ Session::get('message_denied') }}
            @if (Session::get('errorReason'))<br> <strong>Razon(es): <br></strong>
                @if (count(Session::get('errorReason')) > 1)
                    @php $cont = 0 @endphp
                    @foreach (Session::get('errorReason') as $error)
                        @php $cont = $cont + 1; @endphp
                        {{ $cont }} - {{ $error }} <br>
                    @endforeach
                @endif
            @endif
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (Session::has('message_success'))
        <div class="alert alert-success" role="alert">
            {{ Session::get('message_success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form id="form-dinamic-action" method="GET">
        <div class="container-fluid mb-3" id="form-filter">
            <fieldset>
                <legend>Filtro de BÃºsqueda</legend>
                <div class="card shadow-sm border-0">
                    <div class="card-body py-3" style="background: #f9f9f9;">
                        <div class="row">
                            <div class="col-md-4 pl-1 pt-1">
                                <select title="Olt a buscar" class="form-control selectpicker" id="olt_id" name="olt_id"
                                    data-size="5" data-live-search="true" onchange="oltChange(this.value)">
                                    @foreach ($olts as $olt)
                                        <option value="{{ $olt['id'] }}"
                                            {{ $olt['id'] == $olt_default ? 'selected' : '' }}>{{ $olt['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
            </fieldset>
        </div>
    </form>

    <div class="row card-description">
        <div class="col-md-12">
            <table class="table table-striped table-hover" id="table-general">
                <thead class="thead-dark">
                    <tr>
                        <th width='3%'>PON Type</th>
                        <th width='3%'>Board</th>
                        <th width='3%'>Port</th>
                        <th width='1%'>Pon Description</th>
                        <th width='3%'>SN</th>
                        <th width='3%'>Type</th>
                        <th width='3%'>Estatus</th>
                        <th width='3%'>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data populated by JavaScript via WebSocket -->
                    {{-- <tr>
                        <td colspan="8" class="text-center text-muted">
                            No se encontraron ONUs sin autorizar
                        </td>
                    </tr> --}}
                </tbody>
            </table>
        </div>
    </div>
    {{-- Pasar facility al front --}}
    <script>
        window.ADMINOLT_FACILITY_VLANS = @json($facility_vlans ?? null);
        window.ADMINOLT_FACILITY_UNAUTHORIZED = @json($facility_unauthorized ?? null);
    </script>

@endsection

@section('scripts')
    {{-- AdminOLT WS base (usa el host real, ej: novalinksp.adminolt.co) --}}
    <input id="WEBSOCKET_URI" type="hidden" value="wss://{{ $ws_host }}/ws/">

    {{-- Librerías AdminOLT (Required) --}}
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script type="text/javascript" src="https://adminolt.com/static/js/ws4redis.js"></script>
    <script type="text/javascript" src="https://adminolt.com/static/js/websocket-adminolt.js"></script>

    <script>
        // =========================
        // 1) CONFIG / DEBUG
        // =========================
        (function() {
            // Facilities que te llegan desde el backend (API AdminOLT)
            window.ADMINOLT_FACILITY_VLANS = "{{ $vlans_facility }}";
            window.ADMINOLT_FACILITY_UNAUTHORIZED = "{{ $onus_facility }}";

            // OLT actual
            window.ADMINOLT_OLT_DEFAULT = "{{ $olt_default }}";

            // Cola secuencial
            window.ADMINOLT_QUEUE = [];
            window.ADMINOLT_CURRENT = null;

            function adminoltQueueInit() {
                window.ADMINOLT_QUEUE = [];

                if (window.ADMINOLT_FACILITY_VLANS) {
                    window.ADMINOLT_QUEUE.push({
                        label: 'VLANS',
                        facility: window.ADMINOLT_FACILITY_VLANS
                    });
                }
                if (window.ADMINOLT_FACILITY_UNAUTHORIZED) {
                    window.ADMINOLT_QUEUE.push({
                        label: 'UNAUTHORIZED',
                        facility: window.ADMINOLT_FACILITY_UNAUTHORIZED
                    });
                }
            }

            function adminoltStartNext() {
                if (typeof task_adminolt_ajax !== 'function') {
                    console.error('AdminOLT: task_adminolt_ajax no está disponible (websocket-adminolt.js no cargó).');
                    return;
                }

                if (!window.ADMINOLT_QUEUE.length) {
                    // Fin
                    if (typeof Swal !== 'undefined') {
                        try {
                            Swal.close();
                        } catch (e) {}
                    }
                    return;
                }

                window.ADMINOLT_CURRENT = window.ADMINOLT_QUEUE.shift();
                // Llama a la librería oficial (NO new WebSocket)
                task_adminolt_ajax(window.ADMINOLT_CURRENT.facility);
            }

            // =========================
            // 2) CALLBACKS OFICIALES
            // =========================
            // Nota: websocket-adminolt.js invoca task_custom_done(data)
            window.task_custom_done = function(data) {
                console.log('✅ AdminOLT DONE:', {
                    label: window.ADMINOLT_CURRENT ? window.ADMINOLT_CURRENT.label : null,
                    facility: window.ADMINOLT_CURRENT ? window.ADMINOLT_CURRENT.facility : null,
                    data: data
                });

                try {
                    // 1) Procesar según label (lo que tengas realmente)
                    if (window.ADMINOLT_CURRENT && window.ADMINOLT_CURRENT.label === 'UNAUTHORIZED') {
                        var onus = Array.isArray(data) ? data : [];
                        populateOnusTable(onus);
                    }

                    if (window.ADMINOLT_CURRENT && window.ADMINOLT_CURRENT.label === 'VLANS') {
                        var vlans = Array.isArray(data) ? data : [];
                        populateVlansSelect(vlans);
                    }
                } catch (e) {
                    console.error('Error procesando data de AdminOLT:', e);
                }

                // 2) Forzar una ONU de ejemplo si la tabla quedó vacía
                try {
                    var tbody = document.querySelector('#table-general tbody');
                    if (tbody && !tbody.children.length) {
                        console.log('⚠️ Tabla vacía, inyectando ONU DEMO');

                        var demoOnus = [{
                            pon_type: 'gpon',
                            board: 14,
                            port: 7,
                            pon_description: 'DEMO (sin ONUs reales)',
                            sn: 'DEMO_ONU_SN_001',
                            onu_type_name: 'SML-704GWT-DAX',
                            is_disabled: 0,
                            olt_id: window.ADMINOLT_OLT_DEFAULT
                        }];

                        populateOnusTable(demoOnus);
                    }
                } catch (e) {
                    console.error('Error inyectando demo ONU:', e);
                }

                // 3) Continuar con la cola
                adminoltStartNext();
            };

            // Sin ruido (opcional)
            window.task_custom_message = function() {};
            window.task_custom_error = function() {};

            // =========================
            // 3) UI HELPERS
            // =========================
            window.populateOnusTable = window.populateOnusTable || function(onus) {

                console.log('AQUI TAMPOCO ESTA ENTRANDO');
                const tableBody = document.querySelector('#table-general tbody');
                tableBody.innerHTML = '';
                if (!Array.isArray(onus) || onus.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="8" class="text-center">No ONUs found</td></tr>';
                    return;
                }

                let index = 0;
                onus.forEach((onu) => {
                    if (String(onu.olt_id) == String(window.ADMINOLT_OLT_DEFAULT)) {
                        const row = document.createElement('tr');
                        row.id = `olt_${index}`;
                        row.innerHTML = `
                            <td>${onu.pon_type || 'N/A'}</td>
                            <td>${onu.board || 'N/A'}</td>
                            <td>${onu.port || 'N/A'}</td>
                            <td>${onu.pon_description || ''}</td>
                            <td>${onu.sn || ''}</td>
                            <td>${onu.onu_type_name || 'Unknown'}</td>
                            <td>${onu.is_disabled == 0 ? 'Activo' : 'Inactivo'}</td>
                            <td>${onu.is_disabled == 0 ? `<a href="#" onclick="formAuthorizeOnu(${index}); return false;">Authorize</a>` : ''}</td>
                        `;
                        tableBody.appendChild(row);
                        index++;
                    }
                });

                if (tableBody.children.length === 0) {
                    tableBody.innerHTML =
                        '<tr><td colspan="8" class="text-center">No ONUs found for this OLT</td></tr>';
                }
            };

            window.populateVlansSelect = window.populateVlansSelect || function(vlans) {
                const vlanSelect = document.querySelector('#vlan_select');
                if (!vlanSelect) return;

                vlanSelect.innerHTML = '<option value="">-- Selecciona una VLAN --</option>';
                if (!Array.isArray(vlans)) return;

                vlans.forEach((vlan) => {
                    const option = document.createElement('option');
                    option.value = vlan.id || vlan.vlan;
                    option.textContent = `VLAN ${vlan.vlan || ''} - ${vlan.name || ''}`;
                    vlanSelect.appendChild(option);
                });
            };

            // =========================
            // 4) START
            // =========================
            $(document).ready(function() {
                // Loading (SweetAlert “viejo”: usa type/onBeforeOpen)
                if (typeof Swal !== 'undefined' && Swal.fire) {
                    try {
                        Swal.fire({
                            title: 'Cargando datos...',
                            text: 'Conectando con AdminOLT',
                            type: 'info',
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            onBeforeOpen: function() {
                                if (Swal.showLoading) Swal.showLoading();
                            }
                        });
                    } catch (e) {}
                }

                adminoltQueueInit();
                adminoltStartNext();
            });
        })();
    </script>

    {{-- Tus funciones existentes (si ya las tienes arriba, puedes omitir este bloque) --}}
    <script>
        function formAuthorizeOnu(index) {
            let row = document.getElementById('olt_' + index);
            if (!row) return;

            let ponType = row.cells[0].innerText;
            let board = row.cells[1].innerText;
            let port = row.cells[2].innerText;
            let ponDescription = row.cells[3].innerText;
            let sn = row.cells[4].innerText; 
            let onuTypeName = row.cells[5].innerText;
            let status = row.cells[6].innerText;
            let olt_id = document.getElementById("olt_id").value;

            let url =
                `{{ route('olt.form-authorized-onuAdminOlt') }}?ponType=${encodeURIComponent(ponType)}&board=${encodeURIComponent(board)}&port=${encodeURIComponent(port)}&ponDescription=${encodeURIComponent(ponDescription)}&sn=${encodeURIComponent(sn)}&onuTypeName=${encodeURIComponent(onuTypeName)}&status=${encodeURIComponent(status)}&olt_id=${olt_id}`;
            window.location.href = url;
        }

        function abrirFiltrador() {
            if ($('#form-filter').hasClass('d-none')) {
                $('#boton-filtrar').html('<i class="fas fa-times"></i> Cerrar');
                $('#form-filter').removeClass('d-none');
            } else {
                $('#boton-filtrar').html('<i class="fas fa-search"></i> Filtrar');
                cerrarFiltrador();
            }
        }

        function cerrarFiltrador() {
            $('#form-filter').addClass('d-none');
            $('#boton-filtrar').html('<i class="fas fa-search"></i> Filtrar');
        }

        function oltChange(id) {
            if (typeof Swal !== 'undefined' && Swal.fire) {
                try {
                    Swal.fire({
                        title: 'Cargando...',
                        text: 'Por favor espera mientras se procesa la solicitud.',
                        type: 'info',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        onBeforeOpen: function() {
                            if (Swal.showLoading) Swal.showLoading();
                        }
                    });
                } catch (e) {}
            }

            let url = `{{ route('olt.unconfiguredAdminOLT') }}?olt_id=${id}`;
            window.location.href = url;
        }
    </script>
@endsection
