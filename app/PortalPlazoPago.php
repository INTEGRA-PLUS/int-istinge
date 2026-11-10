<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plazo de pago con contador fijado desde el Integra Portal
 * (master-api/plazo-pago). La tabla se auto-provisiona en el primer envío.
 */
class PortalPlazoPago
{
    /** @var array|null|false  false = aún no calculado en esta petición */
    private static $cache = false;

    /**
     * Límite del mes en curso (ISO 8601 con desfase), o null si no hay
     * contador o el mes ya está cubierto: comprobante enviado no rechazado
     * o pago confirmado desde el portal.
     */
    public static function vigente()
    {
        if (self::$cache !== false) {
            return self::$cache;
        }

        self::$cache = null;

        try {
            if (! Schema::hasTable('portal_plazo_pago')) {
                return null;
            }

            $mes = now()->startOfMonth()->toDateString();
            $limite = DB::table('portal_plazo_pago')->where('periodo', $mes)->value('limite');
            if (! $limite) {
                return null;
            }

            $cubierto =
                (Schema::hasTable('portal_comprobantes')
                    && DB::table('portal_comprobantes')->where('periodo', $mes)->where('estado', '!=', 'rechazado')->exists())
                || (Schema::hasTable('portal_meses_pagados')
                    && DB::table('portal_meses_pagados')->where('periodo', $mes)->exists());

            if (! $cubierto) {
                self::$cache = \Carbon\Carbon::parse($limite, config('app.timezone'))->toIso8601String();
            }
        } catch (\Throwable $e) {
            // Nunca romper el dashboard por el contador.
        }

        return self::$cache;
    }
}
