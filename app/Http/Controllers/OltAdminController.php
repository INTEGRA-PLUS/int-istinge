<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Empresa; // usa el mismo namespace que ya tienes

class OltAdminController extends Controller
{
    public function unconfigured(Request $request)
    {
        $this->getAllPermissions(Auth::user()->id);
    
        view()->share([
            'title'   => 'AdminOLT - Onus no autorizadas',
            'icon'    => '',
            'seccion' => ''
        ]);
    
        $onus = []; // para no reventar vista
    
        // 1) OLTs
        $olts = $this->getOltsAdminOLT();
        if (isset($olts['error']) && $olts['error'] === true) {
            $olts = [];
        }
    
        // 2) OLT default
        $olt_default = $request->olt_id ?? null;
        if ($olt_default === null && !empty($olts) && isset($olts[0]['id'])) {
            $olt_default = $olts[0]['id'];
        }
    
        // 3) VLANs -> facility
        $vlansInfo = [];
        $facility_vlans = null;
    
        if ($olt_default !== null) {
            $vlansInfo = $this->getVlansAdminOLT($olt_default);
            if (is_array($vlansInfo) && isset($vlansInfo['facility'])) {
                $facility_vlans = $vlansInfo['facility'];
            }
        }
    
        // 4) Unauthorized -> facility
        $unauthorizedInfo = $this->getUnauthorizedOnusAdminOLT();
        $facility_unauthorized = null;
    
        if (is_array($unauthorizedInfo) && isset($unauthorizedInfo['facility'])) {
            $facility_unauthorized = $unauthorizedInfo['facility'];
        }
    
        return view('olt.unconfiguredAdminOLT', compact(
            'onus',
            'olts',
            'olt_default',
            'vlansInfo',
            'facility_vlans',
            'unauthorizedInfo',
            'facility_unauthorized'
        ));
    }


    // -------------------------
    // Consultas a API (solo data)
    // -------------------------

    private function getOltsAdminOLT()
    {
        $empresa = Empresa::find(Auth::user()->empresa);

        if (!$empresa || !$empresa->adminOLT || !$empresa->smartOLT) {
            return ['error' => true, 'message' => 'Configuracion AdminOLT incompleta'];
        }

        return $this->adminoltGet($empresa->adminOLT, $empresa->smartOLT, '/api/olt-list/');
    }

    private function getUnauthorizedOnusAdminOLT()
    {
        $empresa = Empresa::find(Auth::user()->empresa);
    
        if (!$empresa || !$empresa->adminOLT || !$empresa->smartOLT) {
            return [
                'error' => true,
                'message' => 'Configuración AdminOLT incompleta (adminOLT o smartOLT)',
            ];
        }
    
        $curl = curl_init();
    
        curl_setopt_array($curl, [
            CURLOPT_URL => rtrim($empresa->adminOLT, '/') . '/api/onu/unauthorized/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: Token ' . $empresa->smartOLT,
                'Accept: application/json',
            ],
        ]);
    
        $response = curl_exec($curl);
    
        if ($response === false) {
            $err = curl_error($curl);
            curl_close($curl);
            return ['error' => true, 'message' => $err];
        }
    
        $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
    
        $decoded = json_decode($response, true);
    
        if ($http >= 400) {
            return [
                'error' => true,
                'message' => 'AdminOLT respondió HTTP ' . $http,
                'response' => $decoded ?: $response,
            ];
        }
    
        return $decoded;
    }

    private function getVlansAdminOLT($oltId)
    {
        $empresa = Empresa::find(Auth::user()->empresa);

        if (!$empresa || !$empresa->adminOLT || !$empresa->smartOLT) {
            return ['error' => true, 'message' => 'Configuracion AdminOLT incompleta'];
        }

        // ✅ OJO: el endpoint requiere slash final según docs: /api/vlans/{id}/
        return $this->adminoltGet($empresa->adminOLT, $empresa->smartOLT, '/api/vlans/' . $oltId . '/');
    }

    private function adminoltGet($baseUrl, $token, $path)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => rtrim($baseUrl, '/') . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: Token ' . $token,
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            return ['error' => true, 'message' => $error];
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            return [
                'error' => true,
                'message' => 'AdminOLT respondió HTTP ' . $httpCode,
                'response' => $decoded ?: $response,
            ];
        }

        return $decoded;
    }
}
