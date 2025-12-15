<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class OltAdminController extends Controller
{
    #Esta vista es un index, para mostrar las onus no configuradas
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
