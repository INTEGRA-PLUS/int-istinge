<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Auth;
use App\Empresa; // usa el mismo namespace que ya tienes

class OltAdminController extends Controller
{
    protected $client;
    protected $baseUrl;
    protected $token;
    protected $subdomain;

    public function __construct()
    {
        $this->baseUrl = env('ADMINOLT_BASE_URL');
        $this->token = env('ADMINOLT_TOKEN');

        // Extract subdomain
        if (preg_match('/https:\/\/(.+?)\.adminolt\./', $this->baseUrl, $matches)) {
            $this->subdomain = $matches[1];
        }

        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Token ' . $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
            'verify' => false,
        ]);
    }

    /**
     * Get all OLTs from API
     */
    private function getOlts(): array
    {
        try {
            $response = $this->client->get('/api/olt-list/');
            $responseData = json_decode($response->getBody(), true);

            Log::info('AdminOLT OLTs Response:', ['data' => $responseData]);

            return $this->parseResponse($responseData);
        } catch (\Exception $e) {
            Log::error('Error fetching OLTs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get VLANs facility for a specific OLT
     */
    private function getVlansFacility($oltId): ?string
    {
        try {
            $response = $this->client->get("/api/vlans/{$oltId}");
            $responseData = json_decode($response->getBody(), true);

            Log::info('AdminOLT VLANs Response:', ['data' => $responseData]);

            // Return facility UUID if it exists
            return $responseData['facility'] ?? null;
        } catch (\Exception $e) {
            Log::error('Error fetching VLANs: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get ONUs facility
     */
    private function getOnusFacility(): ?string
    {
        try {
            $response = $this->client->get('/api/onu/authorized/');
            $responseData = json_decode($response->getBody(), true);

            Log::info('AdminOLT ONUs Response:', ['data' => $responseData]);

            // Return facility UUID if it exists
            return $responseData['facility'] ?? null;
        } catch (\Exception $e) {
            Log::error('Error fetching ONUs: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse API response and extract data
     */
    private function parseResponse($responseData): array
    {
        if (!is_array($responseData)) {
            return [];
        }

        return $responseData['results']
            ?? $responseData['data']
            ?? $responseData['response']
            ?? $responseData['onus']
            ?? $responseData['olts']
            ?? (isset($responseData[0]) ? $responseData : []);
    }

    /**
     * Display unconfigured OLTs and ONUs
     */
    public function unconfigured(Request $request)
    {
        if (Auth::user()) {
            $this->getAllPermissions(Auth::user()->id);
        } else {
            return redirect()->back()->with('error', 'No hay un usuario autenticado.');
        }

        view()->share(['title' => 'Olt - Onu Unconfigured', 'icon' => '', 'seccion' => '']);


        try {
            // 1. Get all OLTs
            $olts = $this->getOlts();

            if (empty($olts)) {
                return $this->renderView([], [], null, null, null);
            }

            // 2. Determine default OLT
            $oltDefault = $request->olt ?? $olts[0]['id'] ?? null;

            // 3. Get facilities (WebSocket URLs)
            $onusFacility = $this->getOnusFacility();
            $vlansFacility = $oltDefault ? $this->getVlansFacility($oltDefault) : null;

            return $this->renderView($olts, [], $oltDefault, $onusFacility, $vlansFacility);
        } catch (\Exception $e) {
            Log::error('Error in unconfigured: ' . $e->getMessage());
            return $this->renderView([], [], null, null, null, $e->getMessage());
        }
    }

    /**
     * Render the view with proper data structure
     */
    private function renderView(
        array $olts,
        array $onus,
        $oltDefault = null,
        $onusFacility = null,
        $vlansFacility = null,
        $error = null
    ) {
        $baseUrl = env('ADMINOLT_BASE_URL'); // ej: https://novalinksp.adminolt.co/api
        $wsHost  = parse_url($baseUrl, PHP_URL_HOST);
        return view('olt.unconfiguredAdminOLT', [
            'olts' => $olts,
            'onus' => $onus,
            'ws_host' => $wsHost,
            'adminolt_token' => env('ADMINOLT_TOKEN'),
            'olt_default'   => $oltDefault,
            'subdomain'     => $this->subdomain,
            'onus_facility' => $onusFacility,
            'vlans_facility' => $vlansFacility,
            'error' => $error,
            'title' => 'ONUs No Configuradas - AdminOLT',
            'icon' => 'fas fa-network-wired',
            'seccion' => 'AdminOLT'
        ]);
    }

    public function formAuthorizeOnu(Request $request)
    {

        $this->getAllPermissions(Auth::user()->id);
        view()->share(['title' => 'Olt - Formulario Authorizacion Onu', 'icon' => '', 'seccion' => '']);

        //  Obtener tipos de ONU
        $onu_types = $this->onuTypes();
        // Obtener OLTs
        $olts = $this->getOlts();
        $oltDefault = $request->olt ?? $olts[0]['id'] ?? null;
        // Parametro Vlans facility
        $vlans_facility = $oltDefault ? $this->getVlansFacility($oltDefault) : null;
        $vlan = [];
        // $zones = $this->getZones();
        // $default_zone = 0;

        // if (isset($zones['response'])) {
        //     $zones = $zones['response'];
        //     $default_zone = $zones[0]['id'];
        // } else {
        //     $zones = [];
        // }

        // if ($default_zone != 0) {
        //     $odbList = $this->ODBlist($default_zone);
        //     if (isset($odbList['response'])) {
        //         $odbList = $odbList['response'];
        //     } else {
        //         $odbList = [];
        //     }
        // } else {
        //     $odbList = [];
        // }

        // $speedProfiles = $this->getSpeedProfiles();

        // if (isset($speedProfiles['response'])) {
        //     $speedProfiles = $speedProfiles['response'];
        // } else {
        //     $speedProfiles = [];
        // }  
        $baseUrl = env('ADMINOLT_BASE_URL');
        $ws_host  = parse_url($baseUrl, PHP_URL_HOST);


        return view('olt.form-authorized-onuAdmin', compact(
            'request',
            'onu_types',
            'olts',
            'vlans_facility',
            'ws_host',
            // 'zones',
            'oltDefault',
            // 'default_zone',
            // 'odbList',
            // 'speedProfiles'
        ));
    }

    public function onuTypes()
    {
        try {
            $response = $this->client->get('/api/onu-types/');
            $data = json_decode($response->getBody(), true);

            // Puede venir como lista directa o paginada, por eso usamos parseResponse()
            return $this->parseResponse($data);
        } catch (\Exception $e) {
            // \Log::error('Error fetching ONU types: ' . $e->getMessage());
            return [];
        }
    }
}
