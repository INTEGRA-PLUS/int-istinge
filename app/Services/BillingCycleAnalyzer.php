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
        // Añadimos v19 para corregir error de propiedad tipo en historial
        $cacheKey = "cycle_stats_v19_{$grupoCorteId}_{$periodo}";
        
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
            
            $whatsappStats = [
                'sent' => 0,
                'pending' => 0
            ];

            foreach ($facturasGeneradas as $factura) {
                // Si el día esperado es 0 (No aplica), las contamos todas en el primer contador (Detectadas)
                if ($diaEsperado == 0 || Carbon::parse($factura->fecha)->day == $diaEsperado) {
                    $onDateCount++;
                } else {
                    $outDateCount++;
                }

                if ($factura->whatsapp == 1) {
                    $whatsappStats['sent']++;
                } else {
                    $whatsappStats['pending']++;
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
                'whatsapp_stats' => $whatsappStats,
                'dia_esperado' => $diaEsperado,
                'tasa_exito' => $contratosEsperados->count() > 0 
                    ? round(($facturasGeneradas->count() / $contratosEsperados->count()) * 100, 2) 
                    : 0,
                'facturas' => $facturasGeneradas,
                'missing_reasons' => $missingAnalysis['reasons'],
                'missing_details' => $missingAnalysis['details'],
                'missing_breakdown' => $missingAnalysis['missing_breakdown'],
                'duplicates_analysis' => $this->getDuplicateInvoicesAnalysis($facturasGeneradas),
                'numbering_health' => $this->checkNumberingHealth($contratosEsperados->count())
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
            ->where('contracts.status', 1) // REQ: Solo contratos activos (status=1)
            ->get();

        return $contratos;
    }

    /**
     * Obtiene las facturas generadas en el ciclo
     */
    private function getGeneratedInvoices($grupoCorteId, $fechaCiclo)
    {
        $yearMonth = Carbon::parse($fechaCiclo)->format('Y-m');
        
        // 1. Facturas vinculadas directamente por contrato_id
        $directas = Factura::join('contracts as c', 'c.id', '=', 'factura.contrato_id')
            ->join('contactos as cli', 'cli.id', '=', 'factura.cliente')
            ->select('factura.*', 'cli.nombre as nombre_cliente', 'c.nro as contrato_nro', 'c.id as contrato_id')
            ->where('c.grupo_corte', $grupoCorteId)
            ->where('factura.estatus', '!=', 2)
            ->whereRaw("DATE_FORMAT(factura.fecha, '%Y-%m') = ?", [$yearMonth])
            ->where(function($query) {
                $query->where('factura.facturacion_automatica', 1)
                      ->orWhere(function($q) {
                          $q->where('factura.facturacion_automatica', 0)
                            ->where('factura.factura_mes_manual', 1);
                      });
            })
            ->get();
            
        // 2. Facturas vinculadas mediante tabla pivot facturas_contratos
        $viaPivot = Factura::join('facturas_contratos as fc', 'factura.id', '=', 'fc.factura_id')
            ->join('contracts as c', 'fc.contrato_nro', '=', 'c.nro')
            ->join('contactos as cli', 'cli.id', '=', 'factura.cliente')
            ->select('factura.*', 'cli.nombre as nombre_cliente', 'c.nro as contrato_nro', 'c.id as contrato_id')
            ->where('c.grupo_corte', $grupoCorteId)
            ->where('factura.estatus', '!=', 2)
            ->whereRaw("DATE_FORMAT(factura.fecha, '%Y-%m') = ?", [$yearMonth])
            ->where(function($query) {
                $query->where('factura.facturacion_automatica', 1)
                      ->orWhere(function($q) {
                          $q->where('factura.facturacion_automatica', 0)
                            ->where('factura.factura_mes_manual', 1);
                      });
            })
            ->get();
            
        return $directas->merge($viaPivot)->unique(function($item) {
            return $item->id . '-' . $item->contrato_id;
        });
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
        
        // Obtener nros de contratos que ya facturaron (usando contrato_id para mayor precisión)
        $idsGenerados = $facturasGeneradas->pluck('contrato_id')->unique()->toArray();
        
        // Contratos que no facturaron
        $contratosSinFactura = $contratosEsperados->filter(function($contrato) use ($idsGenerados) {
            return !in_array($contrato->id, $idsGenerados);
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
                'razon_description' => $razon['description'],
                'factura_id' => $razon['factura_id'] ?? null,
                'factura_nro' => $razon['factura_nro'] ?? null
            ];
        }
        
        return [
            'total' => $contratosSinFactura->count(),
            'reasons' => array_values($reasons),
            'details' => $details,
            'missing_breakdown' => $this->calculateMissingBreakdown($contratosSinFactura)
        ];
    }

    /**
     * Calcula el desglose de contratos por tipo de facturación
     */
    private function calculateMissingBreakdown($contratos)
    {
        $breakdown = [
            'standard' => 0,
            'electronic' => 0
        ];

        foreach ($contratos as $contrato) {
            // Lógica basada en NumeracionFactura::tipoNumeracion
            if (isset($contrato->facturacion) && $contrato->facturacion == 3) {
                $breakdown['electronic']++;
            } else {
                $breakdown['standard']++;
            }
        }

        return $breakdown;
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
        $ultimaFactura = $this->getUltimoHistorialFacturacion($contrato);
        
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
                        'description' => 'Ya tiene una factura generada para este mes con "Factura del Mes" = SI',
                        'color' => 'warning'
                    ];
                } else if ($ultimaFactura->facturacion_automatica == 0) {
                    // Existe en el mes, pero es manual y no está marcada como mes_manual=1
                    $fechaFormateada = Carbon::parse($ultimaFactura->fecha)->translatedFormat('j \d\e F - Y');
                    return [
                        'code' => 'manual_invoice_unflagged',
                        'title' => 'Factura manual sin marcar',
                        'description' => "Se detectó una factura manual creada en la fecha {$fechaFormateada} (fecha de la factura) pero no tiene marcado el atributo 'Factura del Mes'. Por esto el sistema no la vincula al ciclo.",
                        'color' => 'danger',
                        'factura_id' => $ultimaFactura->id,
                        'factura_nro' => $ultimaFactura->nro
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
        
        // 5. Validación: Ya se creó factura para esta fecha (líneas 422-424)
        // Buscamos tanto en enlace directo como en tabla pivot
        $existeFacturaDirecto = DB::table('factura')
            ->whereDate('fecha', $fechaCiclo)
            ->where('contrato_id', $contrato->id)
            ->where('estatus', '!=', 2)
            ->first();
            
        $existeFacturaPivot = DB::table('facturas_contratos')
            ->join('factura', 'factura.id', '=', 'facturas_contratos.factura_id')
            ->whereDate('factura.fecha', $fechaCiclo)
            ->where('facturas_contratos.contrato_nro', $contrato->nro)
            ->where('factura.estatus', '!=', 2)
            ->first();

        if ($existeFacturaDirecto || $existeFacturaPivot) {
            return [
                'code' => 'duplicate_today',
                'title' => 'Factura ya generada',
                'description' => 'Ya existe una factura para este contrato con la fecha del ciclo',
                'color' => 'success'
            ];
        }
        
        // 6. Validación: Estado deshabilitado (state)
        if ($contrato->state == 'disabled' && $empresa->factura_contrato_off != 1) {
            return [
                'code' => 'contract_disabled_off',
                'title' => 'El contrato tiene estado deshabilitado',
                'description' => 'El contrato está deshabilitado y la empresa no permite facturar contratos OFF (factura_contrato_off = 0)',
                'color' => 'danger',
                'action_required' => 'enable_off_billing'
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

    /**
     * Verifica la salud de las numeraciones de facturación con proyección de consumo
     * 
     * @param int $consumoEstimadoMensual Cantidad de facturas promedio que se generan por mes (base contratos activos)
     */
    public function checkNumberingHealth($consumoEstimadoMensual = 0)
    {
        $empresaId = 1; 
        if (auth()->check()) {
            $empresaId = auth()->user()->empresa;
        }

        // Si el consumo estimado es 0 (ej: grupo vacío/nuevo), asumimos un mínimo de 1 para evitar división por cero
        // O mejor, usamos un valor base razonable si no hay contratos, pero aquí la proyección es contextual
        $consumoBase = $consumoEstimadoMensual > 0 ? $consumoEstimadoMensual : 100;

        $health = [
            'standard' => ['status' => 'ok', 'message' => 'Numeración Estándar OK'],
            'electronic' => ['status' => 'ok', 'message' => 'Numeración Electrónica OK']
        ];

        // 1. Numeración Estándar (Tipo 1)
        $estandar = NumeracionFactura::where('empresa', $empresaId)
            ->where('preferida', 1)
            ->where('estado', 1)
            ->where('tipo', 1)
            ->where('num_equivalente', 0)
            ->where('nomina', 0)
            ->first();

        $health['standard'] = $this->analyzeNumbering($estandar, 'Estándar', $consumoBase);

        // 2. Numeración Electrónica (Tipo 2)
        $electronica = NumeracionFactura::where('empresa', $empresaId)
            ->where('preferida', 1)
            ->where('estado', 1)
            ->where('tipo', 2)
            ->where('num_equivalente', 0)
            ->where('nomina', 0)
            ->first();

        $health['electronic'] = $this->analyzeNumbering($electronica, 'Electrónica', $consumoBase);

        return $health;
    }

    /**
     * Analiza una numeración específica y genera su reporte de salud
     */
    private function analyzeNumbering($numeracion, $tipo, $consumoEstimado)
    {
        if (!$numeracion) {
            return [
                'status' => 'error', 
                'message' => "No hay numeración $tipo preferida activa",
                'details' => null
            ];
        }

        $restantes = $numeracion->final - $numeracion->inicio;
        $diasVencimiento = Carbon::now()->diffInDays(Carbon::parse($numeracion->hasta), false);
        $status = 'ok';
        $message = "Numeración $tipo operativa";
        $recommendation = "La numeración es suficiente para la operación actual.";

        // Proyección de suficiencia (meses)
        $mesesSuficiencia = $consumoEstimado > 0 ? floor($restantes / $consumoEstimado) : 999;

        // Validaciones
        if ($diasVencimiento < 0) {
            $status = 'error';
            $message = "Resolución vencida (Venció el " . Carbon::parse($numeracion->hasta)->format('d/m/Y') . ")";
            $recommendation = "Solicitar nueva resolución inmediatamente.";
        } elseif ($numeracion->inicio >= $numeracion->final) {
            $status = 'error';
            $message = "Consecutivos agotados (Llegó al límite: {$numeracion->final})";
            $recommendation = "Solicitar nueva resolución inmediatamente.";
        } elseif ($diasVencimiento < 30) {
            $status = 'warning';
            $message = "Resolución vence pronto ({$diasVencimiento} días)";
            $recommendation = "Tramitar renovación antes del " . Carbon::parse($numeracion->hasta)->format('d/m/Y') . ".";
        } elseif ($restantes < $consumoEstimado) {
            // No alcanza para un ciclo completo estimado
            $status = 'warning';
            $message = "Insuficiente para próximo ciclo completo";
            $recommendation = "Quedan $restantes folios. Se requieren aprox $consumoEstimado para un ciclo completo.";
        } elseif ($restantes < 50) {
            $status = 'warning';
            $message = "Quedan pocos consecutivos ($restantes)";
            $recommendation = "Solicitar nueva resolución pronto.";
        }

        // Si todo está OK, dar una proyección positiva
        if ($status == 'ok') {
            if ($mesesSuficiencia < 3) {
                $recommendation = "Suficiente para aprox. $mesesSuficiencia ciclos de facturación.";
            } else {
                $recommendation = "Numeración saludable. Cobertura estimada para +3 ciclos.";
            }
        }

        return [
            'status' => $status,
            'message' => $message,
            'recommendation' => $recommendation,
            'details' => [
                'expiration' => Carbon::parse($numeracion->hasta)->format('d/m/Y'),
                'current' => $numeracion->inicio,
                'limit' => $numeracion->final,
                'remaining' => $restantes,
                'sufficiency_months' => $mesesSuficiencia
            ]
        ];
    }

    /**
     * Marca en lote las facturas manuales detectadas como "no vinculadas"
     * 
     * @param int $grupoCorteId
     * @param string $periodo
     * @return int Cantidad de facturas marcadas
     */
    public function marcarFacturasMesLote($grupoCorteId, $periodo)
    {
        $missingAnalysis = $this->getMissingInvoicesAnalysis($grupoCorteId, $periodo);
        $idsToFix = [];

        foreach ($missingAnalysis['details'] as $detail) {
            if ($detail['razon_code'] === 'manual_invoice_unflagged' && !empty($detail['factura_id'])) {
                $idsToFix[] = $detail['factura_id'];
            }
        }

        if (empty($idsToFix)) {
            return 0;
        }

        return Factura::whereIn('id', $idsToFix)->update(['factura_mes_manual' => 1]);
    }

    /**
     * Busca la última factura de un contrato consultando tanto el enlace directo como la tabla pivot
     */
    private function getUltimoHistorialFacturacion($contrato)
    {
        $f1 = DB::table('factura')
            ->where('contrato_id', $contrato->id)
            ->where('estatus', '!=', 2) // Excepto anuladas
            ->select('id', 'fecha', 'nro', 'codigo', 'factura_mes_manual', 'facturacion_automatica', 'tipo', 'created_at', 'estatus', 'vencimiento')
            ->get();
            
        $f2 = DB::table('factura')
            ->join('facturas_contratos as fc', 'factura.id', '=', 'fc.factura_id')
            ->where('fc.contrato_nro', $contrato->nro)
            ->where('factura.estatus', '!=', 2)
            ->select('factura.id', 'factura.fecha', 'factura.nro', 'factura.codigo', 'factura.factura_mes_manual', 'factura.facturacion_automatica', 'factura.tipo', 'factura.created_at', 'factura.estatus', 'factura.vencimiento')
            ->get();
            
        return $f1->concat($f2)->sortByDesc('fecha')->first();
    }

    /**
     * Analiza si existen contratos con múltiples facturas en el mismo ciclo
     * 
     * @param Collection $facturasGeneradas
     * @return array
     */
    private function getDuplicateInvoicesAnalysis($facturasGeneradas)
    {
        $duplicates = [];
        $totalExcedentes = 0;
        
        // Agrupar por contrato_id
        $grouped = $facturasGeneradas->groupBy('contrato_id');
        
        foreach ($grouped as $contratoId => $facturas) {
            if ($facturas->count() > 1) {
                $totalExcedentes += ($facturas->count() - 1);
                $duplicates[] = [
                    'contrato_id' => $contratoId,
                    'contrato_nro' => $facturas->first()->contrato_nro,
                    'cliente_id' => $facturas->first()->cliente,
                    'cliente_nombre' => $facturas->first()->nombre_cliente,
                    'cantidad' => $facturas->count(),
                    'facturas' => $facturas->map(function($f) {
                        return [
                            'id' => $f->id,
                            'nro' => $f->nro,
                            'codigo' => $f->codigo,
                            'fecha' => $f->fecha,
                            'total' => $f->totalAPI(1)->total ?? 0,
                            'estatus' => $f->estatus,
                            'tipo_operacion' => $f->tipo_operacion == 1 ? 'Estandar' : 'Electronica'
                        ];
                    })->toArray()
                ];
            }
        }
        
        return [
            'total_excedentes' => $totalExcedentes,
            'contratos_duplicados' => $duplicates,
            'conteo_duplicados' => count($duplicates)
        ];
    }

    /**
     * Obtiene el query builder para las facturas generadas (para DataTables)
     */
    public function getGeneratedInvoicesQuery($grupoCorteId, $periodo)
    {
        $grupoCorte = GrupoCorte::find($grupoCorteId);
        if (!$grupoCorte) {
            return null;
        }
        
        $fechaCiclo = $this->calcularFechaCiclo($grupoCorte, $periodo);
        $yearMonth = Carbon::parse($fechaCiclo)->format('Y-m');
        
        // Query 1: Facturas vinculadas directamente
        $query1 = Factura::join('contracts as c', 'c.id', '=', 'factura.contrato_id')
            ->join('contactos as cli', 'cli.id', '=', 'factura.cliente')
            ->select(
                'factura.id', 
                'factura.nro', 
                'factura.codigo', 
                'factura.fecha', 
                'factura.vencimiento', 
                'factura.estatus', 
                'factura.whatsapp', 
                'cli.nombre as nombre_cliente', 
                'c.nro as contrato_nro'
            )
            ->where('c.grupo_corte', $grupoCorteId)
            ->where('factura.estatus', '!=', 2)
            ->whereRaw("DATE_FORMAT(factura.fecha, '%Y-%m') = ?", [$yearMonth])
            ->where(function($query) {
                $query->where('factura.facturacion_automatica', 1)
                      ->orWhere(function($q) {
                          $q->where('factura.facturacion_automatica', 0)
                            ->where('factura.factura_mes_manual', 1);
                      });
            });

        // Query 2: Facturas vinculadas por pivot
        $query2 = Factura::join('facturas_contratos as fc', 'factura.id', '=', 'fc.factura_id')
            ->join('contracts as c', 'fc.contrato_nro', '=', 'c.nro')
            ->join('contactos as cli', 'cli.id', '=', 'factura.cliente')
            ->select(
                'factura.id', 
                'factura.nro', 
                'factura.codigo', 
                'factura.fecha', 
                'factura.vencimiento', 
                'factura.estatus', 
                'factura.whatsapp', 
                'cli.nombre as nombre_cliente', 
                'c.nro as contrato_nro'
            )
            ->where('c.grupo_corte', $grupoCorteId)
            ->where('factura.estatus', '!=', 2)
            ->whereRaw("DATE_FORMAT(factura.fecha, '%Y-%m') = ?", [$yearMonth])
            ->where(function($query) {
                $query->where('factura.facturacion_automatica', 1)
                      ->orWhere(function($q) {
                          $q->where('factura.facturacion_automatica', 0)
                            ->where('factura.factura_mes_manual', 1);
                      });
            });

        return $query1->union($query2);
    }
}
