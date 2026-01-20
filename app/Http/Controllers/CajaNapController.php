<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Mail;
use Validator;
use Illuminate\Validation\Rule;
use DB;
use Session;

use App\Mikrotik;
use App\User;
use App\Contrato;
use App\CajaNap;
use App\Spliter;
use App\Campos;
use Illuminate\Support\Facades\Auth;

class CajaNapController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
        set_time_limit(300);
        view()->share(['inicio' => 'master', 'seccion' => 'zonas', 'subseccion' => 'cajas naps', 'title' => 'Cajas Naps', 'icon' =>'fas fa-sitemap']);
    }

    public function index(Request $request){
        $this->getAllPermissions(Auth::user()->id);
        return view('cajas-naps.index');
    }

    public function cajasNaps(Request $request){
        $modoLectura = auth()->user()->modo_lectura();
        $cajasNaps = CajaNap::query();

        if ($request->filtro == true) {
            if($request->nombre && $request->nombre != ''){
                $cajasNaps->where('nombre', 'like', "%{$request->nombre}%");
            }
            if($request->status !== null && $request->status !== '' && $request->status >= 0){
                $cajasNaps->where('status', '=', $request->status);
            }
        }

        return datatables()->eloquent($cajasNaps)
            ->editColumn('nombre', function (CajaNap $cajaNap) {
                return "<a href=" . route('caja.naps.show', $cajaNap->id) . ">{$cajaNap->nombre}</a>";
            })
            ->editColumn('spliter_asociado', function (CajaNap $cajaNap) {
                $spliter = Spliter::find($cajaNap->spliter_asociado);
                return $spliter ? $spliter->nombre : 'N/A';
            })
            ->editColumn('cant_puertos', function (CajaNap $cajaNap) {
                return $cajaNap->cant_puertos;
            })
            ->editColumn('ubicacion', function (CajaNap $cajaNap) {
                return $cajaNap->ubicacion;
            })
            ->editColumn('coordenadas', function (CajaNap $cajaNap) {
                return $cajaNap->coordenadas;
            })
            ->editColumn('puertos_disponibles', function (CajaNap $cajaNap) {
                $disponibles = $cajaNap->contarPuertosDisponibles();
                return "{$disponibles} / {$cajaNap->cant_puertos}";
            })
            ->editColumn('status', function (CajaNap $cajaNap) {
                return "<span class='text-{$cajaNap->status("true")}'><strong>{$cajaNap->status()}</strong></span>";
            })
            ->addColumn('acciones', function (CajaNap $cajaNap) use ($modoLectura) {
                return view('cajas-naps.acciones', [
                    'id' => $cajaNap->id,
                    'caja_naps_disponible' => $cajaNap->contarPuertosDisponibles(),
                    'cant_puertos' => $cajaNap->cant_puertos
                ])->render();
            })
            ->rawColumns(['acciones', 'nombre', 'status'])
            ->toJson();
    }

    public function create(){
        $this->getAllPermissions(Auth::user()->id);
        view()->share(['title' => 'Nueva Caja Naps']);
         $spliters = Spliter::all(); // Obtener todos los splitters

        return view('cajas-naps.create',compact('spliters'));
    }

    public function store(Request $request){
      /*  $nro = 0;
        $nodo = Nodo::where('id', '>', 0)->where('empresa', Auth::user()->empresa)->orderBy('created_at', 'desc')->first();

        if($nodo){
            $nro = $nodo->nro + 1;
        }*/

        $caja_naps = new CajaNap;
        $caja_naps->nombre = $request->nombre;
        $caja_naps->spliter_asociado = $request->spliter_asociado;
        $caja_naps->cant_puertos = $request->cant_puertos;
        $caja_naps->ubicacion = $request->ubicacion;
        $caja_naps->coordenadas = $request->coordenadas;
        $caja_naps->caja_naps_disponible = $request->caja_naps_disponible;
        $caja_naps->status = $request->status;
        $caja_naps->descripcion = $request->descripcion;
        $caja_naps->created_by = Auth::user()->id;
        $caja_naps->save();

        $mensaje='SE HA CREADO SATISFACTORIAMENTE LA CAJA NAPS';
        return redirect('caja-naps')->with('success', $mensaje);
    }

    public function show($id){
        $this->getAllPermissions(Auth::user()->id);
        $caja_nap = CajaNap::find($id);
        $spliter = Spliter::find($caja_nap->spliter_asociado);

        if ($caja_nap) {
            view()->share(['title' => $caja_nap->nombre]);
            return view('cajas-naps.show')->with(compact('caja_nap', 'spliter'));
        }
        return redirect('caja-naps')->with('danger', 'CAJA NAP NO ENCONTRADA, INTENTE NUEVAMENTE');
    }

    public function edit($id){
        $this->getAllPermissions(Auth::user()->id);
        $caja_nap = CajaNap::find($id);
        $spliters = Spliter::all();

        if ($caja_nap) {
            view()->share(['title' => 'Editar Caja Nap: '.$caja_nap->nombre]);
            return view('cajas-naps.edit')->with(compact('caja_nap', 'spliters'));
        }
        return redirect('caja-naps')->with('danger', 'CAJA NAP NO ENCONTRADA, INTENTE NUEVAMENTE');
    }

    public function update(Request $request, $id){
        $caja_nap = CajaNap::find($id);

        if ($caja_nap) {
            $caja_nap->nombre = $request->nombre;
            $caja_nap->spliter_asociado = $request->spliter_asociado;
            $caja_nap->cant_puertos = $request->cant_puertos;
            $caja_nap->ubicacion = $request->ubicacion;
            $caja_nap->coordenadas = $request->coordenadas;
            $caja_nap->caja_naps_disponible = $request->caja_naps_disponible;
            $caja_nap->status = $request->status;
            $caja_nap->descripcion = $request->descripcion;
            $caja_nap->updated_by = Auth::user()->id;
            $caja_nap->save();

            $mensaje='SE HA MODIFICADO SATISFACTORIAMENTE LA CAJA NAP';
            return redirect('caja-naps')->with('success', $mensaje);
        }
        return redirect('caja-naps')->with('danger', 'CAJA NAP NO ENCONTRADA, INTENTE NUEVAMENTE');
    }

    public function destroy($id){
        $caja_nap = CajaNap::find($id);

        if($caja_nap){
            // Validar que caja_naps_disponible sea igual a cant_puertos
            if($caja_nap->caja_naps_disponible == $caja_nap->cant_puertos){
                $caja_nap->delete();
                $mensaje = 'SE HA ELIMINADO LA CAJA NAP CORRECTAMENTE';
                return redirect('caja-naps')->with('success', $mensaje);
            }else{
                return redirect('caja-naps')->with('danger', 'NO SE PUEDE ELIMINAR LA CAJA NAP. LA CANTIDAD DE PUERTOS DISPONIBLES DEBE SER IGUAL A LA CANTIDAD TOTAL DE PUERTOS');
            }
        }else{
            return redirect('caja-naps')->with('danger', 'CAJA NAP NO ENCONTRADA, INTENTE NUEVAMENTE');
        }
    }

    public function act_des($id){
        $nodo = Nodo::where('id', $id)->where('empresa', Auth::user()->empresa)->first();

        if($nodo){
            if($nodo->status == 0){
                $nodo->status = 1;
                $mensaje = 'SE HA HABILITADO EL NODO CORRECTAMENTE';
            }else{
                $nodo->status = 0;
                $mensaje = 'SE HA DESHABILITADO EL NODO CORRECTAMENTE';
            }
            $nodo->save();
            return redirect('empresa/nodos')->with('success', $mensaje);
        }else{
            return redirect('empresa/nodos')->with('danger', 'NODO NO ENCONTRADO, INTENTE NUEVAMENTE');
        }
    }

    public function state_lote($nodos, $state){
        $this->getAllPermissions(Auth::user()->id);

        $succ = 0; $fail = 0;

        $nodos = explode(",", $nodos);

        for ($i=0; $i < count($nodos) ; $i++) {
            $nodo = Nodo::find($nodos[$i]);

            if($nodo){
                if($state == 'disabled'){
                    $nodo->status = 0;
                }elseif($state == 'enabled'){
                    $nodo->status = 1;
                }
                $nodo->save();
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

    public function destroy_lote($nodos){
        $this->getAllPermissions(Auth::user()->id);

        $succ = 0; $fail = 0;

        $nodos = explode(",", $nodos);

        for ($i=0; $i < count($nodos) ; $i++) {
            $nodo = Nodo::find($nodos[$i]);
            if ($nodo->uso()==0) {
                $nodo->delete();
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

    public function getPuertosDisponibles($cajaNapId, $contratoId = null){
        $cajaNap = CajaNap::find($cajaNapId);

        if (!$cajaNap) {
            return response()->json(['error' => 'Caja NAP no encontrada'], 404);
        }

        // Obtener todos los puertos ocupados por otros contratos (excluyendo el contrato actual si se está editando)
        $puertosOcupados = Contrato::where('cajanap_id', $cajaNapId)
            ->where('status', 1);

        if ($contratoId) {
            $puertosOcupados->where('id', '!=', $contratoId);
        }

        $puertosOcupados = $puertosOcupados->pluck('cajanap_puerto')->toArray();

        // Generar lista de todos los puertos posibles (1 hasta cant_puertos)
        $todosLosPuertos = range(1, $cajaNap->cant_puertos);

        // Filtrar los puertos disponibles (excluir los ocupados)
        $puertosDisponibles = array_diff($todosLosPuertos, $puertosOcupados);

        return response()->json([
            'puertos_disponibles' => array_values($puertosDisponibles),
            'cant_puertos' => $cajaNap->cant_puertos,
            'puertos_ocupados' => $puertosOcupados
        ]);
    }
}
