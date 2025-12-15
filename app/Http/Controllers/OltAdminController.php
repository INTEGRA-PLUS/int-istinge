<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class AdminOLTController extends Controller
{
    public function unconfigured()
    {
        // (Opcional) seguridad
        if (
            Auth::user()->empresa()->nombre !== 'NEXXT-WISP S.A.S' ||
            !isset($_SESSION['permisos']['858'])
        ) {
            abort(403);
        }

        return view('olt.unconfiguredAdminOLT');
    }
}
