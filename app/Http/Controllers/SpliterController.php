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
use App\User;
use App\Contrato;
use App\Spliter;
use App\Campos;
use App\Nodo;

class SpliterController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
        set_time_limit(300);
        view()->share(['inicio' => 'master', 'seccion' => 'zonas', 'subseccion' => 'spliter', 'title' => 'Spliter Optico', 'icon' =>'fas fa-sitemap']);
    }

    public function index(Request $request){
        $this->getAllPermissions(Auth::user()->id);
        return view('spliter.index');
    }

    public function spliter(Request $request){

        $modoLectura = auth()->user()->modo_lectura();
        $splitters = Spliter::query();


        // if ($request->filtro == true) {
        //     if($request->nombre){
        //         $splitters->where(function ($query) use ($request) {
        //             $query->orWhere('nombre', 'like', "%{$request->nombre}%");
        //         });
        //     }
        //     if($request->status !== null && $request->status !== '' && $request->status >= 0){
        //         $splitters->where(function ($query) use ($request) {
        //             $query->orWhere('status', '=', $request->status);
        //         });
        //     }
        // }

        return datatables()->eloquent($splitters)
            ->editColumn('nombre', function (Spliter $spliter) {
                return "<a href=" . route('spliter.show', $spliter->id) . ">{$spliter->nombre}</a>";
            })
            ->editColumn('ubicacion', function (Spliter $spliter) {
                return $spliter->ubicacion;
            })
            ->editColumn('coordenadas', function (Spliter $spliter) {
                return $spliter->coordenadas;
            })
            ->editColumn('num_salida', function (Spliter $spliter) {
                return $spliter->num_salida;
            })
            ->editColumn('num_cajas_naps', function (Spliter $spliter) {
                return $spliter->num_cajas_naps;
            })
            ->editColumn('cajas_disponible', function (Spliter $spliter) {
                return $spliter->cajas_disponible;
            })
            ->editColumn('status', function (Spliter $spliter) {
                return "<span class='text-{$spliter->status("true")}'><strong>{$spliter->status()}</strong></span>";
            })
            ->addColumn('acciones', function (Spliter $spliter) use ($modoLectura) {
                if ($modoLectura) {
                    return '';
                }

                return view('spliter.acciones', [
                    'id'                => $spliter->id,
                    'status'            => $spliter->status,
                    'uso'               => $spliter->uso,
                    'session'           => $spliter->session,
                    'cajas_disponible'  => $spliter->cajas_disponible,
                    'num_cajas_naps'    => $spliter->num_cajas_naps,
                ])->render();
            })
            ->rawColumns(['acciones', 'nombre', 'status'])
            ->toJson();
    }

    public function create(){
        $this->getAllPermissions(Auth::user()->id);
        view()->share(['title' => 'Nuevo Spliter Optico']);

        return view('spliter.create');
    }

    public function store(Request $request){

      /*  $nro = 0;
        $nodo = Nodo::where('id', '>', 0)->where('empresa', Auth::user()->empresa)->orderBy('created_at', 'desc')->first();

        if($nodo){
            $nro = $nodo->nro + 1;
        }*/

        $spliter = new Spliter;
        $spliter->nombre = $request->nombre;
        $spliter->ubicacion= $request->ubicacion;
        $spliter->coordenadas = $request->coordenadas;
        $spliter->num_salida = $request->num_salida;
        $spliter->num_cajas_naps = $request->num_cajas_naps;
        $spliter->cajas_disponible = $request->cajas_disponible;
        $spliter->status = $request->status;
        $spliter->descripcion = $request->descripcion;
        $spliter->created_by = Auth::user()->id;
        $spliter->save();

        $mensaje='SE HA CREADO SATISFACTORIAMENTE EL SPLITER';
        return redirect('spliter/index')->with('success', $mensaje);
    }

    public function show($id){
        $this->getAllPermissions(Auth::user()->id);
        $spliter = Spliter::find($id);

        if ($spliter) {
            view()->share(['title' => $spliter->nombre]);
            return view('spliter.show')->with(compact('spliter'));
        }
        return redirect()->route('spliter.index')->with('danger', 'SPLITER NO ENCONTRADO, INTENTE NUEVAMENTE');
    }

    public function edit($id){
        $this->getAllPermissions(Auth::user()->id);
        $spliter = Spliter::find($id);

        if ($spliter) {
            view()->share(['title' => 'Editar Spliter: '.$spliter->nombre]);

            return view('spliter.edit')->with(compact('spliter'));
        }
        return redirect()->route('spliter.index')->with('danger', 'SPLITER NO ENCONTRADO, INTENTE NUEVAMENTE');
    }

    public function update(Request $request, $id){
        $spliter = Spliter::find($id);

        if ($spliter) {
            $spliter->nombre           = $request->nombre;
            $spliter->ubicacion        = $request->ubicacion;
            $spliter->coordenadas      = $request->coordenadas;
            $spliter->num_salida       = $request->num_salida;
            $spliter->num_cajas_naps   = $request->num_cajas_naps;
            $spliter->cajas_disponible = $request->cajas_disponible;
            $spliter->status           = $request->status;
            $spliter->descripcion      = $request->descripcion;
            $spliter->updated_by       = Auth::user()->id;
            $spliter->save();

            $mensaje='SE HA MODIFICADO SATISFACTORIAMENTE EL SPLITER';
            return redirect()->route('spliter.index')->with('success', $mensaje);
        }
        return redirect()->route('spliter.index')->with('danger', 'SPLITER NO ENCONTRADO, INTENTE NUEVAMENTE');
    }

    public function destroy($id){
        $spliter = Spliter::find($id);

        if($spliter){
            // Solo permitir eliminación si todas las cajas están disponibles
            if ($spliter->cajas_disponible == $spliter->num_cajas_naps) {
                $spliter->delete();
                $mensaje = 'SE HA ELIMINADO EL SPLITER CORRECTAMENTE';
                return redirect()->route('spliter.index')->with('success', $mensaje);
            } else {
                return redirect()->route('spliter.index')->with('danger', 'NO SE PUEDE ELIMINAR EL SPLITER. LA CANTIDAD DE CAJAS DISPONIBLES DEBE SER IGUAL AL TOTAL DE CAJAS NAPS.');
            }
        }
        return redirect()->route('spliter.index')->with('danger', 'SPLITER NO ENCONTRADO, INTENTE NUEVAMENTE');
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
}
