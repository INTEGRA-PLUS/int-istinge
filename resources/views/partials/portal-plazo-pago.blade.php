{{-- Cuenta regresiva del plazo de pago fijado desde el Integra Portal.
     No se puede cerrar: desaparece sola cuando el mes queda cubierto
     (comprobante enviado aquí mismo o pago confirmado por Integra). --}}
@php
    $plazoLimite = \App\PortalPlazoPago::vigente();
@endphp

@if ($plazoLimite)
    <div id="portal-plazo" data-limite="{{ $plazoLimite }}" class="mb-3"
         style="border:1px solid rgba(225,29,72,.35); border-left:4px solid #e11d48; background:rgba(225,29,72,.05); border-radius:10px; padding:14px 16px;">
        <div style="display:flex; flex-wrap:wrap; gap:14px; align-items:center;">
            <div style="flex:1; min-width:240px; display:flex; gap:12px; align-items:flex-start;">
                <i class="fas fa-stopwatch" style="color:#e11d48; font-size:20px; margin-top:2px;"></i>
                <div>
                    <div id="portal-plazo-titulo" style="font-weight:700; margin-bottom:2px;">Tu suscripción de Integra está pendiente de pago</div>
                    <div id="portal-plazo-texto" style="font-size:.9rem; line-height:1.5; color:#475569;">
                        Realiza el pago y envía el comprobante antes del <b id="portal-plazo-cuando"></b> para evitar la suspensión del servicio.
                    </div>
                </div>
            </div>
            <div id="portal-plazo-reloj" style="display:flex; gap:6px;">
                @foreach (['d' => 'días', 'h' => 'horas', 'm' => 'min', 's' => 'seg'] as $k => $etq)
                    <div data-bloque="{{ $k }}" style="min-width:56px; text-align:center; background:#fff; border:1px solid rgba(225,29,72,.2); border-radius:8px; padding:6px 8px;">
                        <div data-valor="{{ $k }}" style="font-size:22px; font-weight:800; color:#e11d48; line-height:1; font-variant-numeric:tabular-nums;">--</div>
                        <div style="font-size:10px; text-transform:uppercase; color:#94a3b8; margin-top:3px;">{{ $etq }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        <form action="{{ route('mi-suscripcion.store') }}" method="POST" enctype="multipart/form-data"
              style="margin-top:10px; display:flex; flex-wrap:wrap; gap:8px; align-items:center; padding:8px 10px; border:1px solid #e2e8f0; border-radius:8px; background:rgba(255,255,255,.6);">
            {{ csrf_field() }}
            <input type="hidden" name="periodo" value="{{ date('Y-m') }}">
            <input type="hidden" name="observaciones" value="Enviado desde el contador de plazo de pago">
            <input type="file" name="archivo" accept=".jpg,.jpeg,.png,.webp,.pdf" required style="font-size:12px; color:#64748b; max-width:260px;">
            <button type="submit" style="padding:6px 14px; border-radius:7px; border:0; background:#e11d48; color:#fff; font-size:12px; font-weight:700; cursor:pointer;">
                <i class="fas fa-paper-plane"></i> Enviar comprobante
            </button>
        </form>
    </div>

    <script>
        (function () {
            var caja = document.getElementById('portal-plazo');
            if (!caja) return;
            var limite = new Date(caja.dataset.limite).getTime();
            var dos = function (n) { return (n < 10 ? '0' : '') + n; };
            try {
                document.getElementById('portal-plazo-cuando').textContent = new Date(limite).toLocaleString('es-CO', {
                    weekday: 'long', day: 'numeric', month: 'long', hour: 'numeric', minute: '2-digit', timeZone: 'America/Bogota'
                });
            } catch (e) {
                document.getElementById('portal-plazo-cuando').textContent = new Date(limite).toLocaleString();
            }
            var poner = function (k, v) { caja.querySelector('[data-valor="' + k + '"]').textContent = v; };
            var tic = function () {
                var resta = Math.max(0, limite - Date.now());
                if (resta === 0) {
                    document.getElementById('portal-plazo-reloj').style.display = 'none';
                    document.getElementById('portal-plazo-titulo').textContent = 'El plazo de pago de tu suscripción venció';
                    document.getElementById('portal-plazo-texto').textContent = 'Tu servicio puede ser suspendido en cualquier momento. Envía el comprobante para evitarlo.';
                    clearInterval(t);
                    return;
                }
                var s = Math.floor(resta / 1000), d = Math.floor(s / 86400);
                caja.querySelector('[data-bloque="d"]').style.display = d > 0 ? '' : 'none';
                poner('d', d);
                poner('h', dos(Math.floor((s % 86400) / 3600)));
                poner('m', dos(Math.floor((s % 3600) / 60)));
                poner('s', dos(s % 60));
            };
            var t = setInterval(tic, 1000);
            tic();
        })();
    </script>
@endif
