<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use stdClass;
use DB;
use App\Empresa;
use Carbon\Carbon;
use App\Aviso;
use App\Plantilla;
use Illuminate\Support\Facades\Storage;
use App\Contrato;
use App\Model\Ingresos\Factura;
use Mail;
use App\Mail\NotificacionMailable;
use Config;
use App\ServidorCorreo;
use App\Integracion;
use App\Contacto;
use App\Mikrotik;
use App\GrupoCorte;
use App\Instance;
use App\Services\WapiService;
use Illuminate\Support\Facades\Auth as Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class AvisosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth');
        view()->share(['inicio' => 'master', 'seccion' => 'avisos', 'subseccion' => 'envios', 'title' => 'Envío de Notificaciones', 'icon' =>'fas fa-paper-plane']);
    }


    public function index()
    {
        $this->getAllPermissions(Auth::user()->id);
        $clientes = (Auth::user()->oficina && Auth::user()->empresa()->oficina) ?
        Contacto::leftJoin('factura as f','f.cliente','contactos.id')
        ->where('f.estatus',1)
        ->whereIn('tipo_contacto', [0,2])->where('status', 1)
        ->where('contactos.empresa', Auth::user()->empresa)
        ->where('oficina', Auth::user()->oficina)
        ->orderBy('nombre', 'ASC')->get()
        :
        Contacto::leftJoin('factura as f','f.cliente','contactos.id')
        ->where('f.estatus',1)
        ->whereIn('tipo_contacto', [0,2])
        ->where('status', 1)
        ->where('contactos.empresa', Auth::user()->empresa)
        ->orderBy('nombre', 'ASC')->get();

        return view('avisos.index', compact('clientes'));
    }

    public function create()
    {
        //respuest
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }

    public function sms($id = false)
    {
        $this->getAllPermissions(Auth::user()->id);
        $opcion = 'SMS';

        view()->share(['title' => 'Envío de Notificaciones por '.$opcion, 'icon' => 'fas fa-paper-plane']);
        $plantillas = Plantilla::where('status', 1)->where('tipo', 0)->get();
        $contratos = Contrato::select('contracts.*', 'contactos.id as c_id', 'contactos.nombre as c_nombre', 'contactos.apellido1 as c_apellido1', 'contactos.apellido2 as c_apellido2', 'contactos.nit as c_nit', 'contactos.telefono1 as c_telefono', 'contactos.email as c_email', 'contactos.barrio as c_barrio')
			->join('contactos', 'contracts.client_id', '=', 'contactos.id')
            ->where('contracts.empresa', Auth::user()->empresa)
            ->whereNotNull('contactos.celular');

        if($id){
            $contratos = $contratos->where('contactos.id', $id);
        }

        if(request()->vencimiento){
            $contratos->join('factura', 'factura.contrato_id', '=', 'contracts.id')
                      ->where('factura.vencimiento', date('Y-m-d', strtotime(request()->vencimiento)))
                      ->groupBy('contracts.id');
        }


        $contratos = $contratos->get();

        $servidores = Mikrotik::where('empresa', auth()->user()->empresa)->get();
        $gruposCorte = GrupoCorte::where('empresa', Auth::user()->empresa)->get();

        return view('avisos.envio')->with(compact('plantillas','contratos','opcion','id', 'servidores', 'gruposCorte'));
    }

    public function whatsapp($id = false)
    {
        $this->getAllPermissions(Auth::user()->id);
        $opcion = 'whatsapp';

        view()->share(['title' => 'Envío de Notificaciones por '.$opcion, 'icon' => 'fas fa-paper-plane']);
        $plantillas = Plantilla::where('status', 1)->where('tipo', 2)->get();

        $contratos = Contrato::select('contracts.*', 'contactos.id as c_id',
        'contactos.nombre as c_nombre', 'contactos.apellido1 as c_apellido1',
        'contactos.apellido2 as c_apellido2', 'contactos.nit as c_nit',
        'contactos.telefono1 as c_telefono', 'contactos.email as c_email',
        'contactos.barrio as c_barrio', DB::raw('COALESCE(planes_velocidad.price, 0) + COALESCE(tv.precio + (tv.precio * tv.impuesto / 100), 0) as factura_total'))
			->join('contactos', 'contracts.client_id', '=', 'contactos.id')
            ->leftJoin('planes_velocidad', 'contracts.plan_id', '=', 'planes_velocidad.id')
            ->leftJoin('inventario as tv', 'contracts.servicio_tv', '=', 'tv.id') // Relación con inventario (servicio de TV)
            ->where('contracts.empresa', Auth::user()->empresa)
            ->whereNotNull('contactos.celular');

        if($id){
            $contratos = $contratos->where('contactos.id', $id);
        }

        if(request()->vencimiento) {
            // Construimos la primera consulta con el modelo Contrato
            $contratos = Contrato::leftJoin('facturas_contratos as fc', 'fc.contrato_nro', 'contracts.nro')
                ->leftJoin('factura', 'factura.id', '=', 'fc.factura_id')
                ->leftJoin('contactos', 'contactos.id', '=', 'contracts.client_id') // Asegúrate que existe la relación entre contratos y contactos
                ->leftJoin('planes_velocidad', 'contracts.plan_id', '=', 'planes_velocidad.id')
                ->leftJoin('inventario as tv', 'contracts.servicio_tv', '=', 'tv.id') // Relación con inventario (servicio de TV)
                ->where('factura.vencimiento', date('Y-m-d', strtotime(request()->vencimiento)))
                ->where('factura.estatus', 1)
                ->select('contracts.*',
                         'contactos.id as c_id',
                         'contactos.nombre as c_nombre',
                         'contactos.apellido1 as c_apellido1',
                         'contactos.apellido2 as c_apellido2',
                         'contactos.nit as c_nit',
                         'contactos.telefono1 as c_telefono',
                         'contactos.email as c_email',
                         'contactos.barrio as c_barrio',
                    DB::raw('COALESCE(planes_velocidad.price, 0) + COALESCE(tv.precio + (tv.precio * tv.impuesto / 100), 0) as factura_total'))
                ->orderBy('fc.id', 'desc')
                ->groupBy('contracts.id');

            // Verificamos si la primera consulta no retorna resultados
            if($contratos->get()->isEmpty()) {

                // Si no hay resultados, redefinimos la variable $contratos con la segunda consulta
                $contratos = Contrato::leftJoin('factura as f', 'f.contrato_id', '=', 'contracts.id')
                ->leftJoin('contactos', 'contactos.id', '=', 'contracts.client_id') // Asegúrate que existe la relación entre contratos y contactos
                ->leftJoin('planes_velocidad', 'contracts.plan_id', '=', 'planes_velocidad.id')
                ->leftJoin('inventario as tv', 'contracts.servicio_tv', '=', 'tv.id') // Relación con inventario (servicio de TV)
                ->where('f.vencimiento', date('Y-m-d', strtotime(request()->vencimiento)))
                ->where('f.estatus', 1)
                ->select('contracts.*',
                         'contactos.id as c_id',
                         'contactos.nombre as c_nombre',
                         'contactos.apellido1 as c_apellido1',
                         'contactos.apellido2 as c_apellido2',
                         'contactos.nit as c_nit',
                         'contactos.telefono1 as c_telefono',
                         'contactos.email as c_email',
                         'contactos.barrio as c_barrio',
                    DB::raw('COALESCE(planes_velocidad.price, 0) + COALESCE(tv.precio + (tv.precio * tv.impuesto / 100), 0) as factura_total')) // Obtener el precio de planes_velocidad o de inventario
                ->groupBy('contracts.id')
                ->orderBy('f.id', 'desc');
            }
        }

        $contratos = $contratos->get();

        foreach($contratos as $contrato){

            $facturaContrato = Factura::join('facturas_contratos as fc','fc.factura_id','factura.id')
            ->where('fc.contrato_nro',$contrato->nro)
            ->where('estatus',1)
            ->orderBy('fc.id','desc')
            ->first();

            if($facturaContrato){
                $contrato->factura_id = $facturaContrato->id;
            }else{
                $contrato->factura_id = null;
            }
        }

        $servidores = Mikrotik::where('empresa', auth()->user()->empresa)->get();
        $gruposCorte = GrupoCorte::where('empresa', Auth::user()->empresa)->get();

        return view('avisos.envio')->with(compact('plantillas','contratos','opcion','id', 'servidores', 'gruposCorte'));
    }


    public function email($id = false)
    {
        $this->getAllPermissions(Auth::user()->id);
        $opcion = 'EMAIL';

        view()->share(['title' => 'Envío de Notificaciones por '.$opcion, 'icon' => 'fas fa-paper-plane']);
        $plantillas = Plantilla::where('status', 1)->where('tipo', 1)->get();
        $contratos = Contrato::select('contracts.*', 'contactos.id as c_id', 'contactos.nombre as c_nombre', 'contactos.apellido1 as c_apellido1', 'contactos.apellido2 as c_apellido2', 'contactos.nit as c_nit', 'contactos.telefono1 as c_telefono', 'contactos.email as c_email', 'contactos.barrio as c_barrio')
            ->join('contactos', 'contracts.client_id', '=', 'contactos.id')
           /* ->where('contracts.status', 1) */
            ->where('contracts.empresa', Auth::user()->empresa);

        if($id){
            $contratos = $contratos->where('contactos.id', $id);
        }

        if(request()->vencimiento){
            $contratos->join('factura', 'factura.contrato_id', '=', 'contracts.id')
                      ->where('factura.vencimiento', date('Y-m-d', strtotime(request()->vencimiento)))
                      ->groupBy('contracts.id');
        }

        $contratos = $contratos->get();
        return view('avisos.envio')->with(compact('plantillas','contratos','opcion','id'));
    }

    public function envio_aviso(Request $request){
        Ini_set ('max_execution_time', 500);
        $empresa = Empresa::find(1);
        $type = ''; 
        $mensaje = '';
        $fail = 0;
        $succ = 0;
        $cor = 0;
        $numeros = [];
        $bulk = '';

        // Contadores para WhatsApp con Meta
        $enviadosExito = 0;
        $enviadosFallidos = 0;

        $enviarConMeta = $request->has('enviarConMeta') && $request->enviarConMeta == 'true';

        // Validar que se hayan seleccionado contratos
        if (!$request->contrato || count($request->contrato) == 0) {
            return back()->with('danger', 'Debe seleccionar al menos un cliente para enviar notificaciones');
        }

        for ($i = 0; $i < count($request->contrato); $i++) {
            $contrato = Contrato::find($request->contrato[$i]);

            if($request->isAbierta && $request->type != 'whatsapp'){
                $factura = Factura::where('contrato_id', $contrato->id)->latest()->first();

                if($factura && ($factura->estatus == 3 || $factura->estatus == 4 || $factura->estatus == 0 || $factura->estatus == 2)){
                    continue;
                }
            }

            if ($contrato) {
                $plantilla = Plantilla::find($request->plantilla);

                // ===================================
                // SECCIÓN DE WHATSAPP
                // ===================================
                if($request->type == 'whatsapp'){
                    
                    // Si el envío es con Meta
                    if($enviarConMeta){
                        
                        $wapiService = new WapiService();
                        $instance = Instance::where('company_id', $empresa->id)
                                            ->where('activo', 1)
                                            ->where('meta', 0)
                                            ->first();
                        
                        // Validar instancia solo una vez
                        if($i == 0 && (is_null($instance) || empty($instance))){
                            return back()->with('danger','Instancia no está creada o no está activa');
                        }
                        
                        $contacto = $contrato->cliente();
                        
                        // Validar que el contacto tenga celular
                        if(!$contacto->celular || empty($contacto->celular)){
                            $enviadosFallidos++;
                            \Log::warning('Contrato ' . $contrato->id . ': Sin número de celular');
                            continue;
                        }
                        
                        $nameEmpresa = $empresa->nombre;
                        
                        // Obtener prefijo dinámico
                        $prefijo = '57'; // valor por defecto (Colombia)
                        if (!empty($contacto->fk_idpais)) {
                            $prefijoData = \DB::table('prefijos_telefonicos')
                                ->where('iso2', strtoupper($contacto->fk_idpais))
                                ->first();
                            if ($prefijoData && !empty($prefijoData->phone_code)) {
                                $prefijo = $prefijoData->phone_code;
                            }
                        }
                        
                        $telefonoCompleto = '+' . $prefijo . ltrim($contacto->celular, '0');
                        $tipoPlantilla = is_numeric($request->plantilla) ? strtolower($plantilla->title) : $request->plantilla;
                        
                        try {
                            // Lógica según el tipo de plantilla
                            if($tipoPlantilla == 'suspension' || str_contains($tipoPlantilla, 'suspension de servicio') || str_contains($tipoPlantilla, 'suspensión de servicio') || str_contains($tipoPlantilla, 'suspension')){
                                // ========================================
                                // CASO: SUSPENSIÓN DE SERVICIO
                                // ========================================
                                
                                $body = [
                                    "phone" => $telefonoCompleto,
                                    "templateName" => "suspensionservicios",
                                    "languageCode" => "en",
                                    "components" => [
                                        [
                                            "type" => "body",
                                            "parameters" => [
                                                ["type" => "text", "text" => $nameEmpresa]
                                            ]
                                        ]
                                    ]
                                ];
                                
                                $response = (object) $wapiService->sendTemplate($instance->uuid, $body);
                                
                                // ========================================
                                // VALIDACIÓN CORRECTA DE RESPUESTA
                                // ========================================
                                if (isset($response->scalar)) {
                                    $responseData = json_decode($response->scalar ?? '{}', true);
                                    
                                    $esExitoso = false;
                                    
                                    // Validar respuesta de Meta/WhatsApp Business API
                                    if (isset($responseData['status']) && $responseData['status'] === "success") {
                                        if (isset($responseData['data']['messages'][0]['id']) || 
                                            isset($responseData['data']['messages'][0]['message_status'])) {
                                            $esExitoso = true;
                                        }
                                    }
                                    
                                    // Respuesta directa con message_id
                                    if (isset($responseData['messages'][0]['id']) || 
                                        isset($responseData['message_id']) || 
                                        isset($responseData['messageId'])) {
                                        $esExitoso = true;
                                    }
                                    
                                    // Verificar que NO haya errores reales
                                    if (isset($responseData['error']) && is_array($responseData['error']) && 
                                        (isset($responseData['error']['code']) || isset($responseData['error']['error_code']))) {
                                        $esExitoso = false;
                                    }
                                    
                                    // Si "error" es un string con mensaje de éxito
                                    if (isset($responseData['error']) && is_string($responseData['error']) &&
                                        (str_contains(strtolower($responseData['error']), 'success') || 
                                        str_contains(strtolower($responseData['error']), 'sent successfully'))) {
                                        $esExitoso = true;
                                    }
                                    
                                    if ($esExitoso) {
                                        $enviadosExito++;
                                        \Log::info('WhatsApp enviado a: ' . $telefonoCompleto . ' | Suspensión');
                                    } else {
                                        $enviadosFallidos++;
                                        \Log::error('Error WhatsApp a: ' . $telefonoCompleto . ' | ' . json_encode($responseData));
                                    }
                                } else {
                                    $enviadosFallidos++;
                                    \Log::error('Sin respuesta scalar para: ' . $telefonoCompleto);
                                }
                                
                            } elseif($tipoPlantilla == 'corte' || str_contains($tipoPlantilla, 'corte')){
                                // ========================================
                                // CASO: CORTE
                                // ========================================
                                
                                $body = [
                                    "phone" => $telefonoCompleto,
                                    "templateName" => "cortes",
                                    "languageCode" => "en",
                                    "components" => [
                                        [
                                            "type" => "body",
                                            "parameters" => [
                                                ["type" => "text", "text" => $nameEmpresa]
                                            ]
                                        ]
                                    ]
                                ];
                                
                                $response = (object) $wapiService->sendTemplate($instance->uuid, $body);
                                
                                if (isset($response->scalar)) {
                                    $responseData = json_decode($response->scalar ?? '{}', true);
                                    $esExitoso = false;
                                    
                                    if (isset($responseData['status']) && $responseData['status'] === "success") {
                                        if (isset($responseData['data']['messages'][0]['id']) || 
                                            isset($responseData['data']['messages'][0]['message_status'])) {
                                            $esExitoso = true;
                                        }
                                    }
                                    
                                    if (isset($responseData['messages'][0]['id']) || 
                                        isset($responseData['message_id']) || 
                                        isset($responseData['messageId'])) {
                                        $esExitoso = true;
                                    }
                                    
                                    if (isset($responseData['error']) && is_array($responseData['error']) && 
                                        (isset($responseData['error']['code']) || isset($responseData['error']['error_code']))) {
                                        $esExitoso = false;
                                    }
                                    
                                    if (isset($responseData['error']) && is_string($responseData['error']) &&
                                        (str_contains(strtolower($responseData['error']), 'success') || 
                                        str_contains(strtolower($responseData['error']), 'sent successfully'))) {
                                        $esExitoso = true;
                                    }
                                    
                                    if ($esExitoso) {
                                        $enviadosExito++;
                                        \Log::info('WhatsApp enviado a: ' . $telefonoCompleto . ' | Corte');
                                    } else {
                                        $enviadosFallidos++;
                                        \Log::error('Error WhatsApp a: ' . $telefonoCompleto . ' | ' . json_encode($responseData));
                                    }
                                } else {
                                    $enviadosFallidos++;
                                }
                                
                            } elseif($tipoPlantilla == 'recordatorio' || str_contains($tipoPlantilla, 'recordatorio')) {
                                // ========================================
                                // CASO: RECORDATORIO
                                // ========================================

                                // 🔹 Verificar si el contacto ya tiene marcado wpp_recordar = 1
                                if ($contacto && isset($contacto->wpp_recordar) && $contacto->wpp_recordar == 1) {
                                    \Log::info("Contrato {$contrato->id}: contacto ya tiene wpp_recordar=1, se omite envío de recordatorio.");
                                    continue; // Saltar este contacto, no enviar mensaje
                                }

                                // Buscar si la empresa es CONECTA COMUNICACIONES SAS
                                $empresaEspecial = \DB::table('empresas')
                                    ->where('nombre', 'CONECTA COMUNICACIONES SAS')
                                    ->exists();

                                if ($empresaEspecial) {
                                    // Buscar la factura más reciente asociada al contrato
                                    $factura = Factura::where('contrato_id', $contrato->id)
                                        ->latest()
                                        ->first();

                                    if (!$factura) {
                                        $enviadosFallidos++;
                                        \Log::warning('Contrato ' . $contrato->id . ': Sin factura para recordatorio especial');
                                        continue;
                                    }

                                    // ================================
                                    // Construcción de variables dinámicas
                                    // ================================

                                    // var1 → Nombre de la factura
                                    $var1 = "Factura_{$factura->codigo}";

                                    // var2 → Suma de precios de items_factura donde factura = $factura->id
                                    $var2 = \DB::table('items_factura')
                                        ->where('factura', $factura->id)
                                        ->sum('precio');

                                    // Formatear valor como dinero (ej: $12.345)
                                    $var2 = '$' . number_format($var2, 0, ',', '.');

                                    // var3 → Fecha de pago oportuno (según grupo de corte)
                                    $contratoData = \DB::table('contracts')->where('id', $factura->contrato_id)->first();

                                    if ($contratoData) {
                                        $grupoCorte = \DB::table('grupos_corte')->where('id', $contratoData->grupo_corte)->first();

                                        if ($grupoCorte && $grupoCorte->fecha_pago) {
                                            // Día del grupo de corte
                                            $dia = str_pad($grupoCorte->fecha_pago, 2, '0', STR_PAD_LEFT);

                                            // Tomamos mes y año de la fecha de creación de la factura
                                            $mes = \Carbon\Carbon::parse($factura->created_at)->month;
                                            $anio = \Carbon\Carbon::parse($factura->created_at)->year;

                                            // Construir la fecha final
                                            $fechaFinal = \Carbon\Carbon::createFromDate($anio, $mes, $dia);

                                            // Formato en español: 25 de noviembre de 2025
                                            $var3 = $fechaFinal->translatedFormat('j \\d\\e F \\d\\e Y');
                                        } else {
                                            $var3 = 'Fecha no disponible';
                                        }
                                    } else {
                                        $var3 = 'Fecha no disponible';
                                    }

                                    // var4 → Texto fijo
                                    $var4 = "suspensión de servicio";

                                    // ================================
                                    // Envío con plantilla especial
                                    // ================================
                                    $body = [
                                        "phone" => $telefonoCompleto,
                                        "templateName" => "conectapendientepago",
                                        "languageCode" => "es",
                                        "components" => [
                                            [
                                                "type" => "body",
                                                "parameters" => [
                                                    ["type" => "text", "text" => $var1], // Factura_XXXX
                                                    ["type" => "text", "text" => $var2], // Valor total items
                                                    ["type" => "text", "text" => $var3], // Fecha de pago oportuno
                                                    ["type" => "text", "text" => $var4]  // suspensión de servicio
                                                ]
                                            ]
                                        ]
                                    ];
                                } else {
                                    // Body genérico
                                    $body = [
                                        "phone" => $telefonoCompleto,
                                        "templateName" => "recordatorios",
                                        "languageCode" => "en",
                                        "components" => [
                                            [
                                                "type" => "body",
                                                "parameters" => [
                                                    ["type" => "text", "text" => $nameEmpresa]
                                                ]
                                            ]
                                        ]
                                    ];
                                }

                                // ================================
                                // Envío del mensaje
                                // ================================
                                $response = (object) $wapiService->sendTemplate($instance->uuid, $body);
                                if (isset($response->scalar)) {
                                    $responseData = json_decode($response->scalar ?? '{}', true);
                                    $esExitoso = false;

                                    if (isset($responseData['status']) && $responseData['status'] === "success") {
                                        if (isset($responseData['data']['messages'][0]['id']) || 
                                            isset($responseData['data']['messages'][0]['message_status'])) {
                                            $esExitoso = true;
                                        }
                                    }

                                    if (isset($responseData['messages'][0]['id']) || 
                                        isset($responseData['message_id']) || 
                                        isset($responseData['messageId'])) {
                                        $esExitoso = true;
                                    }

                                    if (isset($responseData['error']) && is_array($responseData['error']) && 
                                        (isset($responseData['error']['code']) || isset($responseData['error']['error_code']))) {
                                        $esExitoso = false;
                                    }

                                    if (isset($responseData['error']) && is_string($responseData['error']) &&
                                        (str_contains(strtolower($responseData['error']), 'success') || 
                                        str_contains(strtolower($responseData['error']), 'sent successfully'))) {
                                        $esExitoso = true;
                                    }

                                    if ($esExitoso) {
                                        $enviadosExito++;
                                        \Log::info('WhatsApp enviado a: ' . $telefonoCompleto . ' | Recordatorio');

                                        // 🔹 Actualizar columna wpp_recordar a 1 en el contacto correspondiente
                                        if ($contacto && isset($contacto->id)) {
                                            \DB::table('contactos')
                                                ->where('id', $contacto->id)
                                                ->update(['wpp_recordar' => 1]);
                                        }

                                    } else {
                                        $enviadosFallidos++;
                                        \Log::error('Error WhatsApp a: ' . $telefonoCompleto . ' | ' . json_encode($responseData));
                                    }
                                } else {
                                    $enviadosFallidos++;
                                }
                            } elseif($tipoPlantilla == 'factura' || str_contains($tipoPlantilla, 'factura')){
                                // ========================================
                                // CASO: FACTURA
                                // ========================================
                                
                                $factura = Factura::where('contrato_id', $contrato->id)
                                                ->latest()
                                                ->first();
                                
                                if(!$factura){
                                    $enviadosFallidos++;
                                    \Log::warning('Contrato ' . $contrato->id . ': Sin factura');
                                    continue;
                                }
                                
                                // Generar PDF temporal
                                $token = config('app.key');
                                $fileName = 'Factura_' . $factura->codigo . '.pdf';
                                $relativePath = 'temp/' . $fileName;
                                $storagePath = storage_path('app/public/' . $relativePath);
                                
                                if (!file_exists($storagePath)) {
                                    $facturaPDF = $this->getPdfFactura($factura->id);
                                    
                                    if (!Storage::disk('public')->exists('temp')) {
                                        Storage::disk('public')->makeDirectory('temp');
                                    }
                                    
                                    Storage::disk('public')->put($relativePath, $facturaPDF);
                                    
                                    $attempts = 0;
                                    while (!file_exists($storagePath) && $attempts < 5) {
                                        usleep(300000);
                                        $attempts++;
                                    }
                                }
                                
                                if (!file_exists($storagePath)) {
                                    $enviadosFallidos++;
                                    \Log::error('No se pudo generar PDF para factura: ' . $factura->codigo);
                                    continue;
                                }
                                
                                $urlFactura = url('storage/temp/' . $fileName);
                                
                                $estadoCuenta = $factura->estadoCuenta();
                                $total = $factura->total()->total;
                                $saldo = $estadoCuenta->saldoMesAnterior > 0
                                    ? $estadoCuenta->saldoMesAnterior + $total
                                    : $total;
                                
                                $body = [
                                    "phone" => $telefonoCompleto,
                                    "templateName" => "facturas",
                                    "languageCode" => "en",
                                    "components" => [
                                        [
                                            "type" => "header",
                                            "parameters" => [
                                                [
                                                    "type" => "document",
                                                    "document" => [
                                                        "link" => $urlFactura,
                                                        "filename" => "Factura_{$factura->codigo}.pdf"
                                                    ]
                                                ]
                                            ]
                                        ],
                                        [
                                            "type" => "body",
                                            "parameters" => [
                                                ["type" => "text", "text" => $contacto->nombre . " " . $contacto->apellido1],
                                                ["type" => "text", "text" => $nameEmpresa],
                                                ["type" => "text", "text" => number_format($saldo, 0, ',', '.')]
                                            ]
                                        ]
                                    ]
                                ];
                                
                                $response = (object) $wapiService->sendTemplate($instance->uuid, $body);
                                
                                if (isset($response->scalar)) {
                                    $responseData = json_decode($response->scalar ?? '{}', true);
                                    $esExitoso = false;
                                    
                                    if (isset($responseData['status']) && $responseData['status'] === "success") {
                                        if (isset($responseData['data']['messages'][0]['id']) || 
                                            isset($responseData['data']['messages'][0]['message_status'])) {
                                            $esExitoso = true;
                                        }
                                    }
                                    
                                    if (isset($responseData['messages'][0]['id']) || 
                                        isset($responseData['message_id']) || 
                                        isset($responseData['messageId'])) {
                                        $esExitoso = true;
                                    }
                                    
                                    if (isset($responseData['error']) && is_array($responseData['error']) && 
                                        (isset($responseData['error']['code']) || isset($responseData['error']['error_code']))) {
                                        $esExitoso = false;
                                    }
                                    
                                    if (isset($responseData['error']) && is_string($responseData['error']) &&
                                        (str_contains(strtolower($responseData['error']), 'success') || 
                                        str_contains(strtolower($responseData['error']), 'sent successfully'))) {
                                        $esExitoso = true;
                                    }
                                    
                                    if ($esExitoso) {
                                        $enviadosExito++;
                                        \Log::info('WhatsApp enviado a: ' . $telefonoCompleto . ' | Factura: ' . $factura->codigo);
                                    } else {
                                        $enviadosFallidos++;
                                        \Log::error('Error WhatsApp a: ' . $telefonoCompleto . ' | ' . json_encode($responseData));
                                    }
                                } else {
                                    $enviadosFallidos++;
                                }
                                
                            } else {
                                $enviadosFallidos++;
                                \Log::warning('Plantilla no reconocida: ' . $tipoPlantilla);
                            }
                            
                            usleep(100000); // 0.1 segundos entre envíos
                            
                        } catch (\Exception $e) {
                            $enviadosFallidos++;
                            \Log::error('Excepción WhatsApp Meta contrato ' . $contrato->id . ': ' . $e->getMessage());
                        }
                        
                    } else {
                        // ========================================
                        // ENVÍO NORMAL DE WHATSAPP (SIN META)
                        // ========================================
                        
                        $wapiService = new WapiService();
                        $instance = Instance::where('company_id', $empresa->id)
                        ->where('type', 1)
                        ->where('meta', 1)
                        ->first();
                        
                        if($i == 0 && (is_null($instance) || empty($instance))){
                            return back()->with('danger','Instancia no está creada');
                        }
                        
                        if($i == 0 && $instance->status !== "PAIRED") {
                            return back()->with('danger','La instancia de whatsapp no está conectada, por favor conectese a whatsapp y vuelva a intentarlo.');
                        }
                        
                        $contacto = $contrato->cliente();

                        $contact = [
                            "phone" => "57" . $contacto->celular,
                            "name" => $contacto->nombre . " " . $contacto->apellido1
                        ];

                        $nameEmpresa = $empresa->nombre;

                        $contenido = $plantilla->contenido;
                        $contenido = str_replace('{{$name}}', $contacto->nombre, $contenido);
                        $contenido = str_replace('{{$company}}', $nameEmpresa, $contenido);
                        $contenido = str_replace('{{$nit}}', $empresa->nit, $contenido);
                        $contenido = str_replace('{{$date}}', date('Y-m-d'), $contenido);

                        $message = $plantilla->title . "\r\n" . $contenido;

                        $body = [
                            "contact" => $contact,
                            "message" => $message,
                            "media" => ''
                        ];

                        $response = (object) $wapiService->sendMessageMedia($instance->uuid, $instance->api_key, $body);
                    }

                }
                // ===================================
                // SECCIÓN DE SMS Y EMAIL (sin cambios)
                // ===================================
                else if($request->type == 'SMS'){
                    $numero = str_replace('+','',$contrato->cliente()->celular);
                    $numero = str_replace(' ','',$numero);
                    array_push($numeros, '57'.$numero);
                    if(strlen($numero) >= 10  && $plantilla->contenido){
                        $bulk .= '{"numero": "57'.$numero.'", "sms": "'.$plantilla->contenido.'"},';
                    }
                }
                elseif($request->type == 'EMAIL'){
                    $host = ServidorCorreo::where('estado', 1)->where('empresa', Auth::user()->empresa)->first();

                    if($host){
                        $existing = config('mail');
                        $new = array_merge(
                            $existing, [
                                'host' => $host->servidor,
                                'port' => $host->puerto,
                                'encryption' => $host->seguridad,
                                'username' => $host->usuario,
                                'password' => $host->password,
                                'from' => [
                                    'address' => $host->address,
                                    'name' => $host->name
                                ],
                            ]
                        );
                        config(['mail'=>$new]);
                    }

                    $datos = array(
                        'titulo'  => $plantilla->title,
                        'archivo' => $plantilla->archivo,
                        'cliente' => $contrato->cliente()->nombre.' '.$contrato->cliente()->apellidos(),
                        'empresa' => Auth::user()->empresa()->nombre,
                        'nit' => Auth::user()->empresa()->nit.'-'.Auth::user()->empresa()->dv,
                        'date' => date('d-m-Y'),
                        'name' => $contrato->cliente()->nombre.' '.$contrato->cliente()->apellidos(),
                        'company' => Auth::user()->empresa()->nombre,
                    );

                    $correo = new NotificacionMailable($datos);

                    if($mailC = $contrato->cliente()->email){
                        $tituloCorreo = $plantilla->title;
                        if(str_contains($mailC, '@')){
                            $template = 'emails.'.$plantilla->archivo;
                            $content = View::make($template, $datos)->render();
                            self::sendInBlue($content, $correo->subject, [$mailC], $correo->name, []);
                        }
                    }
                }
            }
        }

        // ===================================
        // RESPUESTAS SEGÚN EL TIPO DE ENVÍO
        // ===================================
        
        if($request->type == 'whatsapp'){
            if($enviarConMeta){
                $totalEnviados = $enviadosExito + $enviadosFallidos;
                $mensaje = "Proceso de envío completado a través de Meta WhatsApp API. ";
                $mensaje .= "Total procesados: {$totalEnviados} | ";
                $mensaje .= "Enviados exitosamente: {$enviadosExito} | ";
                $mensaje .= "Fallidos: {$enviadosFallidos}";
                
                if($enviadosFallidos > 0 && $enviadosExito > 0){
                    return redirect('empresa/avisos')->with('warning', $mensaje);
                } elseif($enviadosFallidos > 0 && $enviadosExito == 0){
                    return redirect('empresa/avisos')->with('danger', $mensaje);
                } else {
                    return redirect('empresa/avisos')->with('success', $mensaje);
                }
            } else {
                return redirect('empresa/avisos')->with('success', 'Proceso de envío realizado con éxito notificaciones de WhatsApp');
            }
        }

        if($request->type == 'EMAIL'){
            return redirect('empresa/avisos')->with('success', 'Proceso de envío realizado con exito notificaciones de email');
        }

        if($request->type == 'SMS'){
            $servicio = Integracion::where('empresa', Auth::user()->empresa)->where('tipo', 'SMS')->where('status', 1)->first();
            if($servicio){
                if($servicio->nombre == 'Hablame SMS'){
                    if($servicio->api_key && $servicio->user && $servicio->pass){
                        $curl = curl_init();

                        if(count($request->contrato)>1){
                            curl_setopt_array($curl, [
                                CURLOPT_URL => "https://api103.hablame.co/api/sms/v3/send/marketing/bulk",
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => "",
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 30,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => "POST",
                                CURLOPT_POSTFIELDS => "{\n  \"bulk\": [\n    ".substr($bulk, 0, -1)."\n  ]\n}",
                                CURLOPT_HTTPHEADER => [
                                    'Content-Type: application/json',
                                    'account: '.$servicio->user,
                                    'apiKey: '.$servicio->api_key,
                                    'token: '.$servicio->pass,
                                ],
                            ]);
                        }else{
                            $post['toNumber'] = $numero;
                            $post['sms'] = $plantilla->contenido;
                            curl_setopt_array($curl, array(
                                CURLOPT_URL => 'https://api103.hablame.co/api/sms/v3/send/marketing',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_POSTFIELDS => json_encode($post),
                                CURLOPT_HTTPHEADER => array(
                                    'account: '.$servicio->user,
                                    'apiKey: '.$servicio->api_key,
                                    'token: '.$servicio->pass,
                                    'Content-Type: application/json'
                                ),
                            ));
                        }

                        $response = curl_exec($curl);
                        $err = curl_error($curl);
                        curl_close($curl);

                        $response = json_decode($response, true);
                        if(isset($response['error'])){
                            if($response['error']['code'] == 1000303){
                                $msj = 'Cuenta no encontrada';
                            }else if($response['error']['code'] == '1x023'){
                                $msj = 'Debe tener más de 2 contactos seleccionado para hacer uso de los envíos masivos';
                            }else{
                                $msj = $response['error']['details'];
                            }

                            if(is_array($msj)){
                                return back()->with('danger', 'Envío Fallido: '. implode(",", $msj));
                            }else{
                                return back()->with('danger', 'Envío Fallido: '.$msj);
                            }

                        }else{
                            if($response['status'] == '1x000'){
                                $msj = 'SMS recíbido por hablame exitosamente';
                            }else if($response['status'] == '1x152'){
                                $msj = 'SMS entregado al operador';
                            }else if($response['status'] == '1x153'){
                                $msj = 'SMS entregado al celular';
                            }
                            return redirect('empresa/avisos')->with('success', 'Envío Éxitoso: '.$msj);
                        }
                    }else{
                        $mensaje = 'EL MENSAJE NO SE PUDO ENVIAR PORQUE FALTA INFORMACIÓN EN LA CONFIGURACIÓN DEL SERVICIO';
                        return redirect('empresa/avisos')->with('danger', $mensaje);
                    }
                }elseif($servicio->nombre == 'SmsEasySms'){
                    if($servicio->user && $servicio->pass){
                        $post['to'] = $numeros;
                        $post['text'] = $plantilla->contenido;
                        $post['from'] = "SMS";
                        $login = $servicio->user;
                        $password = $servicio->pass;

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "https://sms.istsas.com/Api/rest/message");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
                        curl_setopt($ch, CURLOPT_HTTPHEADER,
                        array(
                            "Accept: application/json",
                            "Authorization: Basic ".base64_encode($login.":".$password)));
                        $result = curl_exec ($ch);
                        $err  = curl_error($ch);
                        curl_close($ch);

                        if ($err) {
                            return redirect('empresa/avisos')->with('danger', 'Respuesta API SmsEasySms: '.$err);
                        }else{
                            $response = json_decode($result, true);

                            if(isset($response['error'])){
                                $fail++;
                            }else{
                                $succ++;
                            }
                        }
                    }else{
                        $mensaje = 'EL MENSAJE NO SE PUDO ENVIAR PORQUE FALTA INFORMACIÓN EN LA CONFIGURACIÓN DEL SERVICIO';
                        return redirect('empresa/avisos')->with('danger', $mensaje);
                    }
                }else{
                    if($servicio->user && $servicio->pass){
                        $post['to'] = $numeros;
                        $post['text'] = $plantilla->contenido;
                        $post['from'] = "";
                        $login = $servicio->user;
                        $password = $servicio->pass;

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "https://masivos.colombiared.com.co/Api/rest/message");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
                        curl_setopt($ch, CURLOPT_HTTPHEADER,
                        array(
                            "Accept: application/json",
                            "Authorization: Basic ".base64_encode($login.":".$password)));
                        $result = curl_exec ($ch);
                        $err  = curl_error($ch);
                        curl_close($ch);

                        if ($err) {
                            return redirect('empresa/avisos')->with('danger', 'Respuesta API Colombia Red: '.$err);
                        }else{
                            $response = json_decode($result, true);

                            if(isset($response['error'])){
                                $fail++;
                            }else{
                                $succ++;
                            }
                        }
                    }else{
                        $mensaje = 'EL MENSAJE NO SE PUDO ENVIAR PORQUE FALTA INFORMACIÓN EN LA CONFIGURACIÓN DEL SERVICIO';
                        return redirect('empresa/avisos')->with('danger', $mensaje);
                    }
                }
                return redirect('empresa/avisos')->with('success', 'Proceso de envío realizado. SMS Enviados: '.$fail.' - SMS Fallidos: '.$succ);
            }else{
                return redirect('empresa/avisos')->with('danger', 'DISCULPE, NO POSEE NINGUN SERVICIO DE SMS HABILITADO. POR FAVOR HABILÍTELO PARA DISFRUTAR DEL SERVICIO');
            }
        }
    }

    public function envio_personalizado(Request $request){
        $numero = str_replace('+','',$request->numero_sms);
        $numero = str_replace(' ','',$numero);
        $mensaje = $request->text_sms;

        $servicio = Integracion::where('empresa', Auth::user()->empresa)->where('tipo', 'SMS')->where('status', 1)->first();
        if($servicio){
            if($servicio->nombre == 'Hablame SMS'){
                if($servicio->api_key && $servicio->user && $servicio->pass){
                    $post['toNumber'] = $numero;
                    $post['sms'] = $mensaje;

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://api103.hablame.co/api/sms/v3/send/marketing',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',CURLOPT_POSTFIELDS => json_encode($post),
                        CURLOPT_HTTPHEADER => array(
                            'account: '.$servicio->user,
                            'apiKey: '.$servicio->api_key,
                            'token: '.$servicio->pass,
                            'Content-Type: application/json'
                        ),
                    ));
                    $result = curl_exec ($curl);
                    $err  = curl_error($curl);
                    curl_close($curl);

                    $response = json_decode($result, true);
                    if(isset($response['error'])){
                        if($response['error']['code'] == 1000303){
                            $msj = 'Cuenta no encontrada';
                        }else{
                            $msj = $response['error']['details'];
                        }
                        return response()->json(['success' => false, 'title' => 'Envío Fallido', 'message' => $msj , 'type' => 'error']);
                    }else{
                        if($response['status'] == '1x000'){
                            $msj = 'SMS recíbido por hablame exitosamente';
                        }else if($response['status'] == '1x152'){
                            $msj = 'SMS entregado al operador';
                        }else if($response['status'] == '1x153'){
                            $msj = 'SMS entregado al celular';
                        }
                        return response()->json(['success' => true, 'title' => 'Envío Realizado', 'message' => $msj , 'type' => 'success']);
                    }
                }else{
                    return response()->json(['success' => false, 'title' => 'Envío Fallido', 'message' => 'El mensaje no se pudo enviar porque falta información en la configuración del servicio', 'type' => 'error']);
                }
            }elseif($servicio->nombre == 'SmsEasySms'){
                if($servicio->user && $servicio->pass){
                    $post['to'] = array('57'.$numero);
                    $post['text'] = $mensaje;
                    $post['from'] = "SMS";
                    $login = $servicio->user;
                    $password = $servicio->pass;

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://sms.istsas.com/Api/rest/message");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
                    curl_setopt($ch, CURLOPT_HTTPHEADER,
                    array(
                        "Accept: application/json",
                        "Authorization: Basic ".base64_encode($login.":".$password)));
                    $result = curl_exec ($ch);
                    $err  = curl_error($ch);
                    curl_close($ch);

                    if ($err) {
                        return response()->json(['success' => false, 'title' => 'Envío Fallido', 'message' => '', 'type' => 'error']);
                    }else{
                        $response = json_decode($result, true);
                        if(isset($response['error'])){
                            if($response['error']['code'] == 102){
                                $msj = "No hay destinatarios válidos (Cumpla con el formato de nro +5700000000000)";
                            }else if($response['error']['code'] == 103){
                                $msj = "Nombre de usuario o contraseña desconocidos";
                            }else if($response['error']['code'] == 104){
                                $msj = "Falta el mensaje de texto";
                            }else if($response['error']['code'] == 105){
                                $msj = "Mensaje de texto demasiado largo";
                            }else if($response['error']['code'] == 106){
                                $msj = "Falta el remitente";
                            }else if($response['error']['code'] == 107){
                                $msj = "Remitente demasiado largo";
                            }else if($response['error']['code'] == 108){
                                $msj = "No hay fecha y hora válida para enviar";
                            }else if($response['error']['code'] == 109){
                                $msj = "URL de notificación incorrecta";
                            }else if($response['error']['code'] == 110){
                                $msj = "Se superó el número máximo de piezas permitido o número incorrecto de piezas";
                            }else if($response['error']['code'] == 111){
                                $msj = "Crédito/Saldo insuficiente";
                            }else if($response['error']['code'] == 112){
                                $msj = "Dirección IP no permitida";
                            }else if($response['error']['code'] == 113){
                                $msj = "Codificación no válida";
                            }else{
                                $msj = $response['error']['description'];
                            }
                            return response()->json(['success' => false, 'title' => 'Envío Fallido', 'message' => $msj , 'type' => 'error']);
                        }else{
                            return response()->json(['success' => true, 'title' => 'Envío Realizado', 'message' => '', 'type' => 'success']);
                        }
                    }
                }else{
                    return response()->json(['success' => false, 'title' => 'Envío Fallido', 'message' => 'El mensaje no se pudo enviar porque falta información en la configuración del servicio', 'type' => 'error']);
                }
            }else{
                if($servicio->user && $servicio->pass){
                    $post['to'] = array('57'.$numero);
                    $post['text'] = $mensaje;
                    $post['from'] = "SMS";
                    $login = $servicio->user;
                    $password = $servicio->pass;

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://masivos.colombiared.com.co/Api/rest/message");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
                    curl_setopt($ch, CURLOPT_HTTPHEADER,
                    array(
                        "Accept: application/json",
                        "Authorization: Basic ".base64_encode($login.":".$password)));
                    $result = curl_exec ($ch);
                    $err  = curl_error($ch);
                    curl_close($ch);

                    if ($err) {
                        return back()->with('danger', 'Envío Fallido');
                    }else{
                        $response = json_decode($result, true);
                        if(isset($response['error'])){
                            if($response['error']['code'] == 102){
                                $msj = "No hay destinatarios válidos (Cumpla con el formato de nro +5700000000000)";
                            }else if($response['error']['code'] == 103){
                                $msj = "Nombre de usuario o contraseña desconocidos";
                            }else if($response['error']['code'] == 104){
                                $msj = "Falta el mensaje de texto";
                            }else if($response['error']['code'] == 105){
                                $msj = "Mensaje de texto demasiado largo";
                            }else if($response['error']['code'] == 106){
                                $msj = "Falta el remitente";
                            }else if($response['error']['code'] == 107){
                                $msj = "Remitente demasiado largo";
                            }else if($response['error']['code'] == 108){
                                $msj = "No hay fecha y hora válida para enviar";
                            }else if($response['error']['code'] == 109){
                                $msj = "URL de notificación incorrecta";
                            }else if($response['error']['code'] == 110){
                                $msj = "Se superó el número máximo de piezas permitido o número incorrecto de piezas";
                            }else if($response['error']['code'] == 111){
                                $msj = "Crédito/Saldo insuficiente";
                            }else if($response['error']['code'] == 112){
                                $msj = "Dirección IP no permitida";
                            }else if($response['error']['code'] == 113){
                                $msj = "Codificación no válida";
                            }else{
                                $msj = $response['error']['description'];
                            }
                            return response()->json(['success' => false, 'title' => 'Envío Fallido', 'message' => $msj , 'type' => 'error']);
                        }else{
                            return response()->json(['success' => true, 'title' => 'Envío Realizado', 'message' => '', 'type' => 'success']);
                        }
                    }
                }else{
                    return response()->json(['success' => false, 'title' => 'Envío Fallido', 'message' => 'El mensaje no se pudo enviar porque falta información en la configuración del servicio', 'type' => 'error']);
                }
            }
        }else{
            return response()->json(['success' => false, 'title' => 'Envío Fallido', 'message' => 'Disculpe, no posee ningun servicio de sms habilitado. Por favor habilítelo para disfrutar del servicio', 'type' => 'error']);
        }
    }

    public function automaticos(){
        $this->getAllPermissions(Auth::user()->id);

        $empresa = Empresa::find(auth()->user()->empresa);

        view()->share(['subseccion' => 'envio-automatico']);

        return view('avisos.automaticos', compact('empresa'));
    }

    public function storeAutomaticos(Request $request){

        $empresa = Empresa::find(auth()->user()->empresa);

        $empresa->sms_pago = strip_tags(trim($request->sms_pago));
        $empresa->sms_factura_generada = strip_tags(trim($request->sms_factura_generada));

        $empresa->update();

        return back()->with(['success' => 'mensajes guardados correctamente']);
    }

}
