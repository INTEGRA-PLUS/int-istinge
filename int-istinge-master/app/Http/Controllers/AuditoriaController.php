<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;

use App\MovimientoLOG;
use App\User;
use App\Contacto;
use App\Factura;

class AuditoriaController extends Controller
{
    /**
    * Create a new controller instance.
    *
    * @return void
    */
    public function __construct(){
        $this->middleware('auth');
        set_time_limit(300);
        view()->share(['seccion' => 'auditoria', 'title' => 'Auditorías', 'icon' =>'fas fa-user-secret']);
    }

    public function contratos(){
        $this->getAllPermissions(Auth::user()->id);
        $usuarios = User::where('empresa',Auth::user()->empresa)->where('user_status', 1)->get();
        $clientes = (Auth::user()->empresa()->oficina) ? Contacto::whereIn('tipo_contacto', [0,2])->where('status', 1)->where('empresa', Auth::user()->empresa)->where('oficina', Auth::user()->oficina)->orderBy('nombre', 'ASC')->get() : Contacto::whereIn('tipo_contacto', [0,2])->where('status', 1)->where('empresa', Auth::user()->empresa)->orderBy('nombre', 'ASC')->get();

        view()->share(['subseccion' => 'auditoria-contratos', 'title' => 'Auditorías de Contratos', 'icon' =>'fas fa-user-secret']);
        return view('auditorias.index')->with(compact('usuarios', 'clientes'));
    }

    public function auditoria_contratos(Request $request){
        $modoLectura = auth()->user()->modo_lectura();
        $movimientos = MovimientoLOG::query()
        ->join('contracts as cs', 'cs.id', '=', 'log_movimientos.contrato')
        ->join('contactos as c', 'c.id', '=', 'cs.client_id')
        ->select('log_movimientos.*', 'cs.id as cs_id', 'cs.nro as cs_nro', 'cs.ip as cs_ip', 'c.nombre', 'c.apellido1', 'c.apellido2');

        if ($request->filtro == true) {
            if($request->client_id){
                $movimientos->where(function ($query) use ($request) {
                    $query->orWhere('c.id', $request->client_id);
                });
            }
            if($request->contrato){
                $movimientos->where(function ($query) use ($request) {
                    $query->orWhere('log_movimientos.contrato', $request->contrato);
                });
            }
            if($request->ip){
                $movimientos->where(function ($query) use ($request) {
                    $query->orWhere('cs.ip', 'like', "%{$request->ip}%");
                });
            }
            if($request->created_by){
                $movimientos->where(function ($query) use ($request) {
                    $query->orWhere('log_movimientos.created_by', $request->created_by);
                });
            }

            if($request->desde){
                $movimientos->where(function ($query) use ($request) {
                    $query->whereDate('log_movimientos.created_at', '>=', Carbon::parse($request->desde)->format('Y-m-d'));
                });
            }
            if($request->hasta){
                $movimientos->where(function ($query) use ($request) {
                    $query->whereDate('log_movimientos.created_at', '<=', Carbon::parse($request->hasta)->format('Y-m-d'));
                });
            }
        }

        return datatables()->eloquent($movimientos)
            ->editColumn('contrato', function (MovimientoLOG $movimiento) {
                return $movimiento->cs_nro;
            })
            ->editColumn('ip', function (MovimientoLOG $movimiento) {
                return $movimiento->cs_ip;
            })
            ->editColumn('cliente', function (MovimientoLOG $movimiento) {
                return $movimiento->nombre.' '.$movimiento->apellido1.' '.$movimiento->apellido2;
            })
            ->editColumn('created_at', function (MovimientoLOG $movimiento) {
                return date('d-m-Y g:i:s A', strtotime($movimiento->created_at));
            })
            ->editColumn('created_by', function (MovimientoLOG $movimiento) {
                return $movimiento->created_by();
            })
            ->editColumn('descripcion', function (MovimientoLOG $movimiento) {
                return $movimiento->descripcion;
            })
            ->rawColumns(['contrato', 'cliente', 'created_at', 'created_by', 'descripcion'])
            ->toJson();
    }

    public function facturas(){
        $this->getAllPermissions(Auth::user()->id);
        $usuarios = User::where('empresa',Auth::user()->empresa)->where('user_status', 1)->get();
        $clientes = (Auth::user()->empresa()->oficina) ? Contacto::whereIn('tipo_contacto', [0,2])->where('status', 1)->where('empresa', Auth::user()->empresa)->where('oficina', Auth::user()->oficina)->orderBy('nombre', 'ASC')->get() : Contacto::whereIn('tipo_contacto', [0,2])->where('status', 1)->where('empresa', Auth::user()->empresa)->orderBy('nombre', 'ASC')->get();

        view()->share(['subseccion' => 'auditoria-facturas', 'title' => 'Auditorías de Facturas', 'icon' =>'fas fa-user-secret']);
        return view('auditorias.facturas')->with(compact('usuarios', 'clientes'));
    }

    public function auditoria_facturas(Request $request){
        $modoLectura = auth()->user()->modo_lectura();
        $movimientos = MovimientoLOG::query()
        ->join('factura as f', 'f.id', '=', 'log_movimientos.contrato')
        ->join('contactos as c', 'c.id', '=', 'f.cliente')
        ->where('log_movimientos.modulo', 8)
        ->select('log_movimientos.*', 'f.id as factura_id', 'f.codigo as factura_codigo', 'c.nombre', 'c.apellido1', 'c.apellido2');

        if ($request->filtro == true) {
            if($request->client_id){
                $movimientos->where(function ($query) use ($request) {
                    $query->orWhere('c.id', $request->client_id);
                });
            }
            if($request->created_by){
                $movimientos->where(function ($query) use ($request) {
                    $query->orWhere('log_movimientos.created_by', $request->created_by);
                });
            }
            if($request->desde){
                $movimientos->where(function ($query) use ($request) {
                    $query->whereDate('log_movimientos.created_at', '>=', Carbon::parse($request->desde)->format('Y-m-d'));
                });
            }
            if($request->hasta){
                $movimientos->where(function ($query) use ($request) {
                    $query->whereDate('log_movimientos.created_at', '<=', Carbon::parse($request->hasta)->format('Y-m-d'));
                });
            }
            if($request->tipo){
                $movimientos->where(function ($query) use ($request) {
                    switch($request->tipo) {
                        case 'creacion_ingresos':
                            $query->where('log_movimientos.descripcion', 'like', 'Se creo un ingreso de factura%');
                            break;
                        case 'envio_siigo':
                            $query->where('log_movimientos.descripcion', 'like', '%Factura enviada a siigo%');
                            break;
                        case 'cambio_electronica':
                            $query->where('log_movimientos.descripcion', 'like', '%Factura convertida a electrónica%');
                            break;
                    }
                });
            }
        }

        return datatables()->eloquent($movimientos)
            ->editColumn('factura_codigo', function (MovimientoLOG $movimiento) {
                $url = url('/empresa/facturas/' . $movimiento->factura_id);
                return '<a href="'.$url.'">'.$movimiento->factura_codigo.'</a>';
            })
            ->editColumn('cliente', function (MovimientoLOG $movimiento) {
                return $movimiento->nombre.' '.$movimiento->apellido1.' '.$movimiento->apellido2;
            })
            ->editColumn('created_at', function (MovimientoLOG $movimiento) {
                return date('d-m-Y g:i:s A', strtotime($movimiento->created_at));
            })
            ->editColumn('created_by', function (MovimientoLOG $movimiento) {
                return $movimiento->created_by();
            })
            ->editColumn('tipo', function (MovimientoLOG $movimiento) {
                $descripcion = $movimiento->descripcion;
                if (strpos($descripcion, 'Se creo un ingreso de factura') !== false) {
                    return 'Creación de ingresos';
                } elseif (strpos($descripcion, 'Factura enviada a siigo') !== false) {
                    return 'Envio a siigo';
                } elseif (strpos($descripcion, 'Factura convertida a electrónica') !== false) {
                    return 'Cambio de estandar a electronica';
                }
                return '-';
            })
            ->editColumn('descripcion', function (MovimientoLOG $movimiento) {
                return $movimiento->descripcion;
            })
            ->rawColumns(['factura_codigo', 'cliente', 'created_at', 'created_by', 'tipo', 'descripcion'])
            ->toJson();
    }
}
