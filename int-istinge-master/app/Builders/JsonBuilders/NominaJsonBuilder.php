<?php

namespace App\Builders\JsonBuilders;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class NominaJsonBuilder
{
    /**
     * Construye la sección del empleador usando la estructura nueva.
     */
    public static function buildFromEmployer(array $nominaJson): array
    {
        $data = Arr::get($nominaJson, 'Empleador', []);

        return [
            'razonSocial'        => Arr::get($data, 'RazonSocial', ''),
            'nit'                => Arr::get($data, 'NIT', ''),
            'pais'               => Arr::get($data, 'Pais', ''),
            'departamentoEstado' => Arr::get($data, 'DepartamentoEstado', ''),
            'municipioCiudad'    => Arr::get($data, 'MunicipioCiudad', ''),
            'direccion'          => Arr::get($data, 'Direccion', ''),
        ];
    }

    /**
     * Construye el arreglo de trabajadores bajo la nueva estructura.
     */
    public static function buildFromWorkers(array $nominaJson): array
    {
        $tipoXML = Arr::get($nominaJson, 'InformacionGeneral.TipoXML', '');

        $trabajador = [
            'trabajador'         => self::mapTrabajador($nominaJson),
            'numeroSecuenciaXML' => self::mapNumeroSecuencia($nominaJson),
            'informacionGeneral' => self::mapInformacionGeneral($nominaJson),
            'fechasPagos'        => self::mapFechasPagos($nominaJson),
            'basicoDevengados'   => self::mapBasicoDevengados($nominaJson),
            'cesantias'          => self::mapCesantias($nominaJson),
            'compensaciones'     => self::mapCompensaciones($nominaJson),
            'deducciones'        => self::mapDeducciones($nominaJson),
            'devengados'         => self::mapDevengados($nominaJson),
            'fondoSP'            => self::mapFondoSP($nominaJson),
            'horasExtras'        => self::mapHorasExtras($nominaJson),
            'huelgasLegales'     => self::mapHuelgasLegales($nominaJson),
            'notas'              => self::mapNotas($nominaJson),
            'otrasDeducciones'   => self::mapOtrasDeducciones($nominaJson),
            'otrosConceptos'     => self::mapOtrosConceptos($nominaJson),
            'otrosDevengados'    => self::mapOtrosDevengados($nominaJson),
            'pago'               => self::mapPago($nominaJson),
            'primas'             => self::mapPrimas($nominaJson),
            'sanciones'          => self::mapSanciones($nominaJson),
            'transporte'         => self::mapTransporte($nominaJson),
        ];

        // documentRef solo se incluye cuando tipoXML = 102 (nómina individual)
        if ($tipoXML !== '102') {
            $trabajador['documentRef'] = self::mapDocumentReference($nominaJson);
        }

        return [$trabajador];
    }

    /**
     * Construye el json final que se enviará al proveedor.
     */
    public static function buildFullPayroll(array $data): array
    {
        return [
            'empleador'   => $data['empleador'] ?? [],
            'trabajadores'=> $data['trabajadores'] ?? [],
            'mode'        => $data['mode'] ?? null,
            'btw_login'   => $data['btw_login'] ?? null,
            'software'    => $data['software'] ?? null,
        ];
    }

    private static function mapTrabajador(array $nominaJson): array
    {
        $data    = Arr::get($nominaJson, 'Trabajador', []);
        $periodo = Arr::get($nominaJson, 'Periodo', []);

        return [
            'tipoTrabajador'                => Arr::get($data, 'TipoTrabajador', ''),
            'fechaIngreso'                  => Arr::get($periodo, 'FechaIngreso'),
            'fechaRetiro'                   => Arr::get($periodo, 'FechaRetiro'),
            'tiempoLaborado'                => Arr::get($periodo, 'TiempoLaborado', ''),
            'subTipoTrabajador'             => Arr::get($data, 'SubTipoTrabajador', ''),
            'AltoRiesgoPension'             => self::toBool(Arr::get($data, 'AltoRiesgoPension', false)),
            'tipoDocumento'                 => Arr::get($data, 'TipoDocumento', ''),
            'numeroDocumento'               => Arr::get($data, 'NumeroDocumento', ''),
            'primerApellido'                => Arr::get($data, 'PrimerApellido', ''),
            'segundoApellido'               => Arr::get($data, 'SegundoApellido', ''),
            'primerNombre'                  => Arr::get($data, 'PrimerNombre', ''),
            'otrosNombres'                  => Arr::get($data, 'OtrosNombres', ''),
            'lugarTrabajoPais'              => Arr::get($data, 'LugarTrabajoPais', ''),
            'lugarTrabajoMunicipioCiudad'   => Arr::get($data, 'LugarTrabajoMunicipioCiudad', ''),
            'lugarTrabajoDepartamentoEstado'=> Arr::get($data, 'LugarTrabajoDepartamentoEstado', ''),
            'lugarTrabajoDireccion'         => Arr::get($data, 'LugarTrabajoDireccion', ''),
            'salarioIntegral'               => self::toBool(Arr::get($data, 'SalarioIntegral', false)),
            'tipoContrato'                  => self::formatInteger(Arr::get($data, 'TipoContrato')),
            'sueldo'                        => self::formatNumber(Arr::get($data, 'Sueldo', 0)),
            'codigoTrabajador'              => Arr::get($data, 'CodigoTrabajador', ''),
        ];
    }

    private static function mapNumeroSecuencia(array $nominaJson): array
    {
        $data = Arr::get($nominaJson, 'NumeroSecuenciaXML', []);

        return [
            'prefijo'        => Arr::get($data, 'Prefijo', ''),
            'consecutivo'    => Arr::get($data, 'Consecutivo', ''),
            'numero'         => Arr::get($data, 'Numero', ''),
        ];
    }

    private static function mapInformacionGeneral(array $nominaJson): array
    {
        $info    = Arr::get($nominaJson, 'InformacionGeneral', []);
        $periodo = Arr::get($nominaJson, 'Periodo', []);
        $fechaGen = Arr::get($info, 'FechaGen');

        return [
            'fechaGen'         => $fechaGen ? Carbon::parse($fechaGen)->format('Y-m-d\TH:i:s') : null,
            'fechaPagoInicio'  => Arr::get($periodo, 'FechaLiquidacionInicio'),
            'fechaPagoFin'     => Arr::get($periodo, 'FechaLiquidacionFin'),
            'tipoNomina'       => Arr::get($info, 'TipoXML'),
            'periodoNomina'    => Arr::get($info, 'PeriodoNomina'),
            'tipoMoneda'       => Arr::get($info, 'TipoMoneda'),
            'tipoNota'         => Arr::get($info, 'TipoNota', ''),
            'notas'            => Arr::get($info, 'Notas', ''),
            'numDocNovedad'    => Arr::get($info, 'NumDocNovedad', ''),
            'trm'              => self::formatNumber(Arr::get($info, 'TRM', 0)),
            'devengadosTotal'  => self::formatNumber(Arr::get($nominaJson, 'DevengadosTotal', 0)),
            'deduccionesTotal' => self::formatNumber(Arr::get($nominaJson, 'DeduccionesTotal', 0)),
            'comprobanteTotal' => self::formatNumber(Arr::get($nominaJson, 'ComprobanteTotal', 0)),
        ];
    }

    private static function mapDocumentReference(array $nominaJson): array
    {
        // Se obtiene primero la fecha, ya sea FechaGenPred o InformacionGeneral.FechaGen
        $fecha = Arr::get($nominaJson, 'DocumentRef.FechaGenPred')
            ?? Arr::get($nominaJson, 'InformacionGeneral.FechaGen');

        // Si existe la fecha, la formateamos
        $fechaFormateada = $fecha
            ? Carbon::parse($fecha)->format('Y-m-d\TH:i:s')
            : null;

        return [
            'numRef'       => Arr::get($nominaJson, 'DocumentRef.NumRef', ''),
            'fechaGenPred' => $fechaFormateada,
            'cuneRef'      => Arr::get($nominaJson, 'DocumentRef.CuneRef', ''),
        ];
    }

    private static function mapFechasPagos(array $nominaJson): array
    {
        $fechas = Arr::get($nominaJson, 'FechasPagos.FechaPago', []);

        return array_map(function ($fecha) {
            return [
                'fecha' => Carbon::parse($fecha)->format('Y-m-d\TH:i:s')
            ];
        }, (array) $fechas);
    }

    private static function mapBasicoDevengados(array $nominaJson): array
    {
        $basico = Arr::get($nominaJson, 'Devengados.Basico', []);

        return [
            'diasTrabajados' => Arr::get($basico, 'DiasTrabajados', 0),
            'sueldoTrabajado'=> self::formatNumber(Arr::get($basico, 'SueldoTrabajado', 0)),
        ];
    }

    private static function mapCesantias(array $nominaJson): array
    {
        $cesantias = Arr::get($nominaJson, 'Devengados.Cesantias', []);

        if (empty($cesantias)) {
            return [
                'pago'          => 0,
                'porcentaje'    => 0,
                'pagoIntereses' => 0,
            ];
        }

        return [
            'pago'          => self::formatNumber(Arr::get($cesantias, 'Pago', 0)),
            'porcentaje'    => self::formatNumber(Arr::get($cesantias, 'Porcentaje', 0)),
            'pagoIntereses' => self::formatNumber(Arr::get($cesantias, 'PagoIntereses', 0)),
        ];
    }

    private static function mapCompensaciones(array $nominaJson): array
    {
        $compensaciones = Arr::get($nominaJson, 'Devengados.Compensaciones.Compensacion', []);

        return array_map(function ($item) {
            return [
                'compensacionO' => self::formatNumber(Arr::get($item, 'CompensacionO', 0)),
                'compensacionE' => self::formatNumber(Arr::get($item, 'CompensacionE', 0)),
            ];
        }, (array) $compensaciones);
    }

    private static function mapDeducciones(array $nominaJson): array
    {
        $deducciones   = Arr::get($nominaJson, 'Deducciones', []);
        $valorBase     = self::formatNumber(Arr::get($nominaJson, 'Devengados.Basico.SueldoTrabajado', 0));

        $estructuraMap = [
            1 => ['key' => 'Salud',        'descripcion' => 'Salud'],
            2 => ['key' => 'FondoPension', 'descripcion' => 'FondoPension'],
            3 => ['key' => 'FondoSP',      'descripcion' => 'FondoSP', 'valueKey' => 'DeduccionSP'],
            4 => ['key' => 'Libranzas.Libranza.0', 'descripcion' => 'Libranza', 'valueKey' => 'Deduccion'],
        ];

        $response = [];

        foreach ($estructuraMap as $tipo => $config) {
            $item = Arr::get($deducciones, $config['key'], []);

            if (empty($item)) {
                continue;
            }

            $porcentaje = Arr::get($item, 'Porcentaje', Arr::get($item, 'porcentaje', 0));
            $valor      = Arr::get($item, $config['valueKey'] ?? 'Deduccion', 0);
            $valor      = self::formatNumber($valor);

            //No mostrar libranza cuando su valor es 0
            if ($tipo === 4 && floatval($valor) <= 0) {
                continue;
            }

            //No mostrar sindicato cuando su valor es 0
            if ($tipo === 3 && floatval($valor) <= 0) {
                continue;
            }

            $data = [
                'tipo'        => $tipo,
                'porcentaje'  => self::formatNumber($porcentaje),
                'valorBase'   => $valorBase,
                'deduccion'   => $valor,
            ];

            // Agregar descripción solo si corresponde mostrarla
            if ($tipo === 4) {
                $data['descripcion'] = $config['descripcion'];
            }

            $response[] = $data;
        }

        return $response;
    }


    private static function mapDevengados(array $nominaJson): array
    {
        $periodoInicio = Arr::get($nominaJson, 'Periodo.FechaLiquidacionInicio');
        $periodoFin    = Arr::get($nominaJson, 'Periodo.FechaLiquidacionFin');
        $response      = [];

        // Tipo 1: VacacionesCompensadas
        $vacacionesCompensadas = Arr::get($nominaJson, 'Devengados.Vacaciones.VacacionesCompensadas', []);
        $vacacionesCompensadas = is_array($vacacionesCompensadas) ? $vacacionesCompensadas : [];
        foreach ($vacacionesCompensadas as $item) {
            if (empty($item)) continue;
            $response[] = [
                'tipo'        => 1,
                'fechaInicio' => $periodoInicio,
                'fechaFin'    => $periodoFin,
                'cantidad'    => self::formatNumber(Arr::get($item, 'Cantidad', 0)),
                'pago'        => self::formatNumber(Arr::get($item, 'Pago', 0)),
            ];
        }

        // Tipo 2: VacacionesComunes
        $vacacionesComunes = Arr::get($nominaJson, 'Devengados.Vacaciones.VacacionesComunes', []);
        $vacacionesComunes = is_array($vacacionesComunes) ? $vacacionesComunes : [];
        foreach ($vacacionesComunes as $item) {
            if (empty($item)) continue;
            $response[] = [
                'tipo'        => 2,
                'fechaInicio' => Arr::get($item, 'FechaInicio', $periodoInicio),
                'fechaFin'    => Arr::get($item, 'FechaFin', $periodoFin),
                'cantidad'    => self::formatNumber(Arr::get($item, 'Cantidad', 0)),
                'pago'        => self::formatNumber(Arr::get($item, 'Pago', 0)),
            ];
        }

        // Tipos 3, 4, 5: Incapacidades (Comunes, Profesionales, Laborales)
        $incapacidades = Arr::get($nominaJson, 'Devengados.Incapacidades.Incapacidad', []);
        $incapacidades = is_array($incapacidades) ? $incapacidades : [];
        foreach ($incapacidades as $item) {
            if (empty($item)) continue;
            $tipoIncapacidad = (int) Arr::get($item, 'Tipo', 0);
            // Mapeo: 1=Común, 2=Profesional, 3=Laboral -> tipos 3, 4, 5
            $tipoMap = [1 => 3, 2 => 4, 3 => 5];
            $tipo = $tipoMap[$tipoIncapacidad] ?? 3; // Default a Común si no está definido

            $response[] = [
                'tipo'        => $tipo,
                'fechaInicio' => Arr::get($item, 'FechaInicio', $periodoInicio),
                'fechaFin'    => Arr::get($item, 'FechaFin', $periodoFin),
                'cantidad'    => self::formatNumber(Arr::get($item, 'Cantidad', 0)),
                'pago'        => self::formatNumber(Arr::get($item, 'Pago', 0)),
            ];
        }

        // Tipo 6: Licencia de Maternidad o Paternidad
        $licenciasMP = Arr::get($nominaJson, 'Devengados.Licencias.LicenciaMP', []);
        $licenciasMP = is_array($licenciasMP) ? $licenciasMP : [];
        foreach ($licenciasMP as $item) {
            if (empty($item)) continue;
            $response[] = [
                'tipo'        => 6,
                'fechaInicio' => Arr::get($item, 'FechaInicio', $periodoInicio),
                'fechaFin'    => Arr::get($item, 'FechaFin', $periodoFin),
                'cantidad'    => self::formatNumber(Arr::get($item, 'Cantidad', 0)),
                'pago'        => self::formatNumber(Arr::get($item, 'Pago', 0)),
            ];
        }

        // Tipo 7: Licencia Remunerada
        $licenciasR = Arr::get($nominaJson, 'Devengados.Licencias.LicenciaR', []);
        $licenciasR = is_array($licenciasR) ? $licenciasR : [];
        foreach ($licenciasR as $item) {
            if (empty($item)) continue;
            $response[] = [
                'tipo'        => 7,
                'fechaInicio' => Arr::get($item, 'FechaInicio', $periodoInicio),
                'fechaFin'    => Arr::get($item, 'FechaFin', $periodoFin),
                'cantidad'    => self::formatNumber(Arr::get($item, 'Cantidad', 0)),
                'pago'        => self::formatNumber(Arr::get($item, 'Pago', 0)),
            ];
        }

        // Tipo 8: Licencia No Remunerada
        $licenciasNR = Arr::get($nominaJson, 'Devengados.Licencias.LicenciaNR', []);
        $licenciasNR = is_array($licenciasNR) ? $licenciasNR : [];
        foreach ($licenciasNR as $item) {
            if (empty($item)) continue;
            $response[] = [
                'tipo'        => 8,
                'fechaInicio' => Arr::get($item, 'FechaInicio', $periodoInicio),
                'fechaFin'    => Arr::get($item, 'FechaFin', $periodoFin),
                'cantidad'    => self::formatNumber(Arr::get($item, 'Cantidad', 0)),
                'pago'        => 0, // Las licencias no remuneradas no tienen pago
            ];
        }

        return $response;
    }

    private static function mapFondoSP(array $nominaJson): array
    {
        $fondo = Arr::get($nominaJson, 'Deducciones.FondoSP', []);

        if (empty($fondo)) {
            return [];
        }

        return [[
            'porcentaje'     => self::formatNumber(Arr::get($fondo, 'Porcentaje', 0)),
            'deduccionSP'    => self::formatNumber(Arr::get($fondo, 'DeduccionSP', 0)),
            'porcentajeSub'  => self::formatNumber(Arr::get($fondo, 'PorcentajeSub', 0)),
            'deduccionSub'   => self::formatNumber(Arr::get($fondo, 'DeduccionSub', 0)),
        ]];
    }

    private static function mapHorasExtras(array $nominaJson): array
    {
        // Mapeo de tipos: clave del JSON -> tipo en el resultado
        $keys = [
            'HEDs'   => ['path' => 'Devengados.HEDs.HED',   'tipo' => 'HEDs'],
            'HENs'   => ['path' => 'Devengados.HENs.HEN',   'tipo' => 'HENs'],
            'HRNs'   => ['path' => 'Devengados.HRNs.HRN',   'tipo' => 'HRNs'],
            'HEDDFs' => ['path' => 'Devengados.HEDDFs.HEDDF','tipo' => 'HEDDFs'],
            'HRDDFs' => ['path' => 'Devengados.HRDDFs.HRDDF','tipo' => 'HRDDFs'],
            'HENDFs' => ['path' => 'Devengados.HENDFs.HENDF','tipo' => 'HENDFs'],
            'HRNDFs' => ['path' => 'Devengados.HRNDFs.HRNDF','tipo' => 'HRNDFs'],
        ];

        $response = [];

        foreach ($keys as $key => $config) {

            $items = Arr::get($nominaJson, $config['path'], []);
            $items = is_array($items) ? $items : [$items];

            foreach ($items as $item) {
                if (empty($item)) {
                    continue;
                }

                $horaInicio = Arr::get($item, 'HoraInicio');
                $horaFin    = Arr::get($item, 'HoraFin');

                // Formatear fechas si existen
                if ($horaInicio) {
                    try {
                        $horaInicio = Carbon::parse($horaInicio)->format('Y-m-d\TH:i:s');
                    } catch (\Exception $e) {
                        // Si no se puede parsear, mantener el valor original
                    }
                }

                if ($horaFin) {
                    try {
                        $horaFin = Carbon::parse($horaFin)->format('Y-m-d\TH:i:s');
                    } catch (\Exception $e) {
                        // Si no se puede parsear, mantener el valor original
                    }
                }

                $response[] = [
                    'tipo'       => $config['tipo'],
                    'horaInicio' => $horaInicio,
                    'horaFin'    => $horaFin,
                    'cantidad'   => self::formatNumber(Arr::get($item, 'Cantidad', 0)),
                    'porcentaje' => self::formatNumber(Arr::get($item, 'Porcentaje', 0)),
                    'pago'       => self::formatNumber(Arr::get($item, 'Pago', 0)),
                ];
            }
        }

        return $response;
    }

    private static function mapHuelgasLegales(array $nominaJson): array
    {
        $items = Arr::get($nominaJson, 'Devengados.HuelgasLegales.HuelgaLegal', []);
        $items = is_array($items) ? $items : [$items];

        return array_map(function ($item) {
            return [
                'fechaInicio' => Arr::get($item, 'FechaInicio'),
                'fechaFin'    => Arr::get($item, 'FechaFin'),
                'cantidad'    => self::formatNumber(Arr::get($item, 'Cantidad', 0)),
            ];
        }, array_filter($items));
    }

    private static function mapNotas(array $nominaJson): array
    {
        $notas = Arr::get($nominaJson, 'Notas', []);
        $notas = is_array($notas) ? $notas : [$notas];

        return array_map(fn ($nota) => ['nota' => $nota], array_filter($notas));
    }

    private static function mapOtrasDeducciones(array $nominaJson): array
    {
        $deducciones = Arr::get($nominaJson, 'Deducciones', []);

        $otros = [];

        $otros[] = ['deuda' => self::formatNumber(Arr::get($deducciones, 'Deuda', 0))];
        $otros[] = ['anticipo' => self::formatNumber(self::sumCollection(Arr::get($deducciones, 'Anticipos.Anticipo', [])))];
        $otros[] = ['pagoTercero' => self::formatNumber(self::sumCollection(Arr::get($deducciones, 'PagosTerceros.PagoTercero', [])))];

        $otros[] = [
            'otraDeduccion'     => self::formatNumber(self::sumCollection(Arr::get($deducciones, 'OtrasDeducciones.OtraDeduccion', []))),
            'pensionVoluntaria' => self::formatNumber(Arr::get($deducciones, 'PensionVoluntaria', 0)),
            'retencionFuente'   => self::formatNumber(Arr::get($deducciones, 'RetencionFuente', 0)),
            'ica'               => self::formatNumber(Arr::get($deducciones, 'ICA', Arr::get($deducciones, 'ica', 0))),
            'afc'               => self::formatNumber(Arr::get($deducciones, 'AFC', 0)),
            'cooperativa'       => self::formatNumber(Arr::get($deducciones, 'Cooperativa', 0)),
            'embargoFiscal'     => self::formatNumber(Arr::get($deducciones, 'EmbargoFiscal', 0)),
            'planComplementarios'=> self::formatNumber(Arr::get($deducciones, 'PlanComplementarios', 0)),
            'educacion'         => self::formatNumber(Arr::get($deducciones, 'Educacion', 0)),
            'reintegro'         => self::formatNumber(Arr::get($deducciones, 'Reintegro', 0)),
            'deuda'             => self::formatNumber(Arr::get($deducciones, 'Deuda', 0)),
        ];

        return array_filter($otros, function ($item) {
            return collect($item)->some(fn ($value) => $value !== 0 && $value !== null && $value !== '');
        });
    }

    private static function mapOtrosConceptos(array $nominaJson): array
    {
        $conceptos = Arr::get($nominaJson, 'Devengados.OtrosConceptos.OtroConcepto', []);
        $conceptos = is_array($conceptos) ? $conceptos : [$conceptos];

        return array_map(function ($item) {
            return [
                'tipo'               => Arr::get($item, 'Tipo', Arr::get($item, 'tipo', 1)),
                'descripcionConcepto'=> Arr::get($item, 'DescripcionConcepto', Arr::get($item, 'descripcionConcepto', '')),
                'conceptoS'          => self::formatNumber(Arr::get($item, 'ConceptoS', Arr::get($item, 'conceptoS', 0))),
                'conceptoNS'         => self::formatNumber(Arr::get($item, 'ConceptoNS', Arr::get($item, 'conceptoNS', 0))),
            ];
        }, array_filter($conceptos));
    }

    private static function mapOtrosDevengados(array $nominaJson): array
    {
        $devengados = Arr::get($nominaJson, 'Devengados', []);
        $otros      = [];

        $otros[] = [
            'dotacion'        => self::formatNumber(Arr::get($devengados, 'Dotacion', 0)),
            'apoyoSost'       => self::formatNumber(Arr::get($devengados, 'ApoyoSost', 0)),
            'teletrabajo'     => self::formatNumber(Arr::get($devengados, 'Teletrabajo', 0)),
            'bonifRetiro'     => self::formatNumber(Arr::get($devengados, 'BonifRetiro', 0)),
            'reintegro'       => self::formatNumber(Arr::get($devengados, 'Reintegro', 0)),
            'indemnizacion'   => self::formatNumber(Arr::get($devengados, 'Indemnizacion', 0)),
        ];

        foreach ((array) Arr::get($devengados, 'PagosTerceros.PagoTercero', []) as $item) {
            $otros[] = ['pagoTercero' => self::formatNumber($item)];
        }

        foreach ((array) Arr::get($devengados, 'Anticipos.Anticipo', []) as $item) {
            $otros[] = ['anticipo' => self::formatNumber($item)];
        }

        if (!empty($devengados['Comisiones']['Comision'] ?? [])) {
            foreach ((array) $devengados['Comisiones']['Comision'] as $comision) {
                $otros[] = ['comision' => self::formatNumber($comision)];
            }
        }

        return array_filter($otros, function ($item) {
            return collect($item)->some(fn ($value) => $value !== 0 && $value !== null && $value !== '');
        });
    }

    private static function mapPago(array $nominaJson): array
    {
        $pago = Arr::get($nominaJson, 'Pago', []);

        return [
            'forma'        => Arr::get($pago, 'Forma', ''),
            'metodo'       => Arr::get($pago, 'Metodo', ''),
            'banco'        => Arr::get($pago, 'Banco', ''),
            'tipoCuenta'   => Arr::get($pago, 'TipoCuenta', ''),
            'numeroCuenta' => Arr::get($pago, 'NumeroCuenta', ''),
        ];
    }

    private static function mapPrimas(array $nominaJson): array
    {
        $primas = Arr::get($nominaJson, 'Devengados.Primas', []);

        if (empty($primas)) {
            return [];
        }

        if (isset($primas[0])) {
            $lista = $primas;
        } else {
            $lista = [$primas];
        }

        return array_map(function ($item) {
            return [
                'cantidad' => self::formatNumber(Arr::get($item, 'Cantidad', Arr::get($item, 'cantidad', 0))),
                'pago'     => self::formatNumber(Arr::get($item, 'Pago', Arr::get($item, 'pago', 0))),
                'pagoNS'   => self::formatNumber(Arr::get($item, 'PagoNS', Arr::get($item, 'pagoNS', 0))),
            ];
        }, $lista);
    }

    private static function mapSanciones(array $nominaJson): array
    {
        $sanciones = Arr::get($nominaJson, 'Deducciones.Sanciones.Sancion', []);
        $sanciones = is_array($sanciones) ? $sanciones : [$sanciones];

        return array_map(function ($item) {
            return [
                'sancionPublic' => self::formatNumber(Arr::get($item, 'SancionPublic', 0)),
                'sancionPriv'   => self::formatNumber(Arr::get($item, 'SancionPriv', 0)),
            ];
        }, array_filter($sanciones));
    }

    private static function mapTransporte(array $nominaJson): array
    {
        $transporte = Arr::get($nominaJson, 'Devengados.Transporte', []);

        if (empty($transporte)) {
            return [];
        }

        return array_map(function ($item) {
            return [
                'auxilioTransporte'  => self::formatNumber(Arr::get($item, 'AuxilioTransporte', 0)),
                'viaticoManuAlojS'   => self::formatNumber(Arr::get($item, 'ViaticoManuAlojS', 0)),
                'viaticoManuAlojNS'  => self::formatNumber(Arr::get($item, 'ViaticoManuAlojNS', 0)),
            ];
        }, $transporte);
    }

    private static function sumCollection($items): float
    {
        $items = is_array($items) ? $items : [$items];

        return array_reduce($items, function ($carry, $item) {
            if (is_array($item)) {
                $carry += self::formatNumber(Arr::get($item, 'Deduccion', Arr::get($item, 'valor', 0)));
            } else {
                $carry += self::formatNumber($item);
            }

            return $carry;
        }, 0);
    }

    private static function formatNumber($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = str_replace([',', ' '], ['', ''], (string) $value);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    private static function formatInteger($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return 0;
    }

    private static function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $value;
    }
}


