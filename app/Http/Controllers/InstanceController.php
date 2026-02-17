<?php

namespace App\Http\Controllers;

use App\Instance;
use App\Services\WapiService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;

class InstanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ia = $request->get("ia");
        $instance = Instance::where('company_id', auth()->user()->empresa)->where("type", $ia ? 2 : 1)->first();
        return response()->json($instance);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, WapiService $wapiService)
    {
        $addr = url('');

        // Detectar si es creación de instancia Meta (por presencia de phone_number_id)
        if ($request->has('phone_number_id') || $request->type == 0) {
            $validated = $request->validate([
                'phone_number_id' => 'required',
                'waba_id' => 'required',
            ]);

            try {
                $instance = Instance::create([
                    'company_id' => auth()->user()->empresa,
                    'phone_number_id' => $validated['phone_number_id'],
                    'waba_id' => $validated['waba_id'],
                    'status' => 'PAIRED', // Meta se considera conectada al crearla con las credenciales
                    'type' => 1, // Meta Direct
                    'meta' => 0,
                    'uuid' => $validated['phone_number_id'], 
                    'api_key' => 'meta', 
                    'addr' => $addr,
                    'uuid_whatsapp' => $validated['phone_number_id']
                ]);

                return back()->with([
                    'instance' => $instance,
                    'message' => 'Instancia Meta registrada correctamente.'
                ]);
            } catch (Exception $err) {
                return back()->withErrors([
                    'error' => 'Error al registrar instancia Meta: ' . $err->getMessage()
                ])->withInput($request->input());
            }
        }

        $validated = $request->validate([
            'instance_id' => 'required|regex:/[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}/'
        ]);

        $response = $wapiService->getInstance($validated['instance_id']);
        if($response["statusCode"] ?? 0 === 400) {
            return back()->withErrors([
                'instance_id' => 'Esta instancia no existe, valida el identificador con tu proveedor.'
            ])->withInput($request->input());
        }
        $responseInstance = json_decode($response)->data;
        try {

            $instance = Instance::create([
                'uuid' => $responseInstance->channelId,
                'company_id' => auth()->user()->empresa,
                'addr' => $addr,
                'api_key' => $responseInstance->channelId,
                'uuid_whatsapp' => $responseInstance->uuid,
                'status' => $responseInstance->status,
                'type' => $request->type
            ]);
            return back()->with([
                'instance' => $instance,
                'message' => 'Instancia registrada correctamente.'
            ]);
        } catch (Exception $err) {
            return back()->withErrors([
                'instance_id' => 'Esta instancia ya ha sido registrada.'
            ])->withInput($request->input());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, WapiService $wapiService)
    {
        $instance = Instance::where('uuid', $id)->first();

        if(!$instance) {
            return response()->json([
                'status' => 'error',
                'data' => [
                    'error' => 'Instancia no encontrada.'
                ]
            ]);
        }

        try {
            $response = $wapiService->getInstance($instance->uuid);
        } catch (ClientException $e) {
            if($e->getResponse()->getStatusCode() === 404) {
                return back()->withErrors([
                    'instance_id' => 'Esta instancia no existe, valida el identificador con tu proveedor.'
                ])->withInput($request->input());
            }
        }
        $responseInstance = (object) json_decode($response)->data;

        if($responseInstance->status === 'PAIRED') {
            $instance->status = 'PAIRED';
            $instance->save();
            return response()->json([
                'status' => 'success',
                'data' => [
                    'message' => 'Instancia emparejada correctamente.'
                ]
            ]);
        }
        $instance->status = 'UNPAIRED';
        $instance->save();
        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'Instancia actualizada correctamente.'
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function pair($id, WapiService $wapiService)
    {
        $instance = Instance::where("id", $id)->first();
        if(!$instance) {
            return response()->json([
                'status' => 'error',
                'data' => [
                    'error' => 'Instancia no encontrada.'
                ]
            ]);
        }

        // Si es Meta Direct (type 1), no requiere pairing con Wapi
        if ($instance->type == 1) {
            $instance->status = 'PAIRED';
            $instance->save();
            return response()->json([
                'status' => 'success',
                'data' => [
                    'message' => 'Instancia Meta conectada correctamente.'
                ]
            ]);
        }

        $session = $wapiService->initSession($instance->uuid);
        $session = json_decode($session);
        if($session->status === 'error') {
            return response()->json([
                'status' => 'error',
                'data' => [
                    'error' => 'Error al iniciar sesión.'
                ]
            ]);
        }
        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'Sesión iniciada correctamente.'
            ]
        ]);
    }
}