<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Mail;
use Validator;
use Illuminate\Validation\Rule;
use Auth;
use DB;
use Session;

use App\Mikrotik;
use App\Empresa;
use App\Http\Controllers\CronController;
use App\Contrato;
use App\GrupoCorte;
use App\Campos;
use App\Model\Ingresos\Factura;
use App\Model\Ingresos\ItemsFactura;
use App\Contacto;
use App\Services\BillingCycleAnalyzer;

class GruposCorteController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
        set_time_limit(300);
        view()->share(['inicio' => 'master', 'seccion' => 'zonas', 'subseccion' => 'grupo_corte', 'title' => 'Grupos de Corte', 'icon' => 'fas fa-project-diagram']);
    }

    public function index(Request $request){
        $this->getAllPermissions(Auth::user()->id);
        return view('grupos-corte.index');
    }

    public function grupos(Request $request){
        $modoLectura = auth()->user()->modo_lectura();
        $grupos = GrupoCorte::query()
            ->where('empresa', Auth::user()->empresa);
        if ($request->filtro == true) {
            if($request->nombre){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('nombre', 'like', "%{$request->nombre}%");
                });
            }
            if($request->fecha_factura){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('fecha_factura', 'like', "%{$request->fecha_factura}%");
                });
            }
            if($request->fecha_pago){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('fecha_pago', 'like', "%{$request->fecha_pago}%");
                });
            }
            if($request->fecha_corte){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('fecha_corte', 'like', "%{$request->fecha_corte}%");
                });
            }
            if($request->fecha_suspension){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('fecha_suspension', 'like', "%{$request->fecha_suspension}%");
                });
            }
            if($request->status >= 0){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('status', 'like', "%{$request->status}%");
                });
            }
        }

        return datatables()->eloquent($grupos)
            ->editColumn('id', function (GrupoCorte $grupo) {
                return $grupo->id;
            })
            ->editColumn('nombre', function (GrupoCorte $grupo) {
                return "<a href=" . route('grupos-corte.show', $grupo->id) . ">{$grupo->nombre}</div></a>";
            })
            ->editColumn('fecha_factura', function (GrupoCorte $grupo) {
                return ($grupo->fecha_factura == 0) ? 'No aplica' : $grupo->fecha_factura;
            })
            ->editColumn('fecha_pago', function (GrupoCorte $grupo) {
                return ($grupo->fecha_pago == 0) ? 'No aplica' : $grupo->fecha_pago;
            })
            ->editColumn('fecha_corte', function (GrupoCorte $grupo) {
                return ($grupo->fecha_corte == 0) ? 'No aplica' : $grupo->fecha_corte;
            })
            ->editColumn('fecha_suspension', function (GrupoCorte $grupo) {
                return ($grupo->fecha_suspension == 0) ? 'No aplica' : $grupo->fecha_suspension;
            })
            ->editColumn('hora_suspension', function (GrupoCorte $grupo) {
                return date('g:i A', strtotime($grupo->hora_suspension));
            })
            ->editColumn('status', function (GrupoCorte $grupo) {
                return "<span class='text-{$grupo->status("true")}'><strong>{$grupo->status()}</strong></span>";
            })
            ->addColumn('acciones', $modoLectura ?  "" : "grupos-corte.acciones")
            ->rawColumns(['acciones', 'nombre', 'id', 'status'])
            ->toJson();
    }

    public function create(){
        $this->getAllPermissions(Auth::user()->id);
        view()->share(['title' => 'Nuevo Grupo de Corte']);
        return view('grupos-corte.create');
    }

    public function store(Request $request){
        $request->validate([
            'nombre' => 'required|max:250',
            'fecha_corte' => 'required|numeric',
            'fecha_suspension' => 'required|numeric',
            'fecha_factura' => 'required|numeric',
            'fecha_pago' => 'required|numeric',
            'hora_suspension' => 'required',
            'periodo_facturacion' => 'required|numeric|in:1,2,3',
        ]);

        $hora_suspension = explode(":", $request->hora_suspension);
        $hora_suspension_limit = $hora_suspension[0]+4;
        $hora_suspension_limit = $hora_suspension_limit.':'.$hora_suspension[1];

        $grupo = new GrupoCorte;
        $grupo->nombre = $request->nombre;
        $grupo->fecha_factura = $request->fecha_factura;
        $grupo->fecha_pago = $request->fecha_pago;
        $grupo->fecha_corte = $request->fecha_corte;
        $grupo->fecha_suspension = $request->fecha_suspension;
        $grupo->hora_suspension = $request->hora_suspension;
        $grupo->hora_suspension_limit = $hora_suspension_limit;
        $grupo->hora_creacion_factura = $request->hora_creacion_factura;
        $grupo->status = $request->status;
        $grupo->prorroga_tv = $request->prorroga_tv ?? 0;
        $grupo->periodo_facturacion = $request->periodo_facturacion;
        $grupo->created_by = Auth::user()->id;
        $grupo->empresa = Auth::user()->empresa;
        $grupo->save();

        $mensaje='SE HA CREADO SATISFACTORIAMENTE EL GRUPO DE CORTE';
        return redirect('empresa/grupos-corte')->with('success', $mensaje);
    }

    public function storeBack(Request $request){
        $hora_suspension = explode(":", $request->hora_suspension);
        $hora_suspension_limit = $hora_suspension[0]+4;
        $hora_suspension_limit = $hora_suspension_limit.':'.$hora_suspension[1];

        $grupo                   = new GrupoCorte;
        $grupo->nombre           = $request->nombre;
        $grupo->fecha_factura    = $request->fecha_factura;
        $grupo->fecha_pago       = $request->fecha_pago;
        $grupo->fecha_corte      = $request->fecha_corte;
        $grupo->fecha_suspension = $request->fecha_suspension;
        $grupo->hora_suspension  = $request->hora_suspension;
        $grupo->hora_suspension_limit = $hora_suspension_limit;
        $grupo->prorroga_tv = $request->prorroga_tv;
        $grupo->periodo_facturacion = $request->periodo_facturacion ?? 1;
        $grupo->status           = $request->status;
        $grupo->created_by       = Auth::user()->id;
        $grupo->empresa          = Auth::user()->empresa;
        $grupo->save();

        if ($grupo) {
            $arrayPost['success']    = true;
            $arrayPost['id']         = GrupoCorte::all()->last()->id;
            $arrayPost['suspension'] = GrupoCorte::all()->last()->fecha_suspension;
            $arrayPost['corte']      = GrupoCorte::all()->last()->fecha_corte;
            $arrayPost['nombre']     = GrupoCorte::all()->last()->nombre;
            echo json_encode($arrayPost);
            exit;
        }
    }

    public function show($id){
        $this->getAllPermissions(Auth::user()->id);
        $grupo = GrupoCorte::find($id);

        if ($grupo) {
            $contratos = Contrato::where('grupo_corte', $grupo->id)->where('empresa', Auth::user()->empresa)->count();
            $tabla = Campos::where('modulo', 2)->where('estado', 1)->where('empresa', Auth::user()->empresa)->orderBy('orden', 'asc')->get();
            view()->share(['title' => $grupo->nombre]);
            return view('grupos-corte.show')->with(compact('grupo', 'contratos', 'tabla'));
        }
        return redirect('empresa/grupos-corte')->with('danger', 'GRUPO DE CORTE NO ENCONTRADO, INTENTE NUEVAMENTE');
    }

    public function edit($id){
        $this->getAllPermissions(Auth::user()->id);
        $grupo = GrupoCorte::find($id);

        if ($grupo) {
            view()->share(['title' => 'Editar: '.$grupo->nombre]);
            return view('grupos-corte.edit')->with(compact('grupo'));
        }
        return redirect('empresa/grupos-corte')->with('danger', 'GRUPO DE CORTE NO ENCONTRADO, INTENTE NUEVAMENTE');
    }

    public function update(Request $request, $id){
        $request->validate([
            'nombre' => 'required|max:250',
            'fecha_corte' => 'required|numeric',
            'fecha_suspension' => 'required|numeric',
            'fecha_factura' => 'required|numeric',
            'fecha_pago' => 'required|numeric',
            'hora_suspension' => 'required',
            'periodo_facturacion' => 'required|numeric|in:1,2,3',
        ]);

        $grupo = GrupoCorte::find($id);

        if ($grupo) {
            $hora_suspension = explode(":", $request->hora_suspension);
            $hora_suspension_limit = $hora_suspension[0]+4;
            $hora_suspension_limit = $hora_suspension_limit.':'.$hora_suspension[1];

            //Si es diferente es por que hubo un cambio y vamos a actualizar la fecha de suspension de las ultimas facturas creadas
            if($grupo->fecha_suspension != $request->fecha_suspension){

                $mesActual = date('m');
                $yearActual = date('Y');

                $facturasGrupo = Factura::join('contracts as c', 'c.id', '=' ,'factura.contrato_id')
                ->join('grupos_corte as gc','gc.id','=','c.grupo_corte')
                ->select('factura.*','gc.id as grupo_id')
                ->whereRaw("DATE_FORMAT(factura.vencimiento, '%m')=" .$mesActual)
                ->whereRaw("DATE_FORMAT(factura.vencimiento, '%Y')=" .$yearActual)
                ->where('gc.id',$grupo->id)
                ->get();

                foreach($facturasGrupo as $fg){
                    $fg->vencimiento = $yearActual . "-" . $mesActual . "-" . $request->fecha_suspension;
                    $fg->save();
                }
            }

            $grupo->nombre           = $request->nombre;
            $grupo->fecha_factura    = $request->fecha_factura;
            $grupo->fecha_pago       = $request->fecha_pago;
            $grupo->fecha_corte      = $request->fecha_corte;
            $grupo->fecha_suspension = $request->fecha_suspension;
            $grupo->hora_suspension  = $request->hora_suspension;
            $grupo->hora_suspension_limit = $hora_suspension_limit;
            $grupo->hora_creacion_factura = $request->hora_creacion_factura;
            $grupo->status                = $request->status;
            $grupo->prorroga_tv           = $request->prorroga_tv;
            $grupo->periodo_facturacion  = $request->periodo_facturacion;
            $grupo->updated_by            = Auth::user()->id;
            $grupo->nro_factura_vencida = $request->nro_factura_vencida;
            $grupo->save();

            $mensaje='SE HA MODIFICADO SATISFACTORIAMENTE EL GRUPO DE CORTE';
            return redirect('empresa/grupos-corte')->with('success', $mensaje);
        }
        return redirect('empresa/grupos-corte')->with('danger', 'GRUPO DE CORTE NO ENCONTRADO, INTENTE NUEVAMENTE');
    }

    public function destroy($id){
        $grupo = GrupoCorte::find($id);

        if($grupo){
            $grupo->delete();
            $mensaje = 'SE HA ELIMINADO EL GRUPO DE CORTE CORRECTAMENTE';
            return redirect('empresa/grupos-corte')->with('success', $mensaje);
        }else{
            return redirect('empresa/grupos-corte')->with('danger', 'GRUPO DE CORTE NO ENCONTRADO, INTENTE NUEVAMENTE');
        }
    }

    public function act_des($id){
        $grupo = GrupoCorte::find($id);

        if($grupo){
            if($grupo->status == 0){
                $grupo->status = 1;
                $mensaje = 'SE HA HABILITADO EL GRUPO DE CORTE CORRECTAMENTE';
            }else{
                $grupo->status = 0;
                $mensaje = 'SE HA DESHABILITADO EL GRUPO DE CORTE CORRECTAMENTE';
            }
            $grupo->save();
            return redirect('empresa/grupos-corte')->with('success', $mensaje);
        }else{
            return redirect('empresa/grupos-corte')->with('danger', 'GRUPO DE CORTE NO ENCONTRADO, INTENTE NUEVAMENTE');
        }
    }

    public function state_lote($grupos, $state){
        $this->getAllPermissions(Auth::user()->id);

        $succ = 0; $fail = 0;

        $grupos = explode(",", $grupos);

        for ($i=0; $i < count($grupos) ; $i++) {
            $grupo = GrupoCorte::find($grupos[$i]);

            if($grupo){
                if($state == 'disabled'){
                    $grupo->status = 0;
                }elseif($state == 'enabled'){
                    $grupo->status = 1;
                }
                $grupo->save();
                $succ++;
            }else{
                $fail++;
            }
        }

        return response()->json([
            'success'   => true,
            'fallidos'  => $fail,
            'correctos' => $succ,
            'state'     => $state
        ]);
    }

    public function destroy_lote($grupos){
        $this->getAllPermissions(Auth::user()->id);

        $succ = 0; $fail = 0;

        $grupos = explode(",", $grupos);

        for ($i=0; $i < count($grupos) ; $i++) {
            $grupo = GrupoCorte::find($grupos[$i]);
            if ($grupo->uso()==0) {
                $grupo->delete();
                $succ++;
            } else {
                $fail++;
            }
        }

        return response()->json([
            'success'   => true,
            'fallidos'  => $fail,
            'correctos' => $succ,
            'state'     => 'eliminados'
        ]);
    }

    public function opcion_masiva(){
        $this->getAllPermissions(Auth::user()->id);
        view()->share(['title' => 'Opciones Masivas a Contratos']);
        $grupos_corte = GrupoCorte::get();
        return view('grupos-corte.opcionmasiva',compact('grupos_corte'));
    }

    public function gruposOpcionesMasivas(Request $request){
        $modoLectura = auth()->user()->modo_lectura();
        $grupos = GrupoCorte::query()
            ->where('empresa', Auth::user()->empresa);
        if ($request->filtro == true) {
            if($request->nombre){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('nombre', 'like', "%{$request->nombre}%");
                });
            }
            if($request->fecha_factura){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('fecha_factura', 'like', "%{$request->fecha_factura}%");
                });
            }
            if($request->fecha_pago){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('fecha_pago', 'like', "%{$request->fecha_pago}%");
                });
            }
            if($request->fecha_corte){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('fecha_corte', 'like', "%{$request->fecha_corte}%");
                });
            }
            if($request->fecha_suspension){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('fecha_suspension', 'like', "%{$request->fecha_suspension}%");
                });
            }
            if($request->status >= 0){
                $grupos->where(function ($query) use ($request) {
                    $query->orWhere('status', 'like', "%{$request->status}%");
                });
            }
        }

        return datatables()->eloquent($grupos)
            ->editColumn('id', function (GrupoCorte $grupo) {
                return $grupo->id;
            })
            ->editColumn('nombre', function (GrupoCorte $grupo) {
                return "<a href=" . route('grupos-corte.show', $grupo->id) . ">{$grupo->nombre}</div></a>";
            })
            ->editColumn('fecha_factura', function (GrupoCorte $grupo) {
                return ($grupo->fecha_factura == 0) ? 'No aplica' : $grupo->fecha_factura;
            })
            ->editColumn('fecha_pago', function (GrupoCorte $grupo) {
                return ($grupo->fecha_pago == 0) ? 'No aplica' : $grupo->fecha_pago;
            })
            ->editColumn('fecha_corte', function (GrupoCorte $grupo) {
                return ($grupo->fecha_corte == 0) ? 'No aplica' : $grupo->fecha_corte;
            })
            ->editColumn('fecha_suspension', function (GrupoCorte $grupo) {
                return ($grupo->fecha_suspension == 0) ? 'No aplica' : $grupo->fecha_suspension;
            })
            ->editColumn('hora_suspension', function (GrupoCorte $grupo) {
                return date('g:i A', strtotime($grupo->hora_suspension));
            })
            ->editColumn('status', function (GrupoCorte $grupo) {
                return "<span class='text-{$grupo->status("true")}'><strong>{$grupo->status()}</strong></span>";
            })
            ->addColumn('acciones', $modoLectura ?  "" : "grupos-corte.acciones")
            ->rawColumns(['acciones', 'nombre', 'id', 'status'])
            ->toJson();
    }

    public function estadosGruposCorte($grupo = null, $fecha = null){

        $this->getAllPermissions(Auth::user()->id);

        view()->share(['inicio' => 'master', 'seccion' => 'zonas', 'subseccion' => 'estados_corte', 'title' => 'Estados de corte', 'icon' => 'fas fa-project-diagram']);

        if($grupo == 'all'){
            $grupo = null;
        }

        if(!$fecha){
            $fecha = date('Y-m-d');
        }

        if($grupo != null){
            $grupoSeleccionado = GrupoCorte::find($grupo);
            $fecha =  date('Y-m').'-'.$grupoSeleccionado->fecha_suspension;
            $fecha = Carbon::create($fecha)->format('Y-m-d');
        }

        $swGrupo = 1; //masivo
        // $grupos_corte = GrupoCorte::where('fecha_suspension', date('d') * 1)->where('hora_suspension','<=', date('H:i'))->where('hora_suspension_limit','>=', date('H:i'))->where('status', 1)->count();
        $grupos_corte = GrupoCorte::where('hora_suspension','<=', date('H:i'))->where('hora_suspension_limit','>=', date('H:i'))->where('status', 1)->where('fecha_suspension','!=',0)->get();
        $perdonados = 0;


        if(false){
            $grupos_corte_array = array();
            foreach($grupos_corte as $grupo){
                array_push($grupos_corte_array,$grupo->id);
            }

            $contactos = Contacto::join('factura as f','f.cliente','=','contactos.id')->
                join('contracts as cs','cs.client_id','=','contactos.id')->
                join('grupos_corte as gp', 'gp.id', '=', 'cs.grupo_corte')->
                select('gp.nombre as grupo', 'gp.id as idGrupo', 'contactos.id', 'contactos.nombre', 'contactos.nit', 'f.id as factura', 'f.codigo', 'f.estatus', 'f.suspension', 'cs.state', 'f.contrato_id')->
                where('f.estatus',1)->
                whereIn('f.tipo', [1,2])->
                where('f.vencimiento', $fecha)->
                where('contactos.status',1)->
                where('cs.state','enabled')->
                whereIn('cs.grupo_corte',$grupos_corte_array)->
                where('cs.fecha_suspension', null);

                if($grupo){
                    $contactos->where('gp.id', $grupo);
                }

                $contactos = $contactos->get()->all();
                $swGrupo = 1; //masivo
        }else{
            $contactos = Contacto::join('factura as f','f.cliente','=','contactos.id')->
            join('contracts as cs','cs.client_id','=','contactos.id')->
            join('grupos_corte as gp', 'gp.id', '=', 'cs.grupo_corte')->
            select('gp.nombre as grupo', 'gp.id as idGrupo', 'contactos.id', 'contactos.nombre', 'contactos.nit', 'f.id as factura', 'f.estatus', 'f.suspension', 'f.codigo', 'cs.state', 'f.contrato_id')->
            where('f.estatus',1)->
            whereIn('f.tipo', [1,2])->
            where('f.vencimiento', $fecha)->
            where('contactos.status',1)->
            where('cs.state','enabled')->
            where('cs.fecha_suspension', null);

            if($grupo){
                $contactos->where('gp.id', $grupo);
            }


            $contactos = $contactos->get()->all();
           // dd($contactos);
            $swGrupo = 0; // personalizado
        }

        if($contactos){
            foreach ($contactos as $key => $contacto) {
                $contrato = Contrato::find($contacto->contrato_id);
                $promesaExtendida = DB::table('promesa_pago')->where('factura', $contacto->factura)->where('vencimiento', '>=', $fecha)->count();
                if($promesaExtendida > 0){
                    unset($contactos[$key]);
                    $perdonados++;
                }
            }
        }

        $contactos = collect($contactos);
        $totalFacturas = $contactos->count();
        $contactos = $contactos->groupBy('idGrupo');
        $gruposFaltantes = GrupoCorte::whereIn('id', $contactos->keys())->get();

        $grupos_corte = GrupoCorte::get();

        $facturasCortadas = Factura::select('factura.*', 'contactos.nombre as nombreCliente', 'gp.nombre as nombreGrupo', 'gp.hora_suspension', 'gp.id as idGrupo')->
                                     join('contactos', 'contactos.id', '=', 'factura.cliente')->
                                     join('contracts as cs','cs.client_id','=','contactos.id')->
                                     join('grupos_corte as gp', 'gp.id', '=', 'cs.grupo_corte')->
                                     where('vencimiento', $fecha)->
                                     where('estatus', 1)->
                                     whereIn('tipo', [1,2])->
                                     where('cs.state','disabled');

        if($grupo){
            $facturasCortadas = $facturasCortadas->where('gp.id', $grupo);
        }


        $facturasCortadas = $facturasCortadas->groupBy('factura.id')->
                                     orderby('id', 'desc')->
                                     get();



        $facturasGeneradas = Factura::select('factura.*', 'contactos.nombre as nombreCliente', 'gp.nombre as nombreGrupo', 'gp.hora_suspension', 'gp.id as idGrupo')->
                                     join('contactos', 'contactos.id', '=', 'factura.cliente')->
                                     join('contracts as cs','cs.client_id','=','contactos.id')->
                                     join('grupos_corte as gp', 'gp.id', '=', 'cs.grupo_corte')->
                                     where('vencimiento', $fecha)->
                                     whereIn('tipo', [1,2])->
                                     where('factura.facturacion_automatica', 1);

        if($grupo){
            $facturasGeneradas = $facturasGeneradas->where('gp.id', $grupo);
        }


        $facturasGeneradas =  $facturasGeneradas->groupBy('factura.id')->
                                     orderby('id', 'desc')->
                                     get();



        $request = request();

        $cantidadContratos = Contrato::select('contracts.id')
                                        ->join('grupos_corte', 'grupos_corte.id', '=', 'contracts.grupo_corte')
                                        ->where('grupos_corte.fecha_suspension', Carbon::create($fecha)->format('d') * 1)
                                        ->where('grupos_corte.status', 1);


                                        if($grupo){
                                            $cantidadContratos->where('grupos_corte.id', $grupo);
                                        }

        $cantidadContratos = $cantidadContratos->count();

        return view('grupos-corte.estados', compact('contactos', 'gruposFaltantes', 'perdonados', 'grupo', 'fecha', 'totalFacturas', 'grupos_corte', 'facturasCortadas', 'request', 'facturasGeneradas', 'cantidadContratos'));
    }

    /**
     * Vista principal de análisis de ciclos de facturación
     */
    public function analisisCiclo($idGrupo, $periodo = null)
    {
        $this->getAllPermissions(Auth::user()->id);
        
        view()->share([
            'inicio' => 'master', 
            'seccion' => 'zonas', 
            'subseccion' => 'analisis_ciclo', 
            'title' => 'Análisis de Ciclos de Facturación', 
            'icon' => 'fas fa-chart-bar'
        ]);

        $grupo = GrupoCorte::find($idGrupo);
        
        if (!$grupo) {
            return redirect('empresa/grupos-corte')->with('danger', 'GRUPO DE CORTE NO ENCONTRADO');
        }

        // Si no se especifica período, usar el mes actual
        if (!$periodo) {
            $periodo = Carbon::now()->format('Y-m');
        }

        $analyzer = new BillingCycleAnalyzer();
        
        // Obtener estadísticas del ciclo
        $cycleStats = $analyzer->getCycleStats($idGrupo, $periodo);
        
        // Obtener datos históricos para gráficas (últimos 6 meses)
        $historicalData = $analyzer->getHistoricalData($idGrupo, 6);
        
        // Calcular métricas comparativas
        $promedioFacturas = count($historicalData) > 0 
            ? round(collect($historicalData)->avg('generadas'), 2) 
            : 0;
        
        // Variación vs mes anterior
        $variacionMesAnterior = 0;
        if (count($historicalData) >= 2) {
            $mesActual = end($historicalData);
            $mesAnterior = $historicalData[count($historicalData) - 2];
            
            if ($mesAnterior['generadas'] > 0) {
                $variacionMesAnterior = round(
                    (($mesActual['generadas'] - $mesAnterior['generadas']) / $mesAnterior['generadas']) * 100, 
                    2
                );
            }
        }

        // Obtener lista completa de grupos para la navegación
        $grupos = GrupoCorte::where('empresa', Auth::user()->empresa)
            ->where('status', 1)
            ->get();

        // Empresa
        $empresa = Empresa::find(Auth::user()->empresa);

        return view('grupos-corte.analisis-ciclo', compact(
            'grupo', 
            'periodo', 
            'cycleStats', 
            'historicalData', 
            'promedioFacturas', 
            'variacionMesAnterior',
            'grupos',
            'empresa'
        ));
    }

    /**
     * API: Obtiene lista de ciclos disponibles para el selector
     */
    public function getCiclosDisponibles($idGrupo)
    {
        $analyzer = new BillingCycleAnalyzer();
        $ciclos = $analyzer->getAvailableCycles($idGrupo);
        
        return response()->json([
            'success' => true,
            'ciclos' => $ciclos
        ]);
    }

    /**
     * API: Obtiene datos completos de un ciclo específico
     */
    public function getCycleData($idGrupo, $periodo)
    {
        $analyzer = new BillingCycleAnalyzer();
        
        // Estadísticas del ciclo
        $cycleStats = $analyzer->getCycleStats($idGrupo, $periodo);
        
        // Datos históricos
        $historicalData = $analyzer->getHistoricalData($idGrupo, 6);
        
        // Métricas comparativas
        $promedioFacturas = count($historicalData) > 0 
            ? round(collect($historicalData)->avg('generadas'), 2) 
            : 0;
        
        $variacionMesAnterior = 0;
        if (count($historicalData) >= 2) {
            $ultimoIndice = count($historicalData) - 1;
            $mesActual = $historicalData[$ultimoIndice];
            $mesAnterior = $historicalData[$ultimoIndice - 1];
            
            if ($mesAnterior['generadas'] > 0) {
                $variacionMesAnterior = round(
                    (($mesActual['generadas'] - $mesAnterior['generadas']) / $mesAnterior['generadas']) * 100, 
                    2
                );
            }
        }
        
        return response()->json([
            'success' => true,
            'cycleStats' => $cycleStats,
            'historicalData' => $historicalData,
            'metricas' => [
                'promedio_facturas' => $promedioFacturas,
                'variacion_mes_anterior' => $variacionMesAnterior
            ]
        ]);
    }

    /**
     * Habilitar la facturación para contratos OFF
     */
    public function habilitarFacturacionOff()
    {
        $empresa = Empresa::find(1);
        $empresa->factura_contrato_off = 1;
        $empresa->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Configuración de empresa actualizada: Ahora se permiten facturas en contratos deshabilitados.'
        ]);
    }

    /**
     * Generar facturas faltantes de forma manual
     */
    public function generarFacturasFaltantes(Request $request)
    {
        $idGrupo = $request->idGrupo;
        $periodo = $request->periodo;
        
        if (!$idGrupo || !$periodo) {
            return response()->json(['success' => false, 'message' => 'Faltan parámetros requeridos.'], 400);
        }

        $grupo = GrupoCorte::find($idGrupo);
        if (!$grupo) {
            return response()->json(['success' => false, 'message' => 'Grupo no encontrado.'], 404);
        }

        list($year, $month) = explode('-', $periodo);
        $dia = $grupo->fecha_factura;
        if ($dia == 0) $dia = 1;
        
        $ultimoDiaMes = Carbon::create($year, $month, 1)->endOfMonth()->day;
        if ($dia > $ultimoDiaMes) $dia = $ultimoDiaMes;
        
        $fechaRef = Carbon::create($year, $month, $dia)->format('Y-m-d');
        
        try {
            CronController::CrearFactura($fechaRef, $idGrupo);
            
            // Invalidar caché
            $cacheKey = "cycle_stats_v5_{$idGrupo}_{$periodo}";
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            
            return response()->json([
                'success' => true, 
                'message' => 'Proceso de generación de facturas finalizado para el grupo ' . $grupo->nombre
            ]);
        } catch (\Exception $e) {
            Log::error("Error en generación manual de facturas: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Ocurrió un error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar configuración de la empresa de forma genérica
     */
    public function updateEmpresaConfig(Request $request)
    {
        $field = $request->field;
        $value = $request->value;
        
        $validFields = [
            'factura_auto', 
            'aplicar_saldofavor', 
            'cron_fact_abiertas', 
            'factura_contrato_off', 
            'prorrateo'
        ];
        
        if (!in_array($field, $validFields)) {
            return response()->json(['success' => false, 'message' => 'Campo no válido.'], 400);
        }
        
        $empresa = Empresa::find(Auth::user()->empresa);
        $empresa->$field = $value;
        $empresa->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada correctamente.'
        ]);
    }

    /**
     * Marcar en lote las facturas manuales como "Factura del Mes"
     */
    public function marcarFacturasMesLote(Request $request)
    {
        $idGrupo = $request->idGrupo;
        $periodo = $request->periodo;
        
        if (!$idGrupo || !$periodo) {
            return response()->json(['success' => false, 'message' => 'Faltan parámetros requeridos.'], 400);
        }

        try {
            $analyzer = new \App\Services\BillingCycleAnalyzer();
            $marcadas = $analyzer->marcarFacturasMesLote($idGrupo, $periodo);
            
            // Invalidar caché
            $cacheKey = "cycle_stats_v13_{$idGrupo}_{$periodo}";
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            
            return response()->json([
                'success' => true, 
                'message' => "Se han vinculado {$marcadas} facturas manuales al ciclo actual correctamente."
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error en marcado manual de facturas: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Ocurrió un error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar facturas duplicadas de manera segura
     */
    public function eliminarFacturaDuplicada(Request $request)
    {
        $facturaId = $request->factura_id;

        if (!$facturaId) {
            return response()->json([
                'success' => false,
                'message' => 'ID de factura requerido'
            ], 400);
        }

        try {
            $factura = Factura::find($facturaId);
            
            if (!$factura) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada'
                ], 404);
            }

            // Validar que la factura no tenga pagos asociados
            if ($factura->pagado() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una factura con pagos asociados'
                ], 400);
            }

            // Eliminar dependencias
            DB::table('facturas_contratos')->where('factura_id', $factura->id)->delete();
            ItemsFactura::where('factura', $factura->id)->delete();
            DB::table('crm')->where('factura', $factura->id)->delete();

            // Eliminar factura
            $factura->delete();

            // Limpiar caché
            \Cache::tags(['cycle_stats'])->flush();

            return response()->json([
                'success' => true,
                'message' => 'Factura eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar factura duplicada: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar masivamente todas las facturas duplicadas de un ciclo
     */
    public function eliminarMasivoDuplicados(Request $request)
    {
        $idGrupo = $request->idGrupo;
        $periodo = $request->periodo;
        $contratoId = $request->contrato_id; // Opcional: eliminar solo duplicados de un contrato específico

        if (!$idGrupo || !$periodo) {
            return response()->json([
                'success' => false,
                'message' => 'Faltan parámetros requeridos'
            ], 400);
        }

        try {
            $analyzer = new \App\Services\BillingCycleAnalyzer();
            $cycleStats = $analyzer->getCycleStats($idGrupo, $periodo);
            
            if (!isset($cycleStats['duplicates_analysis']) || 
                $cycleStats['duplicates_analysis']['total_excedentes'] == 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'No se encontraron facturas duplicadas para eliminar',
                    'eliminadas' => 0
                ]);
            }

            $eliminadas = 0;
            $noPudoEliminar = 0;
            $contratos_duplicados = $cycleStats['duplicates_analysis']['contratos_duplicados'];

            // Si se especifica un contrato, filtrar solo ese
            if ($contratoId) {
                $contratos_duplicados = array_filter($contratos_duplicados, function($dup) use ($contratoId) {
                    return $dup['contrato_id'] == $contratoId;
                });
            }

            foreach ($contratos_duplicados as $dup) {
                // Ordenar facturas por fecha (más recientes primero) para mantener la más antigua
                $facturas = collect($dup['facturas'])->sortByDesc('fecha')->values();
                
                // Saltar la primera (más reciente, la mantenemos)
                for ($i = 1; $i < $facturas->count(); $i++) {
                    $facturaId = $facturas[$i]['id'];
                    $factura = Factura::find($facturaId);
                    
                    if (!$factura) {
                        continue;
                    }

                    // Validar que no tenga pagos
                    if ($factura->pagado() > 0) {
                        $noPudoEliminar++;
                        continue;
                    }

                    // Eliminar dependencias
                    DB::table('facturas_contratos')->where('factura_id', $factura->id)->delete();
                    ItemsFactura::where('factura', $factura->id)->delete();
                    DB::table('crm')->where('factura', $factura->id)->delete();

                    // Eliminar factura
                    $factura->delete();
                    $eliminadas++;
                }
            }

            // Limpiar caché
            \Cache::tags(['cycle_stats'])->flush();

            $mensaje = "Se eliminaron {$eliminadas} facturas duplicadas correctamente";
            if ($noPudoEliminar > 0) {
                $mensaje .= ". {$noPudoEliminar} facturas no se pudieron eliminar por tener pagos asociados";
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'eliminadas' => $eliminadas,
                'no_pudieron_eliminar' => $noPudoEliminar
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar masivamente duplicados: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }
}
