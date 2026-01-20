@extends('layouts.app')

@section('boton')
    <a href="javascript:abrirFiltrador()" class="btn btn-info btn-sm my-1" id="boton-filtrar">
        <i class="fas fa-search"></i>Filtrar
    </a>
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
                <legend>Filtro de Búsqueda</legend>
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
                        <th>PON Type</th>
                        <th>Board</th>
                        <th>Port</th>
                        <th>Pon Description</th>
                        <th>SN</th>
                        <th>Type</th>
                        <th>Estatus</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        window.ADMINOLT_FACILITY_UNAUTHORIZED = @json($facility_unauthorized ?? null);
    </script>
@endsection

@section('scripts')
    <input id="WEBSOCKET_URI" type="hidden" value="wss://{{ $ws_host }}/ws/">

    {{-- Librerías AdminOLT --}}
    <script type="text/javascript" src="https://adminolt.com/static/js/ws4redis.js"></script>
    <script type="text/javascript" src="https://adminolt.com/static/js/websocket-adminolt.js"></script>

    {{-- DataTables Responsive (asegúrate de tener estos en tu layout o aquí) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <script>
        (function() {
            window.ADMINOLT_FACILITY_UNAUTHORIZED = "{{ $onus_facility }}";
            window.ADMINOLT_OLT_DEFAULT = "{{ $olt_default }}";
            window.ADMINOLT_QUEUE = [];
            window.ADMINOLT_CURRENT = null;

            // Guardamos los datos de ONUs para accederlos desde formAuthorizeOnu
            window.ONUS_DATA = [];

            function adminoltQueueInit() {
                window.ADMINOLT_QUEUE = [];
                if (window.ADMINOLT_FACILITY_UNAUTHORIZED) {
                    window.ADMINOLT_QUEUE.push({
                        label: 'UNAUTHORIZED',
                        facility: window.ADMINOLT_FACILITY_UNAUTHORIZED
                    });
                }
            }

            function adminoltStartNext() {
                if (typeof task_adminolt_ajax !== 'function') {
                    console.error('AdminOLT: task_adminolt_ajax no está disponible.');
                    return;
                }

                if (!window.ADMINOLT_QUEUE.length) {
                    if (typeof Swal !== 'undefined') {
                        try {
                            Swal.close();
                        } catch (e) {}
                    }
                    return;
                }

                window.ADMINOLT_CURRENT = window.ADMINOLT_QUEUE.shift();
                task_adminolt_ajax(window.ADMINOLT_CURRENT.facility);
            }

            window.task_custom_done = function(data) {
                try {
                    if (window.ADMINOLT_CURRENT && window.ADMINOLT_CURRENT.label === 'UNAUTHORIZED') {
                        var onus = [];

                        if (Array.isArray(data)) {
                            onus = data;
                        } else if (data && data.unauthorized_onus && typeof data.unauthorized_onus === 'object') {
                            var entries = Object.entries(data.unauthorized_onus);

                            onus = entries.map(function([snKey, o]) {
                                o = o || {};
                                var board = o.slot;
                                var port = o.port;

                                if ((board === undefined || board === null || board === '') && typeof o
                                    .interface === 'string') {
                                    var parts = o.interface.split('/');
                                    if (parts.length >= 3) board = parts[1];
                                }
                                if ((port === undefined || port === null || port === '') && typeof o
                                    .interface === 'string') {
                                    var parts2 = o.interface.split('/');
                                    if (parts2.length >= 3) port = parts2[2];
                                }

                                return {
                                    pon_type: o.pon_type || 'gpon',
                                    board: board || 'N/A',
                                    port: port || 'N/A',
                                    pon_description: o.interface || '',
                                    sn: o.sn || snKey,
                                    onu_type_name: o.onu_type || o.onu_type_name || '',
                                    is_disabled: 0,
                                    olt_id: o.olt_id,
                                    chasis: o.chasis || '0',
                                    interface_id: o.interface_id || ''
                                };
                            });
                        }

                        // console.log('[AdminOLT] Parsed unauthorized onus:', onus);
                        populateOnusTable(onus);
                    }
                } catch (e) {
                    console.error('Error procesando data de AdminOLT:', e);
                }

                adminoltStartNext();
            };

            window.task_custom_message = function() {};
            window.task_custom_error = function() {};

            // ===== NUEVA FUNCIÓN CON API DATATABLES =====
            window.populateOnusTable = function(onus) {
                const dt = window.DT_ONUS;
                if (!dt) {
                    console.error('DataTable no inicializado');
                    return;
                }

                const filtered = (Array.isArray(onus) ? onus : [])
                    .filter(o => String(o.olt_id) === String(window.ADMINOLT_OLT_DEFAULT));

                // Guardamos los datos filtrados globalmente
                window.ONUS_DATA = filtered;

                dt.clear();

                if (!filtered.length) {
                    dt.draw();
                    return;
                }

                const rows = filtered.map((onu, index) => ([
                    onu.pon_type || 'N/A',
                    onu.board || 'N/A',
                    onu.port || 'N/A',
                    onu.pon_description || '',
                    onu.sn || '',
                    onu.onu_type_name || 'Unknown',
                    (onu.is_disabled == 0 ? 'Activo' : 'Inactivo'),
                    (onu.is_disabled == 0 ?
                        `<a href="#" data-index="${index}" class="btn-authorize">Authorize</a>` :
                        '')
                ]));

                dt.rows.add(rows).draw();

                // Recalcula responsive
                if (dt.responsive) {
                    dt.responsive.recalc();
                }
                dt.columns.adjust();
            };

            // ===== INICIALIZAR DATATABLE UNA SOLA VEZ =====
            $(document).ready(function() {
                // ===== VERIFICAR SI YA EXISTE ANTES DE INICIALIZAR =====
                if ($.fn.DataTable.isDataTable('#table-general')) {
                    // Si ya existe, obtener la instancia existente
                    window.DT_ONUS = $('#table-general').DataTable();
                    // console.log('DataTable ya estaba inicializado, usando instancia existente');
                } else {
                    // Si no existe, inicializar con Responsive
                    window.DT_ONUS = $('#table-general').DataTable({
                        responsive: true,
                        autoWidth: false,
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
                        },
                        columnDefs: [{
                                targets: -1, // Action (última columna)
                                orderable: false,
                                searchable: false,
                                responsivePriority: 10001, // Se oculta primero
                                className: 'text-center'
                            },
                            {
                                targets: 0,
                                responsivePriority: 1
                            }, // PON Type siempre visible
                            {
                                targets: 4,
                                responsivePriority: 2
                            } // SN siempre visible
                        ]
                    });
                    // console.log('DataTable inicializado por primera vez');
                }

                // Event delegation para links Authorize (funciona con responsive)
                $('#table-general').on('click', '.btn-authorize', function(e) {
                    e.preventDefault();
                    const index = $(this).data('index');
                    formAuthorizeOnu(index);
                });

                // Loading
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

    <script>
        // ===== FUNCIÓN ACTUALIZADA (usa datos globales) =====
        function formAuthorizeOnu(index) {
            const onu = window.ONUS_DATA[index];
            if (!onu) {
                console.error('ONU no encontrada en índice:', index);
                return;
            }

            const olt_id = document.getElementById("olt_id").value;

            let url = `{{ route('olt.form-authorized-onuAdminOlt') }}?` +
                `ponType=${encodeURIComponent(onu.pon_type)}` +
                `&board=${encodeURIComponent(onu.board)}` +
                `&port=${encodeURIComponent(onu.port)}` +
                `&ponDescription=${encodeURIComponent(onu.pon_description)}` +
                `&sn=${encodeURIComponent(onu.sn)}` +
                `&onuTypeName=${encodeURIComponent(onu.onu_type_name)}` +
                `&status=${encodeURIComponent(onu.is_disabled == 0 ? 'Activo' : 'Inactivo')}` +
                `&olt_id=${olt_id}` +
                `&chasis=${encodeURIComponent(onu.chasis || '0')}` + 
                `&interface_id=${encodeURIComponent(onu.interface_id || '')}`;

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
