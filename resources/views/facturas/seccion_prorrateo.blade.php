@if($factura->prorrateo_aplicado == 1 || $factura->prorrateo_aplicado == 2)
<div class="card mb-3" style="border-left: 4px solid #007bff;">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="fa fa-calculator"></i> 
            <strong>Información de Prorrateo Aplicado</strong>
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <i class="fa fa-info-circle"></i>
            <strong>¿Qué es el prorrateo?</strong>
            <p class="mb-0 mt-2">
                El prorrateo es un ajuste proporcional que se realiza cuando el servicio no se utiliza durante un período completo de 30 días. 
                Esto garantiza que solo pague por los días efectivos de servicio.
            </p>
        </div>

        @php
            $empresa = App\Empresa::find($factura->empresa);
            $diasCobrados = $factura->diasCobradosProrrateo();
            $contrato = App\Contrato::find($factura->contrato_id);
            
            // Determinar el tipo de prorrateo
            $tipoProrrateo = '';
            $razonProrrateo = '';
            
            if($factura->prorrateo_aplicado == 1 && $contrato) {
                // Prorrateo por inicio de contrato
                $fechaCreacionContrato = \Carbon\Carbon::parse($contrato->created_at);
                $tipoProrrateo = 'Primer Factura del Contrato';
                $razonProrrateo = 'Esta factura corresponde a la primera facturación de su contrato. 
                                   El cálculo se realizó desde la fecha de inicio de su servicio hasta la fecha de corte correspondiente.';
            } elseif($factura->prorrateo_aplicado == 2) {
                // Prorrateo desde ingreso (factura manual con fecha personalizada)
                $tipoProrrateo = 'Ajuste por Período Personalizado';
                $razonProrrateo = 'Esta factura se generó con un período de cobro personalizado, 
                                   calculando únicamente los días de servicio efectivos.';
            }
        @endphp

        <div class="row">
            <div class="col-md-12">
                <h6 class="text-primary"><i class="fa fa-receipt"></i> Tipo de Prorrateo</h6>
                <p><strong>{{ $tipoProrrateo }}</strong></p>
                <p class="text-muted">{{ $razonProrrateo }}</p>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary"><i class="fa fa-calendar-alt"></i> Detalle del Cálculo</h6>
                <table class="table table-sm table-bordered">
                    <tbody>
                        @if($contrato && $factura->prorrateo_aplicado == 1)
                        <tr>
                            <td><strong>Fecha de inicio del servicio:</strong></td>
                            <td>{{ \Carbon\Carbon::parse($contrato->created_at)->format('d/m/Y') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td><strong>Fecha de facturación:</strong></td>
                            <td>{{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Días cobrados:</strong></td>
                            <td><span class="badge badge-success">{{ $diasCobrados }} días</span></td>
                        </tr>
                        <tr>
                            <td><strong>Base de cálculo:</strong></td>
                            <td>30 días (mes completo)</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="col-md-6">
                <h6 class="text-primary"><i class="fa fa-calculator"></i> Fórmula de Cálculo</h6>
                <div class="alert alert-light border">
                    <p class="mb-2"><strong>Cálculo aplicado a cada servicio:</strong></p>
                    <div class="bg-white p-3 rounded border">
                        <code style="font-size: 14px;">
                            Precio Prorrateado = (Precio Original × {{ $diasCobrados }}) ÷ 30
                        </code>
                    </div>
                    
                    <p class="mt-3 mb-2"><strong>Ejemplo con un servicio:</strong></p>
                    @if($factura->itemsFactura->count() > 0)
                        @php
                            $primerItem = $factura->itemsFactura->first();
                            // Calcular el precio original antes del prorrateo
                            $precioOriginal = round(($primerItem->precio * 30) / $diasCobrados, 2);
                        @endphp
                        <div class="bg-white p-3 rounded border">
                            <p class="mb-1">Servicio: <strong>{{ $primerItem->descripcion }}</strong></p>
                            <p class="mb-1">Precio mensual: <strong>${{ number_format($precioOriginal, 2, ',', '.') }}</strong></p>
                            <p class="mb-1">Cálculo: (${{ number_format($precioOriginal, 2, ',', '.') }} × {{ $diasCobrados }}) ÷ 30</p>
                            <p class="mb-0">Precio final: <strong class="text-success">${{ number_format($primerItem->precio, 2, ',', '.') }}</strong></p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-12">
                <h6 class="text-primary"><i class="fa fa-list"></i> Servicios Incluidos en esta Factura</h6>
                <table class="table table-sm table-striped table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Descripción</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">Precio Mensual</th>
                            <th class="text-right">Precio Prorrateado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($factura->itemsFactura as $item)
                            @php
                                // Calcular el precio original antes del prorrateo
                                $precioMensual = round(($item->precio * 30) / $diasCobrados, 2);
                            @endphp
                            <tr>
                                <td>{{ $item->descripcion }}</td>
                                <td class="text-center">{{ $item->cant }}</td>
                                <td class="text-right">${{ number_format($precioMensual, 2, ',', '.') }}</td>
                                <td class="text-right"><strong class="text-success">${{ number_format($item->precio, 2, ',', '.') }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="alert alert-success mt-3">
            <i class="fa fa-check-circle"></i>
            <strong>Resumen:</strong>
            <p class="mb-0 mt-2">
                Esta factura ha sido calculada proporcionalmente para cobrar únicamente <strong>{{ $diasCobrados }} días de servicio</strong> 
                de los 30 días del período completo. Esto se ha aplicado automáticamente según las políticas de facturación 
                {{ $factura->prorrateo_aplicado == 1 ? 'para nuevos contratos' : 'para períodos personalizados' }}.
            </p>
        </div>

        @if($empresa->precision)
        <div class="text-muted small mt-2">
            <i class="fa fa-info-circle"></i> Los valores han sido redondeados a {{ $empresa->precision }} decimales según la configuración de la empresa.
        </div>
        @endif
    </div>
</div>
@endif
