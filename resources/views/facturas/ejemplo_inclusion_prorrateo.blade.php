{{-- 
    EJEMPLO DE INCLUSIÓN EN VISTA DE FACTURA
    
    Para mostrar la sección de prorrateo en la vista de detalle de una factura,
    incluye este componente en la ubicación deseada de tu vista.
    
    Ejemplo en: resources/views/empresa/facturas/ver.blade.php
--}}

@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            {{-- Información general de la factura --}}
            <div class="card">
                <div class="card-header">
                    <h3>Factura #{{ $factura->codigo }}</h3>
                </div>
                <div class="card-body">
                    {{-- Datos del cliente, empresa, fecha, etc. --}}
                    <!-- ... contenido existente ... -->
                </div>
            </div>

            {{-- AQUÍ SE INCLUYE LA SECCIÓN DE PRORRATEO --}}
            @include('facturas.seccion_prorrateo', ['factura' => $factura])

            {{-- Items de la factura --}}
            <div class="card mt-3">
                <div class="card-header">
                    <h4>Servicios Facturados</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($factura->itemsFactura as $item)
                            <tr>
                                <td>{{ $item->descripcion }}</td>
                                <td>{{ $item->cant }}</td>
                                <td>${{ number_format($item->precio, 2, ',', '.') }}</td>
                                <td>${{ number_format($item->precio * $item->cant, 2, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Totales --}}
            <div class="card mt-3">
                <div class="card-body">
                    <!-- ... totales, impuestos, etc. ... -->
                </div>
            </div>

        </div>
    </div>
</div>
@endsection


{{-- 
    ALTERNATIVA: Usar en PDF de Factura
    
    Si quieres incluir la explicación de prorrateo en el PDF,
    puedes agregar una versión simplificada:
--}}

@if($factura->prorrateo_aplicado == 1 || $factura->prorrateo_aplicado == 2)
<div style="margin-top: 20px; padding: 15px; border: 1px solid #007bff; background-color: #f8f9fa;">
    <h4 style="color: #007bff; margin-top: 0;">Información de Prorrateo</h4>
    
    @php
        $diasCobrados = $factura->diasCobradosProrrateo();
        $tipoProrrateo = $factura->prorrateo_aplicado == 1 ? 'Primera Factura del Contrato' : 'Período Personalizado';
    @endphp
    
    <p><strong>Tipo:</strong> {{ $tipoProrrateo }}</p>
    <p><strong>Días cobrados:</strong> {{ $diasCobrados }} de 30 días</p>
    <p><strong>Fórmula aplicada:</strong> Precio Prorrateado = (Precio Original × {{ $diasCobrados }}) ÷ 30</p>
    
    <p style="margin-bottom: 0;">
        Esta factura ha sido calculada proporcionalmente para cobrar únicamente los días efectivos de servicio.
        Los precios mostrados en los servicios ya reflejan este ajuste proporcional.
    </p>
</div>
@endif
