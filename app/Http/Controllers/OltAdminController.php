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
     * Get ONU types from API
     **/
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
    /**
     * Get  zones from API
     **/
    public function zones()
    {
        try {
            $response = $this->client->get('/api/zones/');
            $data = json_decode($response->getBody(), true);

            // Puede venir como lista directa o paginada, por eso usamos parseResponse()
            return $this->parseResponse($data);
        } catch (\Exception $e) {
            // \Log::error('Error fetching ONU types: ' . $e->getMessage());
            return [];
        }
    }
    /**
     * Get  speed profiles from API
     **/
    public function getPrUP()
    {
        try {
            $response = $this->client->get('/api/speedprofiles/upload');
            $data = json_decode($response->getBody(), true);

            // Puede venir como lista directa o paginada, por eso usamos parseResponse()
            return $this->parseResponse($data);
        } catch (\Exception $e) {
            // \Log::error('Error fetching ONU types: ' . $e->getMessage());
            return [];
        }
    }
    /**
     * Get  speed profiles from API
     **/
    public function getPrDown()
    {
        try {
            $response = $this->client->get('/api/speedprofiles/download');
            $data = json_decode($response->getBody(), true);

            // Puede venir como lista directa o paginada, por eso usamos parseResponse()
            return $this->parseResponse($data);
        } catch (\Exception $e) {
            // \Log::error('Error fetching ONU types: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get ONUs facility
     */
    private function getOnusFacility(): ?string
    {
        try {
            $response = $this->client->get('/api/onu/unauthorized/');
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
        // Obtener zonas
        $zones = $this->zones();
        // Obtener zonas
        $speedProfilesUp = $this->getPrUP();
        // Obtener zonas
        $speedProfilesDown = $this->getPrDown();
        // Obtener OLTs
        $olts = $this->getOlts();
        $oltDefault = $request->olt ?? $olts[0]['id'] ?? null;
        // Parametro Vlans facility
        $vlans_facility = $oltDefault ? $this->getVlansFacility($oltDefault) : null;
        $vlan = [];
        $baseUrl = env('ADMINOLT_BASE_URL');
        $ws_host  = parse_url($baseUrl, PHP_URL_HOST);


        return view('olt.form-authorized-onuAdmin', compact(
            'request',
            'onu_types',
            'zones',
            'olts',
            'vlans_facility',
            'ws_host',
            'oltDefault',
            // 'default_zone',F
            // 'odbList',
            'speedProfilesUp',
            'speedProfilesDown'
        ));
    }


    public function authorizeOnu(Request $request)
    {
        try {
            // 1) Validación mínima (ajusta después)
            $request->validate([
                'olt_id' => 'required',
                'board' => 'required',
                'port' => 'required',
                'sn' => 'required',
                'onu_type' => 'required',
                'onu_mode' => 'required',
                'user_vlan_id' => 'required',
                'name' => 'required',
                'interface' => 'required',
            ]);


            // $onuTypeId = (int) $request->onu_type; 

            $payload = [
                'chasis' => (int) ($request->chasis ?? 0),
                'board' => (string) $request->board,
                'port' => (int) $request->port,
                'sn' => (string) $request->sn,
                'onu_type' => $request->onu_type,
                'onu_mode' => $request->onu_mode,
                'cvlan' => (string) $request->user_vlan_id,
                'svlan' => (string) $request->user_vlan_id,
                'zone' => $request->zone ?? 'None',
                'odb_splitter' => (string) ($request->odb_splitter ?? 'None'), // string
                'upload_speed' => (int) ($request->upload_speed ?? 0),   // int
                'download_speed' => (int) ($request->download_speed ?? 0), // int
                'name' => (string) $request->name,                    // string
                'comment' => (string) ($request->address_comment ?? ''), // string
                'address' => (string) ($request->address_comment ?? ''), // string (reutiliza el mismo campo)
                'phone' => '',                                         // string (no está en form)
                'longitude' => '',                                     // string (no está en form)
                'latitude' => '',                                      // string (no está en form)
                'interface' => (string) $request->interface,          // string ✅
                'olt_id' => (int) $request->olt_id,                   // int
            ];


            $response = $this->client->post('/api/onu/authorize/', [
                'json' => $payload,
            ]);

            $status = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            Log::info('Authorize Response:', ['status' => $status, 'data' => $responseData]);

            if ($status >= 200 && $status < 300) {
                return redirect('Olt/unconfigured-adminolt')->with('success', 'ONU autorizada con éxito');
            }

            return redirect('Olt/unconfigured-adminolt')->with('error', 'ONU no autorizada: ' . ($responseData['message'] ?? 'Error desconocido'));
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Si es error 4xx (validación/bad request)
            $errorBody = $e->getResponse()->getBody()->getContents();
            Log::error('Error autorizando ONU (4xx):', ['body' => $errorBody]);
            return redirect('Olt/unconfigured-adminolt')->with('error', 'Error de validación: ' . $errorBody);
        } catch (\Exception $e) {
            Log::error('Error autorizando ONU:', ['msg' => $e->getMessage()]);
            return redirect('Olt/unconfigured-adminolt')->with('error', 'Error autorizando ONU: ' . $e->getMessage());
        }
    }
}
