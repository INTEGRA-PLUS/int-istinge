<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Instance;
use App\WhatsAppConversation;
use App\WhatsAppMessage;
use App\Services\MetaWhatsAppService;

class ChatController extends Controller
{
    private $metaService;

    public function __construct(MetaWhatsAppService $metaService)
    {
        $this->middleware('auth');
        $this->metaService = $metaService;
    }

    /**
     * Vista principal del chat
     */
    public function index()
    {
        $user = auth()->user();
        
        // Obtener instancias activas de Meta para esta empresa
        $instances = Instance::where('company_id', $user->empresa)
            ->whereIn('type', [0, 1])
            ->where('meta', 0)
            ->where('activo', true)
            ->get();

        return view('chat.index', compact('instances'))
            ->with('title', 'Chat WhatsApp Meta')
            ->with('icon', 'fab fa-whatsapp')
            ->with('seccion', 'CRM')
            ->with('subseccion', 'chat_whatsapp_meta');
    }

    /**
     * Listar conversaciones
     */
    public function conversations(Request $request)
    {
        $user = auth()->user();
        $instanceId = $request->instance_id;

        if (!$instanceId) {
            return response()->json(['error' => 'instance_id es requerido'], 400);
        }

        // Verificar que la instancia pertenece a la empresa del usuario
        $instance = Instance::where('id', $instanceId)
            ->where('company_id', $user->empresa)
            ->firstOrFail();

        $conversations = WhatsAppConversation::forInstance($instanceId)
            ->with('assignedAgent:id,nombres') // changed name to nombres as user table usually has nombres
            ->when($request->search, function ($query, $search) {
                $query->search($search);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderByDesc('last_message_at')
            ->paginate(50);

        return response()->json($conversations);
    }

    /**
     * Obtener actualizaciones (para polling)
     */
    public function updates(Request $request)
    {
        $user = auth()->user();
        $instanceId = $request->instance_id;
        $since = $request->since;

        if (!$instanceId) {
            return response()->json(['error' => 'instance_id es requerido'], 400);
        }

        // Verificar permisos
        $instance = Instance::where('id', $instanceId)
            ->where('company_id', $user->empresa)
            ->firstOrFail();

        // Conversaciones actualizadas
        $updatedConversations = WhatsAppConversation::forInstance($instanceId)
            ->with('assignedAgent:id,nombres')
            ->when($since, function ($query, $since) {
                $query->where('updated_at', '>', $since);
            })
            ->orderByDesc('last_message_at')
            ->get();

        // Nuevos mensajes si hay conversación seleccionada
        $newMessages = [];
        if ($request->conversation_id && $since) {
            $newMessages = WhatsAppMessage::where('conversation_id', $request->conversation_id)
                ->with('sender:id,nombres')
                ->where('created_at', '>', $since)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        // Estados actualizados
        $updatedStatuses = [];
        if ($request->conversation_id && $since) {
            $updatedStatuses = WhatsAppMessage::where('conversation_id', $request->conversation_id)
                ->where('updated_at', '>', $since)
                ->whereIn('status', ['delivered', 'read', 'failed'])
                ->select('id', 'wamid', 'status', 'delivered_at', 'read_at')
                ->get();
        }

        return response()->json([
            'conversations'     => $updatedConversations,
            'new_messages'      => $newMessages,
            'updated_statuses'  => $updatedStatuses,
            'timestamp'         => now()->toIso8601String()
        ]);
    }

    /**
     * Obtener mensajes de una conversación
     */
    public function messages($conversationId)
    {
        $user = auth()->user();

        $conversation = WhatsAppConversation::with('instance')
            ->findOrFail($conversationId);

        // Verificar permisos
        if ($conversation->instance->company_id !== $user->empresa) {
            abort(403, 'No autorizado');
        }

        $messages = $conversation->messages()
            ->with('sender:id,nombres')
            ->orderBy('created_at', 'asc')
            ->get();

        // Marcar como leídos
        $conversation->markAsRead();

        return response()->json([
            'conversation' => $conversation,
            'messages'     => $messages,
            'timestamp'    => now()->toIso8601String()
        ]);
    }

    /**
     * Enviar mensaje de texto
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:4096'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();

        $conversation = WhatsAppConversation::with('instance')
            ->findOrFail($conversationId);

        // Verificar permisos
        if ($conversation->instance->company_id !== $user->empresa) {
            abort(403, 'No autorizado');
        }

        $instance = $conversation->instance;

        if (!$instance->isMetaConfigured()) {
            return response()->json([
                'success' => false,
                'error'   => 'Instancia no configurada correctamente'
            ], 400);
        }

        // Enviar mensaje vía Meta API
        $result = $this->metaService->sendMessage(
            $instance->phone_number_id,
            $conversation->phone_number,
            $request->message
        );

        if ($result['success']) {
            // Guardar mensaje en BD
            $message = WhatsAppMessage::create([
                'conversation_id' => $conversation->id,
                'wamid'           => $result['data']['messages'][0]['id'],
                'type'            => 'text',
                'content'         => $request->message,
                'direction'       => 'outbound',
                'status'          => 'sent',
                'sent_by'         => $user->id,
                'sent_at'         => now()
            ]);

            // Actualizar conversación
            $conversation->update([
                'last_message'    => $request->message,
                'last_message_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado',
                'data'    => $message->load('sender')
            ]);
        }

        return response()->json([
            'success' => false,
            'error'   => $result['error']['error']['message'] ?? 'Error al enviar mensaje'
        ], 500);
    }

    /**
     * Enviar imagen
     */
    public function sendImage(Request $request, $conversationId)
    {
        $validator = Validator::make($request->all(), [
            'image'   => 'required|image|max:5120', // 5MB
            'caption' => 'nullable|string|max:1024'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();

        $conversation = WhatsAppConversation::with('instance')
            ->findOrFail($conversationId);

        // Verificar permisos
        if ($conversation->instance->company_id !== $user->empresa) {
            abort(403, 'No autorizado');
        }

        $instance = $conversation->instance;

        // Guardar imagen
        $path = $request->file('image')->store('whatsapp/outbound', 'public');
        $imageUrl = url(Storage::url($path));

        // Enviar vía Meta API
        $result = $this->metaService->sendImage(
            $instance->phone_number_id,
            $conversation->phone_number,
            $imageUrl,
            $request->caption ?? ''
        );

        if ($result['success']) {
            // Guardar mensaje
            $message = WhatsAppMessage::create([
                'conversation_id' => $conversation->id,
                'wamid'           => $result['data']['messages'][0]['id'],
                'type'            => 'image',
                'content'         => $request->caption ?? '',
                'media_url'       => $imageUrl,
                'direction'       => 'outbound',
                'status'          => 'sent',
                'sent_by'         => $user->id,
                'sent_at'         => now()
            ]);

            $conversation->update([
                'last_message'    => $request->caption ?? 'Imagen',
                'last_message_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen enviada',
                'data'    => $message->load('sender')
            ]);
        }

        return response()->json([
            'success' => false,
            'error'   => 'Error al enviar imagen'
        ], 500);
    }

    /**
     * Asignar conversación
     */
    public function assign(Request $request, $conversationId)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();

        $conversation = WhatsAppConversation::with('instance')
            ->findOrFail($conversationId);

        if ($conversation->instance->company_id !== $user->empresa) {
            abort(403, 'No autorizado');
        }

        $conversation->update(['assigned_to' => $request->user_id]);

        return response()->json([
            'success' => true,
            'message' => 'Conversación asignada'
        ]);
    }

    /**
     * Cerrar conversación
     */
    public function close($conversationId)
    {
        $user = auth()->user();

        $conversation = WhatsAppConversation::with('instance')
            ->findOrFail($conversationId);

        if ($conversation->instance->company_id !== $user->empresa) {
            abort(403, 'No autorizado');
        }

        $conversation->update(['status' => 'closed']);

        return response()->json([
            'success' => true,
            'message' => 'Conversación cerrada'
        ]);
    }
}
