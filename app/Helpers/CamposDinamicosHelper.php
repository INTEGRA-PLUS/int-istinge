<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CamposDinamicosHelper
{
    /**
     * Procesa los campos dinámicos en un string reemplazando los placeholders con valores reales
     *
     * @param string $paramValue El string con placeholders a procesar
     * @param \App\Contacto $contacto El objeto contacto
     * @param \App\Model\Ingresos\Factura|null $factura El objeto factura (opcional)
     * @param \App\Empresa|null $empresa El objeto empresa (opcional)
     * @return string El string procesado con los valores reemplazados
     */
    public static function procesarCamposDinamicos($paramValue, $contacto, $factura = null, $empresa = null)
    {
        if (!is_string($paramValue)) {
            $paramValue = '';
        }

        // ============================================================
        // REEMPLAZOS DE CONTACTO
        // ============================================================
        $paramValue = str_replace('[contacto.nombre]', $contacto->nombre ?? '', $paramValue);
        $paramValue = str_replace('[contacto.apellido1]', $contacto->apellido1 ?? '', $paramValue);
        $paramValue = str_replace('[contacto.apellido2]', $contacto->apellido2 ?? '', $paramValue);
        $paramValue = str_replace('[contacto.direccion]', $contacto->direccion ?? '', $paramValue);
        
        // Obtener nombre del barrio
        $barrioNombre = 'N/A';
        if ($contacto && method_exists($contacto, 'barrio')) {
            try {
                $barrio = $contacto->barrio();
                $barrioNombre = $barrio->nombre ?? 'N/A';
            } catch (\Exception $e) {
                Log::warning('Error obteniendo barrio del contacto: ' . $e->getMessage());
            }
        }
        $paramValue = str_replace('[contacto.barrio]', $barrioNombre, $paramValue);
        
        $paramValue = str_replace('[contacto.celular]', $contacto->celular ?? '', $paramValue);
        $paramValue = str_replace('[contacto.email]', $contacto->email ?? '', $paramValue);

        // ============================================================
        // REEMPLAZOS DE FACTURA (si existe)
        // ============================================================
        if ($factura) {
            $paramValue = str_replace('[factura.fecha]', $factura->fecha ?? '', $paramValue);
            $paramValue = str_replace('[factura.vencimiento]', $factura->vencimiento ?? '', $paramValue);
            $paramValue = str_replace('[factura.codigo]', $factura->codigo ?? '', $paramValue);

            // Obtener total de la factura
            $facturaTotal = 0;
            try {
                if (method_exists($factura, 'total')) {
                    $totalObj = $factura->total();
                    if ($totalObj && isset($totalObj->total)) {
                        $facturaTotal = $totalObj->total;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo total de factura: ' . $e->getMessage());
            }
            $paramValue = str_replace('[factura.total]', number_format($facturaTotal, 0, ',', '.'), $paramValue);

            // Obtener porpagar de la factura
            $facturaPorpagar = 0;
            try {
                if (method_exists($factura, 'porpagar')) {
                    $facturaPorpagar = $factura->porpagar();
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo porpagar de factura: ' . $e->getMessage());
            }
            $paramValue = str_replace('[factura.porpagar]', number_format($facturaPorpagar, 0, ',', '.'), $paramValue);

            // Obtener contratos concatenados
            $contratos = '';
            try {
                $contratosArray = DB::table('facturas_contratos')
                    ->where('factura_id', $factura->id)
                    ->pluck('contrato_nro')
                    ->toArray();
                
                if (!empty($contratosArray)) {
                    $contratos = implode(', ', $contratosArray);
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo contratos de factura: ' . $e->getMessage());
            }
            $paramValue = str_replace('[factura.contrato]', $contratos, $paramValue);
        } else {
            // Si no hay factura, reemplazar con valores vacíos
            $paramValue = str_replace('[factura.fecha]', '', $paramValue);
            $paramValue = str_replace('[factura.vencimiento]', '', $paramValue);
            $paramValue = str_replace('[factura.codigo]', '', $paramValue);
            $paramValue = str_replace('[factura.total]', '0', $paramValue);
            $paramValue = str_replace('[factura.porpagar]', '0', $paramValue);
            $paramValue = str_replace('[factura.contrato]', '', $paramValue);
        }

        // ============================================================
        // REEMPLAZOS DE EMPRESA (si existe)
        // ============================================================
        if ($empresa) {
            $paramValue = str_replace('[empresa.nombre]', $empresa->nombre ?? '', $paramValue);
            $paramValue = str_replace('[empresa.nit]', $empresa->nit ?? '', $paramValue);
        } else {
            $paramValue = str_replace('[empresa.nombre]', '', $paramValue);
            $paramValue = str_replace('[empresa.nit]', '', $paramValue);
        }

        return $paramValue;
    }
}

