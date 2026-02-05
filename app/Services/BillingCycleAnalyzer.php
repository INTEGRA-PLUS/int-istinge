<?php

namespace App\Services;

use App\GrupoCorte;
use App\Contrato;
use App\Empresa;
use App\Model\Ingresos\Factura;
use App\NumeracionFactura;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BillingCycleAnalyzer
{
    /**
     * Obtiene estadísticas completas de un ciclo de facturación
     * 
     * @param int $grupoCorteId
     * @param string $periodo Formato: Y-m (ej: 2026-02)
     * @return array
     */
    public function getCycleStats($grupoCorteId, $periodo)
    {
        // Añadimos v4 para incluir validación de numeración y fallback de problema no identificado
        $cacheKey = "cycle_stats_v4_{$grupoCorteId}_{$periodo}";
        
        return Cache::remember($cacheKey, 3600, function () use ($grupoCorteId, $periodo) {
            $grupoCorte = GrupoCorte::find($grupoCorteId);
            if (!$grupoCorte) {
                return null;
            }

            // Calcular fecha del ciclo
            $fechaCiclo = $this->calcularFechaCiclo($grupoCorte, $periodo);
            
            // Obtener contratos que deberían facturar
            $contratosEsperados = $this->getContractsExpectedToInvoice($grupoCorteId, $periodo);
            
            // Obtener facturas generadas en el ciclo
            $facturasGeneradas = $this->getGeneratedInvoices($grupoCorteId, $fechaCiclo);
            
            // Análisis de facturas faltantes
            $missingAnalysis = $this->getMissingInvoicesAnalysis($grupoCorteId, $periodo);
            
            // Reporte de Cronología
            $onDateCount = 0;
            $outDateCount = 0;
            $diaEsperado = $this->calcularDiaEsperado($grupoCorte, $periodo);
            
            foreach ($facturasGeneradas as $factura) {
                // Si el día esperado es 0 (No aplica), las contamos todas en el primer contador (Detectadas)
                if ($diaEsperado == 0 || Carbon::parse($factura->fecha)->day == $diaEsperado) {
                    $onDateCount++;
                } else {
                    $outDateCount++;
                }
            }
            
            return [
                'grupo_corte' => $grupoCorte,
                'periodo' => $periodo,
                'fecha_ciclo' => $fechaCiclo,
                'total_contratos' => $contratosEsperados->count(),
                'facturas_generadas' => $facturasGeneradas->count(),
                'facturas_esperadas' => $contratosEsperados->count(),
                'facturas_faltantes' => $missingAnalysis['total'],
                'facturas_en_fecha' => $onDateCount,
                'facturas_fuera_fecha' => $outDateCount,
                'dia_esperado' => $diaEsperado,
                'tasa_exito' => $contratosEsperados->count() > 0 
                    ? round(($facturasGeneradas->count() / $contratosEsperados->count()) * 100, 2) 
                    : 0,
                'facturas' => $facturasGeneradas,
                'missing_reasons' => $missingAnalysis['reasons'],
                'missing_details' => $missingAnalysis['details']
            ];
        });
    }

    /**
     * Calcula el día esperado para el ciclo, manejando fin de mes
     */
    private function calcularDiaEsperado($grupoCorte, $periodo)
    {
        list($year, $month) = explode('-', $periodo);
        $dia = $grupoCorte->fecha_factura;
        
        if ($dia == 0) {
            return 0; // No aplica
        }
        
        $ultimoDiaMes = Carbon::create($year, $month, 1)->endOfMonth()->day;
        return ($dia > $ultimoDiaMes) ? $ultimoDiaMes : $dia;
    }

    /**
     * Calcula la fecha del ciclo basándose en la fecha_factura del grupo
     * Maneja correctamente meses con diferentes días (28, 30, 31)
     */
    private function calcularFechaCiclo($grupoCorte, $periodo)
    {
        list($year, $month) = explode('-', $periodo);
        $dia = $grupoCorte->fecha_factura;
        
        if ($dia == 0) {
            $dia = 1; // Si no aplica, usamos el día 1 para referenciar el mes correctamente
        }
        
        // Obtener último día del mes
        $ultimoDiaMes = Carbon::create($year, $month, 1)->endOfMonth()->day;
        
        // Si el día de facturación es mayor al último día del mes, usar el último día
        if ($dia > $ultimoDiaMes) {
            $dia = $ultimoDiaMes;
        }
        
        return Carbon::create($year, $month, $dia)->format('Y-m-d');
    }

    /**
     * Obtiene todos los contratos que deberían haber facturado en el ciclo
     */
    public function getContractsExpectedToInvoice($grupoCorteId, $periodo)
    {
        $grupoCorte = GrupoCorte::find($grupoCorteId);
        $fechaCiclo = $this->calcularFechaCiclo($grupoCorte, $periodo);
        
        $empresa = Empresa::find(1);
        $state = ['enabled'];
        if ($empresa->factura_contrato_off == 1) {
            $state[] = 'disabled';
        }

        // Obtener contratos del grupo (incluyendo deshabilitados para poder diagnosticar)
        $contratos = Contrato::join('contactos as c', 'c.id', '=', 'contracts.client_id')
            ->select('contracts.*', 'c.nombre as cli_nombre', 'c.apellido1 as cli_ap1', 'c.apellido2 as cli_ap2', 'c.nit as cli_nit')
            ->where('contracts.grupo_corte', $grupoCorteId)
            ->where('contracts.created_at', '<=', $fechaCiclo)
            ->get();

        return $contratos;
    }

    /**
     * Obtiene las facturas generadas en el ciclo
     */
    private function getGeneratedInvoices($grupoCorteId, $fechaCiclo)
    {
        $yearMonth = Carbon::parse($fechaCiclo)->format('Y-m');
        
        return Factura::join('contracts as c', 'c.id', '=', 'factura.contrato_id')
            ->join('contactos as cli', 'cli.id', '=', 'factura.cliente')
            ->select('factura.*', 'cli.nombre as nombre_cliente', 'c.nro as contrato_nro')
            ->where('c.grupo_corte', $grupoCorteId)
            ->where(function($query) {
                $query->where('factura.facturacion_automatica', 1)
                      ->orWhere(function($q) {
                          $q->where('factura.facturacion_automatica', 0)
                            ->where('factura.factura_mes_manual', 1);
                      });
            })
            ->whereRaw("DATE_FORMAT(factura.fecha, '%Y-%m') = ?", [$yearMonth])
            ->orderBy('factura.id', 'desc')
            ->get();
    }

    /**
     * Análisis completo de facturas faltantes con razones específicas
     */
    public function getMissingInvoicesAnalysis($grupoCorteId, $periodo)
    {
        $grupoCorte = GrupoCorte::find($grupoCorteId);
        $fechaCiclo = $this->calcularFechaCiclo($grupoCorte, $periodo);
        $empresa = Empresa::find(1);
        
        $contratosEsperados = $this->getContractsExpectedToInvoice($grupoCorteId, $periodo);
        $facturasGeneradas = $this->getGeneratedInvoices($grupoCorteId, $fechaCiclo);
        
        // Obtener nros de contratos que ya facturaron
        $contratosConFactura = $facturasGeneradas->pluck('contrato_nro')->unique()->toArray();
        
        // Contratos que no facturaron
        $contratosSinFactura = $contratosEsperados->filter(function($contrato) use ($contratosConFactura) {
            return !in_array($contrato->nro, $contratosConFactura);
        });
        
        $reasons = [];
        $details = [];
        
        foreach ($contratosSinFactura as $contrato) {
            $razon = $this->analyzeContractValidations($contrato, $fechaCiclo, $empresa, $grupoCorte);
            
            if (!isset($reasons[$razon['code']])) {
                $reasons[$razon['code']] = [
                    'code' => $razon['code'],
                    'title' => $razon['title'],
                    'count' => 0,
                    'color' => $razon['color']
                ];
            }
            
            $reasons[$razon['code']]['count']++;
            
            $details[] = [
                'contrato_nro' => $contrato->nro,
                'contrato_id' => $contrato->id,
                'cliente_id' => $contrato->client_id,
                'cliente_nombre' => trim("{$contrato->cli_nombre} {$contrato->cli_ap1} {$contrato->cli_ap2}"),
                'cliente_nit' => $contrato->cli_nit,
                'razon_code' => $razon['code'],
                'razon_title' => $razon['title'],
                'razon_description' => $razon['description']
            ];
        }
        
        return [
            'total' => $contratosSinFactura->count(),
            'reasons' => array_values($reasons),
            'details' => $details
        ];
    }

    /**
     * Analiza las validaciones del CronController para determinar por qué no se generó la factura
     * Replica la lógica de CronController::CrearFactura() líneas 336-730
     */
    private function analyzeContractValidations($contrato, $fechaCiclo, $empresa, $grupoCorte)
    {
        // 0. Validación PRIORITARIA: Grupo de corte deshabilitado (línea 264)
        if ($grupoCorte->status != 1) {
            return [
                'code' => 'billing_group_disabled',
                'title' => 'Grupo de corte deshabilitado',
                'description' => 'El grupo de corte no está activo',
                'color' => 'danger'
            ];
        }

        // 1. Validación: Primera factura del contrato (líneas 336-359)
        $creacion_contrato = Carbon::parse($contrato->created_at);
        $dia_creacion_contrato = $creacion_contrato->day;
        $dia_creacion_factura = $grupoCorte->fecha_factura;
        
        if ($dia_creacion_contrato <= $dia_creacion_factura) {
            $primer_fecha_factura = $creacion_contrato->copy()->day($dia_creacion_factura);
        } else {
            $primer_fecha_factura = $creacion_contrato->copy()->addMonth()->day($dia_creacion_factura);
        }
        $primer_fecha_factura = Carbon::parse($primer_fecha_factura)->format("Y-m-d");
        
        if (!DB::table('facturas_contratos as fc')->where('contrato_nro', $contrato->nro)->first()) {
            if (isset($primer_fecha_factura) && 
                Carbon::parse($fechaCiclo)->format("Y-m-d") == $primer_fecha_factura && 
                $contrato->fact_primer_mes == 0) {
                return [
                    'code' => 'first_invoice_skip',
                    'title' => 'Primera factura no corresponde',
                    'description' => 'El contrato tiene fact_primer_mes = 0 y es su primer ciclo de facturación',
                    'color' => 'info'
                ];
            }
        }
        
        // 2. Validación: Factura del mes ya existe (líneas 362-388)
        $ultimaFactura = DB::table('facturas_contratos')
            ->join('factura', 'facturas_contratos.factura_id', '=', 'factura.id')
            ->where('facturas_contratos.contrato_nro', $contrato->nro)
            ->where('factura.estatus', '!=', 2)
            ->select('factura.*')
            ->orderBy('factura.fecha', 'desc')
            ->first();
        
        $mesActualFactura = date('Y-m', strtotime($fechaCiclo));
        
        if ($ultimaFactura) {
            if ($ultimaFactura->tipo == 2) {
                $mesUltimaFactura = date('Y-m', strtotime($ultimaFactura->created_at));
            } else {
                $mesUltimaFactura = date('Y-m', strtotime($ultimaFactura->fecha));
            }
            
            if ($mesActualFactura == $mesUltimaFactura) {
                if ($ultimaFactura->factura_mes_manual == 1) {
                    return [
                        'code' => 'invoice_month_exists',
                        'title' => 'Factura del mes ya existe',
                        'description' => 'Ya tiene una factura generada para este mes con factura_mes_manual = 1',
                        'color' => 'warning'
                    ];
                }
            }
            
            // 3. Validación: Factura abierta vigente (líneas 397-409)
            if ($mesActualFactura != $mesUltimaFactura || 
                ($mesActualFactura == $mesUltimaFactura && $ultimaFactura->factura_mes_manual == 0 && $ultimaFactura->facturacion_automatica == 0)) {
                
                if ($ultimaFactura->estatus == 1 && $ultimaFactura->vencimiento > $fechaCiclo) {
                    return [
                        'code' => 'open_invoice_active',
                        'title' => 'Factura abierta vigente',
                        'description' => 'Tiene una factura abierta con vencimiento mayor a la fecha del ciclo',
                        'color' => 'primary'
                    ];
                }
            }
        }
        
        // 4. Validación: Sin numeración asignada (línea 415-417)
        $nro = NumeracionFactura::tipoNumeracion($contrato);
        if (is_null($nro)) {
            return [
                'code' => 'no_numbering',
                'title' => 'Sin numeración asignada',
                'description' => 'El contrato no tiene una numeración de facturas asignada',
                'color' => 'danger'
            ];
        }
        
        // 5. Validación: Ya se creó factura hoy (líneas 422-424)
        $hoy = $fechaCiclo;
        if (DB::table('facturas_contratos')
            ->whereDate('created_at', $hoy)
            ->where('contrato_nro', $contrato->nro)
            ->where('is_cron', 1)
            ->first()) {
            return [
                'code' => 'duplicate_today',
                'title' => 'Factura duplicada del día',
                'description' => 'Ya se creó una factura automática para este contrato en la fecha del ciclo',
                'color' => 'secondary'
            ];
        }
        
        // 6. Validación: Estado deshabilitado (líneas 153-155 del CronController)
        // El 'state' puede ser 'enabled' o 'disabled'. Si es 'disabled' y no se permite facturar contratos OFF.
        if ($contrato->state != 'enabled' && $empresa->factura_contrato_off != 1) {
            return [
                'code' => 'contract_disabled_off',
                'title' => 'El contrato tiene estado deshabilitado',
                'description' => 'El contrato está deshabilitado y la empresa no permite facturar contratos OFF (factura_contrato_off = 0)',
                'color' => 'danger',
                'action_required' => 'enable_off_billing'
            ];
        }
        
        // 7. Validación: Status inactivo (columna status != 1)
        if ($contrato->status != 1) {
            return [
                'code' => 'status_inactive',
                'title' => 'El contrato está inactivo (Status)',
                'description' => 'El contrato tiene el campo status diferente de 1',
                'color' => 'danger'
            ];
        }
        
        // 8. Validación COMPLETADA (Numeración): (Líneas 415-417 CronController)
        // Obtenemos el número depende del contrato que tenga asignado (con fact electrónica o estándar).
        $nro_numeracion = NumeracionFactura::tipoNumeracion($contrato);
        if (is_null($nro_numeracion)) {
            return [
                'code' => 'no_valid_numbering',
                'title' => 'Numeración no asignada o vencida',
                'description' => 'El contrato no tiene una numeración de facturas asignada, está vencida o no es preferida',
                'color' => 'danger',
                'action_required' => 'fix_numbering'
            ];
        }
        
        // 8. Validación removida (movida al inicio por prioridad)
        
        // Si llegamos aquí y no tiene factura, es un problema no identificado pero cumple condiciones básicas
        return [
            'code' => 'unidentified_issue',
            'title' => 'Problema no identificado',
            'description' => 'El contrato cumple con las condiciones básicas para generar factura pero el sistema no detectó una razón específica de omisión. Se recomienda intentar la generación manual.',
            'color' => 'secondary',
            'action_required' => 'manual_generation'
        ];
    }

    /**
     * Obtiene datos históricos de ciclos para gráficas (últimos N meses)
     */
    public function getHistoricalData($grupoCorteId, $months = 6)
    {
        $grupoCorte = GrupoCorte::find($grupoCorteId);
        $data = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $periodo = Carbon::now()->subMonths($i)->format('Y-m');
            $stats = $this->getCycleStats($grupoCorteId, $periodo);
            
            if ($stats) {
                $data[] = [
                    'periodo' => $periodo,
                    'periodo_label' => Carbon::parse($periodo . '-01')->locale('es')->isoFormat('MMM Y'),
                    'generadas' => $stats['facturas_generadas'],
                    'esperadas' => $stats['facturas_esperadas'],
                    'tasa_exito' => $stats['tasa_exito']
                ];
            }
        }
        
        return $data;
    }

    /**
     * Obtiene lista de todos los períodos disponibles desde el primer ciclo
     */
    public function getAvailableCycles($grupoCorteId)
    {
        $grupoCorte = GrupoCorte::find($grupoCorteId);
        
        // Obtener la fecha del contrato más antiguo del grupo
        $primerContrato = Contrato::where('grupo_corte', $grupoCorteId)
            ->orderBy('created_at', 'asc')
            ->first();
        
        if (!$primerContrato) {
            return [];
        }
        
        $fechaInicio = Carbon::parse($primerContrato->created_at)->startOfMonth();
        $fechaActual = Carbon::now()->endOfMonth();
        
        $ciclos = [];
        $fecha = $fechaInicio->copy();
        
        while ($fecha <= $fechaActual) {
            $ciclos[] = [
                'value' => $fecha->format('Y-m'),
                'label' => $fecha->locale('es')->isoFormat('MMMM YYYY')
            ];
            $fecha->addMonth();
        }
        
        return array_reverse($ciclos); // Más recientes primero
    }
}
