@extends('layouts.app')

@section('style')
<style>
    .ai-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        /* background: linear-gradient(135deg, #667eea 0%, #667eea 100%); */
        border-radius: 20px;
        padding: 40px 20px;
        position: relative;
        overflow: hidden;
    }

    .ai-container::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: pulse 4s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 0.5;
        }

        50% {
            transform: scale(1.1);
            opacity: 0.8;
        }
    }

    .ai-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 40px;
        max-width: 700px;
        width: 100%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        position: relative;
        z-index: 1;
    }

    .ai-header {
        text-align: center;
        margin-bottom: 35px;
    }

    .ai-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #667eea 0%, #a1b4ff 100%);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    }

    .ai-icon i {
        font-size: 35px;
        color: white;
    }

    .ai-title {
        font-size: 28px;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 10px;
    }

    .ai-subtitle {
        font-size: 16px;
        color: #718096;
    }

    .form-group label {
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 10px;
        display: block;
    }

    .ai-input,
    .ai-textarea {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 15px;
        transition: all 0.3s ease;
        background: #f7fafc;
    }

    .ai-input:focus,
    .ai-textarea:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .ai-textarea {
        min-height: 120px;
        resize: vertical;
        font-family: inherit;
    }

    .ai-btn {
        width: 100%;
        padding: 16px 30px;
        background: linear-gradient(135deg, #667eea 0%, #667eea 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 25px;
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }

    .ai-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
    }

    .ai-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .ai-btn.loading {
        position: relative;
        color: transparent;
    }

    .ai-btn.loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
@endsection

@section('boton')
<a href="{{route('borrar-cache')}}" class="btn btn-outline-info btn-sm"><i class="fas fa-plus"></i> Borrar Caché</a>
@endsection

@section('content')
<div class="ai-container">
    <div class="ai-card">
        <div class="ai-header">
            <div class="ai-icon">
                <i class="fas fa-brain"></i>
            </div>
            <h1 class="ai-title">Asistente IA</h1>
            <p class="ai-subtitle">Registra tus preguntas y respuestas</p>
        </div>

        <form id="chatbox-form">
            @csrf

            <div class="form-group mb-4">
                <label for="pregunta">
                    <i class="fas fa-question-circle"></i> Pregunta
                </label>
                <textarea
                    class="ai-textarea"
                    id="pregunta"
                    name="pregunta"
                    placeholder="Escribe tu pregunta aquí..."
                    required></textarea>
            </div>

            <div class="form-group mb-4">
                <label for="respuesta">
                    <i class="fas fa-comment-dots"></i> Respuesta
                </label>
                <textarea
                    class="ai-textarea"
                    id="respuesta"
                    name="respuesta"
                    placeholder="Escribe la respuesta aquí..."
                    required></textarea>
            </div>

            <button type="submit" class="ai-btn" id="submit-btn">
                <i class="fas fa-paper-plane"></i> Registrar Información
            </button>
        </form>
    </div>
</div>
@endsection


@section('scripts')
<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('chatbox-form');
    const submitBtn = document.getElementById('submit-btn');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const pregunta = document.getElementById('pregunta').value.trim();
        const respuesta = document.getElementById('respuesta').value.trim();
        
        // Validación básica
        if (!pregunta || !respuesta) {
            swal({
                title: 'Campos requeridos',
                text: 'Por favor completa todos los campos',
                type: 'warning',
                confirmButtonColor: '#667eea',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        // Mostrar loading
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name="_token"]').value);
            formData.append('pregunta', pregunta);
            formData.append('respuesta', respuesta);
            
            const response = await fetch('{{ url()->current() }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('El servidor no devolvió una respuesta JSON válida');
            }
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `Error ${response.status}`);
            }
            
            if (data.success) {
                swal({
                    title: '¡Éxito!',
                    text: 'La información se ha registrado correctamente',
                    type: 'success',
                    confirmButtonColor: '#667eea',
                    confirmButtonText: 'Genial'
                }).then(() => {
                    // Limpiar formulario
                    form.reset();
                });
            } else {
                throw new Error(data.message || 'Error al guardar');
            }
            
        } catch (error) {
            console.error('Error:', error);
            swal({
                title: 'Error',
                text: error.message || 'Ha ocurrido un error al registrar la información',
                type: 'error',
                confirmButtonColor: '#667eea',
                confirmButtonText: 'Aceptar'
            });
        } finally {
            // Quitar loading
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    });
});
</script>
<script defer>
    const getQr = document.querySelector('#btn-get-qr');
    const qrContainer = document.querySelector('#qr-container');
    const qrCode = document.querySelector('#qrcode');

    getQr?.addEventListener('click', async () => {
        if (getQr.classList.contains('button--loading')) {
            return;
        }
        setLoading(getQr);
        const response = await fetch("/software/empresa/instances?ia=true");
        const instance = await response.json();

        if (instance.id) {
            const session = await fetch(`/software/empresa/instances/${instance.id}/pair`);
            const sessionData = await session.json();
            if (sessionData.status == "error") {
                removeLoading(getQr);
                swal({
                    title: 'Ha ocurrido un error iniciando sesión, comuniquse con el administrador',
                    type: 'error',
                    showCancelButton: false,
                    showConfirmButton: true,
                    cancelButtonColor: '#00ce68',
                    cancelButtonText: 'Aceptar',
                });
            }
        }
    });

    const setLoading = (btn) => {
        if (!btn.classList.contains('button--loading')) {
            btn.classList.add('button--loading');
        }
    }

    const removeLoading = (btn) => {
        if (btn.classList.contains('button--loading')) {
            btn.classList.remove('button--loading');
        }
    }
</script>

<script defer>
    const apiKey = document.querySelector('#instance-key').value || "";
    const socket = io("{{ env('WAPI_URL') }}", {
        extraHeaders: {
            "Authorization": `Bearer ${apiKey}`
        }
    });

    socket.on("session:update", (arg) => {
        const {
            wbot
        } = arg;
        if (wbot.status == "QRCODE") {
            document.getElementById("qrcode").innerHTML = "";

            new QRCode(document.getElementById("qrcode"), {
                text: wbot.qrCode,
                colorDark: "#052e16",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
            return;
        }

        if (wbot.status == "PAIRED") {
            fetch(`/software/empresa/instances/${wbot.channelId}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status == "error") {
                        swal({
                            title: 'Ha ocurrido un error iniciando sesión, comuniquse con el administrador',
                            type: 'error',
                            showCancelButton: false,
                            showConfirmButton: true,
                            cancelButtonColor: '#00ce68',
                            cancelButtonText: 'Aceptar',
                        });
                    }

                    swal({
                        title: 'Sesión iniciada correctamente',
                        type: 'success',
                        showCancelButton: false,
                        showConfirmButton: true,
                        cancelButtonColor: '#00ce68',
                        cancelButtonText: 'Aceptar',
                    });

                    window.location.reload();
                }).catch(error => {
                    swal({
                        title: 'Ha ocurrido un error iniciando sesión, comuniquse con el administrador',
                        type: 'error',
                        showCancelButton: false,
                        showConfirmButton: true,
                        cancelButtonColor: '#00ce68',
                        cancelButtonText: 'Aceptar',
                    });
                });
        }
    })
</script>
@endsection