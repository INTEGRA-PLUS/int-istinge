<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MikrotikService;

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
        $this->getAllPermissions(\Auth::user()->id);
        return view('mikrotik.morosos');
    }

    public function apiIndex()
    {
        // Check permissions if needed, or rely on middleware
        // For now, allow authenticated users
        
        $result = $this->mikrotikService->getMorosos();
        return response()->json($result);
    }
}
