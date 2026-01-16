<?php

namespace App\Http\Controllers;

use App\Instance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class InstancesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        view()->share(['seccion' => 'configuracion', 'title' => 'Instancias WhatsApp Meta', 'icon' => 'fab fa-whatsapp']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->getAllPermissions(Auth::user()->id);
        $instances = Instance::where('company_id', Auth::user()->empresa)
            ->where('meta', 0)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('configuracion.instances.index')->with(compact('instances'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validar campos requeridos
        $request->validate([
            'phone_number_id' => 'required|string|max:255',
            'waba_id' => 'required|string|max:255',
        ]);

        // Validar que ACCESS_TOKEN_META y WAPI_TOKEN estén en .env
        $accessTokenMeta = env('ACCESS_TOKEN_META');
        $wapiToken = env('WAPI_TOKEN');

        if (empty($accessTokenMeta)) {
            return response()->json([
                'success' => false,
                'message' => 'ACCESS_TOKEN_META no está configurado en el archivo .env'
            ], 400);
        }

        if (empty($wapiToken)) {
            return response()->json([
                'success' => false,
                'message' => 'WAPI_TOKEN no está configurado en el archivo .env'
            ], 400);
        }

        try {
            // Hacer petición a la API de VibioCRM
            $client = new Client();
            $url = 'https://api.vibiocrm.com/api/v1/channels/waba';

            $body = [
                'name' => 'Meta',
                'credentials' => [
                    'accessToken' => $accessTokenMeta,
                    'phoneNumberId' => $request->phone_number_id,
                    'wabaId' => $request->waba_id,
                    'verifyToken' => 'mi_token_verificacion'
                ]
            ];

            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $wapiToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'json' => $body
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            // Verificar si la respuesta es exitosa
            if (isset($responseData['status']) && $responseData['status'] === 'success' && isset($responseData['data']['channel'])) {
                $channel = $responseData['data']['channel'];

                // Crear la instancia en la base de datos
                $instance = Instance::create([
                    'uuid' => $channel['uuid'],
                    'api_key' => $channel['uuid'],
                    'uuid_whatsapp' => $channel['uuid'],
                    'company_id' => Auth::user()->empresa,
                    'status' => 'PAIRED',
                    'type' => 1,
                    'meta' => 0,
                    'activo' => 1,
                    'phone_number_id' => $request->phone_number_id,
                    'waba_id' => $request->waba_id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Instancia creada correctamente',
                    'instance' => $instance
                ]);
            } else {
                $errorMessage = $responseData['error'] ?? 'Error desconocido al crear la instancia';
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }
        } catch (RequestException $e) {
            Log::error('Error al crear instancia WhatsApp Meta: ' . $e->getMessage());
            
            $errorMessage = 'Error al comunicarse con la API de VibioCRM';
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $responseBody = json_decode($response->getBody()->getContents(), true);
                if (isset($responseBody['error'])) {
                    $errorMessage = $responseBody['error'];
                } elseif (isset($responseBody['message'])) {
                    $errorMessage = $responseBody['message'];
                }
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error inesperado al crear instancia WhatsApp Meta: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $instance = Instance::where('id', $id)
                ->where('company_id', Auth::user()->empresa)
                ->where('meta', 0)
                ->first();

            if (!$instance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Instancia no encontrada'
                ], 404);
            }

            $instance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Instancia eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar instancia: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la instancia: ' . $e->getMessage()
            ], 500);
        }
    }
}

