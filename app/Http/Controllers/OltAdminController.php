<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class OltAdminController extends Controller
{
    #Esta vista es un index, para mostrar las onus no configuradas
    public function unconfigured()
    {
        if (
            Auth::user()->empresa()->nombre !== 'NOVA LINK TELECOMUNICACIONES S.A.S' ||
            !isset($_SESSION['permisos']['858'])
        ) {
            abort(403);
        }

        return view('olt.unconfiguredAdminOLT');
    }

    public static function getOltsAdminOLT()
    {
        $empresa = Empresa::find(Auth::user()->empresa);

        if (!$empresa || !$empresa->adminOLT || !$empresa->adminOLT_token) {
            return [
                'error' => true,
                'message' => 'Configuración AdminOLT incompleta'
            ];
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => rtrim($empresa->adminOLT, '/') . '/api/olt-list/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: Token ' . $empresa->adminOLT_token,
                'Accept: application/json'
            ],
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            return [
                'error' => true,
                'message' => $error
            ];
        }

        curl_close($curl);

        return json_decode($response, true);
    }

}
