<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBillingCycleIndices extends Migration
{
    /**
     * Índices compuestos para optimizar el rendimiento del análisis de ciclos de facturación.
     * Cada índice está diseñado para cubrir los filtros más frecuentes en BillingCycleAnalyzer.
     */
    public function up()
    {
        // ===================================================================
        // TABLA: factura
        // ===================================================================
        
        // Índice principal: contrato_id + fecha + estatus
        // Cubre: getGeneratedInvoices (JOIN directo), getUltimoHistorialFacturacion,
        //        existence checks por fecha, getHistoricalData
        $this->addIndexSafely('factura', ['contrato_id', 'fecha', 'estatus'], 'idx_factura_contrato_fecha_estatus');
        
        // Índice para filtrado por fecha + estatus (queries globales por periodo)
        // Cubre: getHistoricalData (rango global), getGeneratedInvoicesQuery
        $this->addIndexSafely('factura', ['fecha', 'estatus'], 'idx_factura_fecha_estatus');
        
        // Índice para lookups por cliente + contrato
        // Cubre: JOINs con contactos y filtros por cliente
        $this->addIndexSafely('factura', ['cliente', 'contrato_id'], 'idx_factura_cliente_contrato');

        // ===================================================================
        // TABLA: contracts
        // ===================================================================
        
        // Índice compuesto: grupo_corte + status + state
        // Cubre: getContractsExpectedToInvoice, getContratosDeshabilitadosElegibles
        $this->addIndexSafely('contracts', ['grupo_corte', 'status', 'state'], 'idx_contracts_grupo_status_state');
        
        // Índice para JOINs por nro (con facturas_contratos)
        $this->addIndexSafely('contracts', ['nro'], 'idx_contracts_nro');

        // ===================================================================
        // TABLA: facturas_contratos
        // ===================================================================
        
        // Índice para lookups por contrato_nro
        // Cubre: getGeneratedInvoices (pivot), preloadUltimasFacturas, existence checks
        $this->addIndexSafely('facturas_contratos', ['contrato_nro'], 'idx_fc_contrato_nro');
        
        // Índice para JOINs por factura_id
        $this->addIndexSafely('facturas_contratos', ['factura_id'], 'idx_fc_factura_id');

        // ===================================================================
        // TABLA: numeracion_factura
        // ===================================================================
        
        // Índice para checkNumberingHealth
        $this->addIndexSafely('numeracion_factura', ['empresa', 'preferida', 'estado', 'tipo'], 'idx_numeracion_empresa_pref_estado_tipo');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $this->dropIndexSafely('factura', 'idx_factura_contrato_fecha_estatus');
        $this->dropIndexSafely('factura', 'idx_factura_fecha_estatus');
        $this->dropIndexSafely('factura', 'idx_factura_cliente_contrato');
        $this->dropIndexSafely('contracts', 'idx_contracts_grupo_status_state');
        $this->dropIndexSafely('contracts', 'idx_contracts_nro');
        $this->dropIndexSafely('facturas_contratos', 'idx_fc_contrato_nro');
        $this->dropIndexSafely('facturas_contratos', 'idx_fc_factura_id');
        $this->dropIndexSafely('numeracion_factura', 'idx_numeracion_empresa_pref_estado_tipo');
    }

    /**
     * Agrega un índice de forma segura, verificando que no exista previamente
     */
    private function addIndexSafely($table, $columns, $indexName)
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Exception $e) {
            // El índice ya existe o la columna no existe — ignorar
        }
    }

    /**
     * Elimina un índice de forma segura
     */
    private function dropIndexSafely($table, $indexName)
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        } catch (\Exception $e) {
            // El índice no existe — ignorar
        }
    }
}
