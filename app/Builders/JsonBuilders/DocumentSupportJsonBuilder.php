<?php

namespace App\Builders\JsonBuilders;

use App\Empresa;
use App\Impuesto;
use App\Model\Gastos\FacturaProveedoresRetenciones;
use App\Model\Ingresos\FacturaRetencion;
use App\NotaRetencion;
use Carbon\Carbon;
use DB;
use InvalidArgumentException;

class DocumentSupportJsonBuilder
{

    public static function buildFromHeadDocument($factura,$resolucion, $modoBTW){

        $empresa = Empresa::Find($factura->empresa);
        $totales = $factura->total();
        $forma_pago = $factura->forma_pago();
        $plazo = intval(Carbon::parse($factura->fecha)->diffinDays($factura->vencimiento));
        $resolucion_vigencia_meses = intval(Carbon::parse($resolucion->desde)->diffInMonths($resolucion->hasta));
        $resolucion_msj = 'Resolución DIAN No. ' . $resolucion->nroresolucion . ' de ' . $resolucion->desde . ' Prefijo ' . $resolucion->prefijo . ' - Numeración ' . $resolucion->inicioverdadero . ' a la ' . $resolucion->final . ', vigencia ' . $resolucion_vigencia_meses . ' meses.';

        if(strtolower($factura->plazo()) == 'de contado'){
            $plazo = $factura->fecha_factura;
        }else{
            $plazo = $factura->fecha_factura;
        }

        $totalIva = 0;
        $totalInc = 0;
        $retenciones = FacturaProveedoresRetenciones::where('factura', $factura->id)->get();
        $totalRetenciones = 0;

        foreach($retenciones as $retencion){
            $totalRetenciones+= $retencion->valor;
        }

        foreach ($totales->imp as $key => $imp) {

            if (isset($imp->total) && $imp->tipo == 1) {
                $totalIva+= round($imp->total,2);
            } elseif (isset($imp->total) && $imp->tipo == 3 || isset($imp->total) && $imp->tipo == 4) {
                $totalInc+= round($imp->total,2);
            }
        }

        if($modoBTW == 'test'){
            $empresa->nit = '901548158';
            $resolucion->nroresolucion = '18762008997356';
        }

        $moneda = 'COP';

        return [
            'head' => [
                'company' => $empresa->nit,
                'nit' => $empresa->nit,
                'invoiceType' => '05',
                'invoiceNum' => $factura->codigo_dian,
                'legalNumber' => $factura->codigo_dian,
                'custNum' => $empresa->nit,
                'customerName' => $factura->cliente()->nombre,
                'invoiceDate' => $factura->fecha_factura,
                'dueDate' => $plazo,
                'dspDocSubTotal' => round($totales->subtotal - $totales->descuento, 2),
                'discount' => round($totales->descuento),
                'docTaxAmt' => round($totalIva,2),
                'docWHTaxAmt' => round($totalRetenciones,2),
                'dspDocInvoiceAmt' => round($totalIva,2) + round($totales->subtotal - $totales->descuento,2),
                'currencyCode' => $moneda,
                'paymentMeansID_c' => $forma_pago,
                'paymentMeansDescription' => $forma_pago == 1 ? 'Contado' : 'Crédito',
                'paymentMeansCode_c' => $forma_pago == 1 ? '10' : '1',
                'invoiceComment' => $factura->observaciones,
                'resolutionNumber' => $resolucion->nroresolucion,
                'resolution1' => $resolucion_msj,
                'resolutionDateInvoice' => $resolucion->desde,
                'paymentDueDate' => $factura->vencimiento,
            ]
        ];
    }

    public static function buildFromHeadAdjustmentNote($nota,$documento, $resolucion, $modoBTW){

        $empresa = Empresa::Find($nota->empresa);
        $totales = $nota->total();
        $forma_pago = $documento->forma_pago();
        $plazo = intval(Carbon::parse($documento->fecha)->diffinDays($documento->vencimiento));
        $resolucion_vigencia_meses = intval(Carbon::parse($resolucion->desde)->diffInMonths($resolucion->hasta));
        $resolucion_msj = 'Resolución DIAN No. ' . $resolucion->nroresolucion . ' de ' . $resolucion->desde . ' Prefijo ' . $resolucion->prefijo . ' - Numeración ' . $resolucion->inicioverdadero . ' a la ' . $resolucion->final . ', vigencia ' . $resolucion_vigencia_meses . ' meses.';

        if(strtolower($documento->plazo()) == 'de contado'){
            $plazo = $nota->fecha_factura;
        }else{
            $plazo = $nota->fecha_factura;
        }

        $totalIva = 0;
        $totalInc = 0;
        $retenciones = NotaRetencion::where('notas', $nota->id)->where('tipo',2)->get();
        $totalRetenciones = 0;

        foreach($retenciones as $retencion){
            $totalRetenciones+= $retencion->valor;
        }

        foreach ($totales->imp as $key => $imp) {

            if (isset($imp->total) && $imp->tipo == 1) {
                $totalIva+= round($imp->total,2);
            } elseif (isset($imp->total) && $imp->tipo == 3 || isset($imp->total) && $imp->tipo == 4) {
                $totalInc+= round($imp->total,2);
            }
        }

        if($modoBTW == 'test'){
            $empresa->nit = '901548158';
            $resolucion->nroresolucion = '18762008997356';
        }

        $moneda = 'COP';

        return [
            'head' => [
                'company' => $empresa->nit,
                'nit' => $empresa->nit,
                'invoiceType' => '95',
                'invoiceNum' => $nota->nro,
                'legalNumber' => $nota->nro,
                'custNum' => $empresa->nit,
                'customerName' => $nota->cliente()->nombre,
                'invoiceDate' => $nota->fecha,
                'dueDate' => $documento->vencimiento_factura,
                'dspDocSubTotal' => round($totales->subtotal - $totales->descuento, 2),
                'discount' => round($totales->descuento),
                'docTaxAmt' => round($totalIva,2),
                'docWHTaxAmt' => round($totalRetenciones,2),
                'dspDocInvoiceAmt' => round($totalIva,2) + round($totales->subtotal - $totales->descuento,2),
                'currencyCode' => $moneda,
                'paymentMeansID_c' => $forma_pago,
                'paymentMeansDescription' => $forma_pago == 1 ? 'Contado' : 'Crédito',
                'paymentMeansCode_c' => $forma_pago == 1 ? '10' : '1',
                'invoiceComment' => $nota->observaciones,
                'resolutionNumber' => $resolucion->nroresolucion,
                'resolution1' => $resolucion_msj,
                'resolutionDateInvoice' => $resolucion->desde
            ]
        ];

    }

    public static function buildFromDetails($documento, $modoBTW){

        $items = $documento->items;

        if($modoBTW == 'test'){
            $nit = '901548158';
        }else{
            $nit = Empresa::Find($documento->empresa)->nit;
        }

        $monedaCambio  = 'COP';
        $datosExportacion = null;

        if($documento->tipo == 4 && isset($documento->datosExportacion)){
            $datosExportacion = $documento->datosExportacion;
            $monedaCambio = $datosExportacion->data_moneda->codigo;
        }


        return [
            'details' => array_map(function ($item,$index) use ($nit,$documento,$monedaCambio){

                $subtotal = $item->precio * $item->cant;
                $discount = round(($item->desc / 100) * $subtotal, 2);
                $neto = $subtotal - $discount;

                //Fact. Exportacion.
                $precioExtranjero = "0.00";
                $precioExntranjeroCompleto = "0.00";
                if($documento->tipo == 4){
                    $precioExtranjero = self::convertirPrecioUSD($item->precio, $documento->trmActual);
                    $precioExntranjeroCompleto = self::convertirPrecioUSD($item->precio * $item->cant - $discount, $documento->trmActual);
                }

                return [
                    'company' => $nit,
                    'invoiceNum' => isset($documento->codigo_dian) ? $documento->codigo_dian : $documento->nro,
                    'invoiceLine' => $index + 1,
                    'partNum' => $item->ref,
                    'lineDesc' => $item->producto() ?? $item->descripcion,
                    'taxAmtLineIVA' => round(($item->impuesto / 100) * $neto, 2),
                    'sellingShipQty' => round($item->cant),
                    'salesUM' => '94',
                    'unitPrice' => round($item->precio),
                    'docUnitPrice' => $precioExtranjero,
                    'extPrice' => round($item->precio * $item->cant) - $discount,
                    'docExtPrice' => $precioExntranjeroCompleto,
                    'discountPercent' => $item->desc ?? 0,
                    'discount' => $discount,
                    'currencyCode' => $monedaCambio,
                    'idSupplier' => null,
                    'codInvima' => null,
                    'dspDocTotalMiscChrg' => 0,
                    'startDate' => $documento->fecha_factura,
                    'invoicePeriodCode' => 1
                ];
            },$items->all(), array_keys($items->all()))
        ];
    }

    public static function buildFromReferenceDocument($documento, $nota, $cliente){

        return [
            'invcRef' => [
                'company' => $cliente->nit,
                'invoiceNum' => $nota->nro,
                'docRef' => $documento->codigo_dian,
            ]
        ];

    }

    public static function convertirPrecioUSD(float $precioCOP, object $trm): float {
        if ($trm->valor_cop <= 0) {
            throw new InvalidArgumentException("El valor_cop no puede ser 0 o negativo");
        }
        return round($precioCOP / $trm->valor_cop, 2); // con 2 decimales
    }

    public static function buildFromAdditionalTags($factura, $empresa, $modoBTW)
    {
        $additionalTags = [];

        $additionalTags[] = (object)[
            'company'          => $empresa->nit,
            'invoiceNum'       => $factura->codigo_dian,
            'name'             => 'Note',
            'value'            => $factura->tipo_operacion == 3
                                    ? $factura->notaDetalleXml()
                                    : $factura->nota,
            'nodoPersonalizado'=> 'terceros',
        ];

        return $additionalTags;
    }


    public static function buildFromCompany($cliente, $empresa, $modoBTW, $operacionCodigo){

        $municipio = $empresa->municipio();
        $departamento = $empresa->departamento();

        $responsabilidades_empresa = DB::table('empresa_responsabilidad as er')
            ->join('responsabilidades_facturacion as rf', 'rf.id', '=', 'er.id_responsabilidad')
            ->select('rf.*')
            ->where('er.id_empresa', $empresa->id)
            ->get();

        $responsabilidades = "";
        $re_cont = $responsabilidades_empresa->count();
        $i = 1;
        foreach($responsabilidades_empresa as $re){
            if($re_cont == $i){
                $responsabilidades .= $re->codigo;
            }else{
                $responsabilidades .= $re->codigo . ";";
            }
            $i++;
        }

        if($modoBTW == 'test'){
            $empresa->nit = '901548158';
        }

        return [
            'company' => [
                'company' => $empresa->nit,
                'stateTaxID' => $cliente->nit,
                'name' => $empresa->nombre,
                'regimeType_c' => '05',
                'fiscalResposability_c' => $responsabilidades,
                'operationType_c' => $operacionCodigo,
                'companyType_c' => $empresa->tipo_persona == 'j' ? 1 : 2,
                'state' => $departamento->nombre,
                'stateNum' => $departamento->codigo,
                'city' => $municipio->nombre,
                'cityNum' => $municipio->codigo_completo,
                'industryClassificationCode_c'=> "",
                'identificationType'=> $empresa->tipoIdentificacion->codigo_dian,
                'address1'=> $empresa->direccion,
                'country'=> $empresa->fk_idpais,
                'postalZone_c'=> $municipio->codigo_completo,
                'phoneNum'=> $empresa->telefono,
                'email' => $empresa->email,
                'attrOperationType_c' => "",
                'faxNum' =>  '',
                'webPage' => '',
                'companyOrigin' => $empresa->fk_idpais,
                'shareholder'=> '',
                'participationPercent' => ''
            ]
        ];

    }

    public static function buildFromCustomer($cliente,$empresa, $modoBTW, $factura){

        $municipio = $cliente->municipio();
        $departamento = $cliente->departamento();

        if($modoBTW == 'test'){
            $empresa->nit = '901548158';
            $cliente->nit = '901548158';
        }

        $monedaCambio  = 'COP';
        if($factura->tipo == 4 && isset($factura->datosExportacion)){
            $monedaCambio = $factura->datosExportacion->data_moneda->codigo;
        }

        return [
            'customer' => [
                'company' => $cliente->nit,
                'resaleID' => $empresa->nit,
                'name' => $cliente->nombre,
                'identificationType' => $cliente->identificacion->codigo_dian,
                'address1' => $cliente->direccion,
                'email' => $cliente->email,
                'phoneNum' => $cliente->telefono1,
                'country' => $cliente->fk_idpais,
                'state' => $departamento->nombre,
                'stateNum' => $departamento->codigo,
                'city' => $municipio->nombre,
                'cityNum' => $municipio->codigo_completo,
                'codPostal' => $municipio->codigo_completo,
                'currencyCode' => $monedaCambio,
                'regimeType_c' => 'No aplica',
                'fiscalResposability_c' => 'R-99-PN',
                'termsDescription' => null,
                'territoryTerritoryDesc' => null,
                'contactsNumber' => null,
                'shipToCity' => null,
                'shipToEmail' => null,
                'shipToPhoneNum' => null,
                'shipToAddress' => null,
                'shipToId' => null,
                'shipToName' => null
            ]
        ];

    }

    public static function buildFromTaxes($isNota, $documento, $empresa, $modoBTW){

        $total = $documento->total();
        // dd($taxes);
        $withholdingTaxInvoice = $total->reten;

        $items = $documento->items;

        $k = 1;
        $totalImpuestos = 0;
        $rateCode = '01';
        $taxes = [];
        foreach($items as $item){

            if($item->impuesto > 0){

                $item->tipo = Impuesto::Find($item->id_impuesto)->tipo;
                $descuento = ($item->desc / 100) * ($item->precio * $item->cant);
                $totalSobre = $item->precio * $item->cant - $descuento;
                $totalImp = ($item->impuesto / 100) * $totalSobre;

                $totalImpuestos = $totalImpuestos + $totalImp;

                    if($modoBTW == 'test'){
                        $nit = '901548158';
                    }else{
                        $nit = $empresa->nit;
                    }

                    if ($item->tipo == 1) {
                        $rateCode = '01'; //IVA
                    } elseif ($item->tipo == 3 || isset($item->total) && $item->tipo == 4) {
                        $rateCode = '04'; //INC
                    }

                    $taxes[] = (object) [
                        'company' => $nit,
                        'invoiceNum' => isset($documento->codigo_dian) ? $documento->codigo_dian : $documento->nro,
                        'invoiceLine' => $k,
                        'currencyCode' => 'COP',
                        'rateCode' => $rateCode,
                        'idImpDIAN_c' => $rateCode,
                        'docTaxableAmt' => round($totalSobre,2),
                        'taxAmt' => round($totalImp,2),
                        'docTaxAmt' => round($totalImp,2),
                        'percent' => round($item->impuesto,2),
                        'withholdingTax_c' => false
                    ];
                    $k++;
            }else{
                $k++;
            }
        }

        if(!$isNota){
            foreach($withholdingTaxInvoice as $retencion){
                if(isset($retencion->total)){

                    if($modoBTW == 'test'){
                        $nit = '901548158';
                    }else{
                        $nit = $empresa->nit;
                    }

                    $sobre_quien_retiene = 0;

                    if($retencion->tipo == 1){
                        $sobre_quien_retiene = round($totalImpuestos,2);
                    }else{
                        $sobre_quien_retiene = round($total->subtotal2,2);
                    }

                    if($retencion->tipo == 1){
                        $rateCode = '05'; //ReteIva
                    }
                    else if($retencion->tipo == 2){
                        $rateCode = '06'; //ReteFuente
                    }
                    else {
                        $rateCode = 'ZZ'; //Indefinido
                    }


                    $taxes[] = (object) [
                        'company' => $nit,
                        'invoiceNum' => isset($documento->codigo_dian) ? $documento->codigo_dian : $documento->nro,
                        'invoiceLine' => 0,
                        'currencyCode' => 'COP',
                        'rateCode' => $rateCode,
                        'idImpDIAN_c' => $rateCode,
                        'docTaxableAmt' => $sobre_quien_retiene,
                        'taxAmt' => round($retencion->total,2),
                        'docTaxAmt' => round($retencion->total,2),
                        'percent' => $retencion->porcentaje,
                        'withholdingTax_c' => true,
                    ];
                }
            }
        }

        return $taxes;
    }

    public static function buildFullInvoice($data)
    {
        return [
            'head'     => $data['head']['head'] ?? [],
            'company'  => $data['company']['company'] ?? [],
            'customer' => $data['customer']['customer'] ?? [],
            'details'  => $data['details']['details'] ?? [],
            'taxes'    => $data['taxes'] ?? [],
            'invcRef'   => $data['invcRef']['invcRef'] ?? [],
            'discrepancyResponse' => $data['discrepancyResponse']['discrepancyResponse'] ?? [],
            'mode'     => $data['mode'] ?? 'no',
            'btw_login'=> $data['btw_login'] ?? '',
        ];
    }


    public static function buildFromDiscrepancyResponse($nota,$empresa){

        return [
            'discrepancyResponse' => [
                'company' => $empresa->nit,
                'invoiceNum' => $nota->nro,
                'responseCode' => $nota->tipo,
            ]
        ];
    }

}
