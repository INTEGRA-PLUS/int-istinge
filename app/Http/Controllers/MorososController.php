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
}
