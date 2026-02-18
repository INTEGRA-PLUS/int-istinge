<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MikrotikService;
use App\Mikrotik;
use Auth;

class MorososController extends Controller
{
    protected $mikrotikService;

    public function __construct(MikrotikService $mikrotikService)
    {
        $this->middleware('auth');
        $this->mikrotikService = $mikrotikService;
        view()->share(['seccion' => 'contratos', 'subseccion' => 'clientes', 'title' => 'Lista de Morosos', 'icon' => 'fas fa-users']);
    }

    public function index()
    {
        $this->getAllPermissions(Auth::user()->id);
        $mikrotiks = Mikrotik::where('empresa', Auth::user()->empresa)->get();
        return view('mikrotik.morosos', compact('mikrotiks'));
    }

    public function listar(Request $request)
    {
        if (!$request->has('mikrotik_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Seleccione una Mikrotik'
            ]);
        }

        $result = $this->mikrotikService->getMorosos($request->mikrotik_id);
        return response()->json($result);
    }

    public function sacarMoroso(Request $request)
    {
        $request->validate([
            'mikrotik_id' => 'required',
            'ip' => 'required',
            'contrato_id' => 'required'
        ]);

        $contrato = \App\Contrato::find($request->contrato_id);
        if (!$contrato) {
            return response()->json(['success' => false, 'message' => 'Contrato no encontrado']);
        }

        // 1. Eliminar de Mikrotik
        $removed = $this->mikrotikService->removerMoroso($request->mikrotik_id, $request->ip);

        if ($removed) {
            // 2. Activar contrato
            $contrato->state = 'enabled';
            $contrato->save();

            // 3. Registrar Log
            $movimiento = new \App\MovimientoLOG;
            $movimiento->contrato    = $contrato->id;
            $movimiento->modulo      = 5; // Modulo 5 indicado por el usuario
            $movimiento->descripcion = "Se sacó de morosos por la acción en lote (Discrepancia resuelta)";
            $movimiento->created_by  = Auth::user()->id;
            $movimiento->empresa     = Auth::user()->empresa;
            $movimiento->save();

            return response()->json([
                'success' => true,
                'message' => 'Contrato removido de morosos y activado correctamente'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo remover del Mikrotik. Verifique la conexión.'
        ]);
    }
}
