<?php

namespace App\Http\Controllers;

use App\Model\Gastos\ItemsFacturaProv;
use App\Retencion;
use Illuminate\Http\Request;
use App\Empresa;
use App\Contacto;
use App\Banco;
use App\Builders\JsonBuilders\DocumentSupportJsonBuilder;
use App\Categoria;
use App\EtiquetaEstado;
use App\Model\Gastos\DevolucionesDebito;
use App\Model\Gastos\NotaDebito;
use App\Model\Gastos\ItemsNotaDebito;
use App\Model\Gastos\NotaDebitoFactura;
use App\Model\Gastos\FacturaProveedores;
use App\Impuesto;
use App\Model\Inventario\Inventario;
use App\Model\Inventario\Bodega;
use App\Model\Inventario\ProductosBodega;
use App\Model\Ingresos\Ingreso;
use App\Funcion;
use App\NotaRetencion;
use Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Mail;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\NumeracionFactura;
use App\Model\Ingresos\FacturaRetencion;
use File;
use Illuminate\Support\Arr;
use ZipArchive;
use App\Campos;
use App\Services\BTWService;

class NotasDebitoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('readingMode')->only(['create', 'store', 'edit', 'update']);

        view()->share(['seccion' => 'gastos', 'title' => 'Notas Débito', 'icon' => 'fas fa-minus', 'subseccion' => 'debito']);
    }

    /**
     * Vista Principal de las notas de credito
     */
    public function index(Request $request)
    {
        $this->getAllPermissions(Auth::user()->id);

        $modoLectura = auth()->user()->modo_lectura();
        $empresaId = auth()->user()->empresa;

        $empresa = Empresa::select('id', 'nombre', 'moneda', 'form_fe', 'estado_dian', 'technicalkey', 'equivalente','proveedor')
            ->where('id', $empresaId)
            ->first();

        $perPage = $request->get('per_page', 50);

        $notas = NotaDebito::query()
            ->with([
                'proveedorObj:id,nombre',
                'facturaNotaDebito.facturaObj:id,nro,codigo,comprador',
                'facturaNotaDebito.facturaObj.compradorObj:id,nombre'
            ])
            ->where('empresa', $empresaId)
            ->whereNotIn('estatus', [9])
            ->orderBy('fecha', 'DESC')
        ->paginate($perPage);

        $camposUsuarios = DB::table('campos_usuarios')
            ->where('estado', 1)
            ->where('id_modulo', 15)
            ->where('id_usuario', auth()->user()->id)
            ->get()
            ->keyBy('id_campo')
            ->keys()
            ->all();

        if (count($camposUsuarios) > 0) {
            $tabla = Campos::where('modulo', 15)
                ->where('estado', 1)
                ->whereNotIn('id', $camposUsuarios)
                ->orderBy('orden', 'ASC')
                ->paginate(50);
        } else {
            $tabla = collect([]);
        }

        return view('notasdebito.index', compact('notas', 'empresa', 'modoLectura', 'tabla'));
    }

    /**
     * Formulario para crear un nueva nota de debito
     * @return view
     */
    public function create()
    {

        $user = auth()->user();

        $this->getAllPermissions($user->id);

        $modoLectura = $user->modo_lectura();

        $empresa = Empresa::select('id', 'nombre', 'moneda')
            ->where('id', $user->empresa)
            ->first();

        $bodega = Bodega::where('empresa', $empresa->id)->where('status', 1)->first();

        $inventario = collect([]);

        $bodegas = Bodega::where('empresa', $empresa->id)->where('status', 1)->get();

        $categorias = Categoria::where('empresa', $empresa->id)->where('estatus', 1)->whereNull('asociado')->get();

        $bancos = Banco::where('empresa', $empresa->id)->where('estatus', 1)->get();

        $impuestos = Impuesto::where('estado', 1)
            ->where(function ($query) use ($empresa) {
                $query->where('empresa', $empresa->id)
                    ->orWhere('empresa', null);
            })
            ->get();

        $tipos = DB::table('tipos_nota_credito')->get();

        $proveedores = Contacto::select('id', 'nombre', 'nit')
            ->where('empresa', $empresa->id)
            ->whereIn('tipo_contacto', [1, 2])
            ->get();

        $retenciones = Retencion::where('empresa', $empresa->id)->get();

        $notas = NotaDebito::where('empresa', $empresa->id)->latest()->first();

        if ($notas == null) {
            $numero = 1;
        } else {
            $numero = $notas->nro + 1;
        }

        view()->share(['icon' => '', 'title' => "Nueva Nota Débito #{$numero}", 'subseccion' => 'debito']);

        $dataPro = (new InventarioController())->create();

        $categorias2 = $dataPro->categorias;
        $unidades2 = $dataPro->unidades;
        $medidas2 = $dataPro->medidas;
        $impuestos2 = $dataPro->impuestos;
        $extras2 = $dataPro->extras;
        $listas = $dataPro->listas;
        $bodegas2 = $dataPro->bodegas;
        $identificaciones2 = $dataPro->identificaciones;
        $tipos_empresa2 = $dataPro->tipos_empresa;
        $prefijos2 = $dataPro->prefijos;
        $vendedores2 = $dataPro->vendedores;
        $extras2 = $dataPro->extras;
        $extras = \App\CamposExtra::where('empresa', $empresa->id)->where('status', 1)->get();




        return view('notasdebito.create', compact('proveedores','tipos','inventario', 'impuestos', 'bancos', 'bodegas', 'categorias', 'retenciones', 'numero', 'empresa',   'listas',
        'categorias2',
        'unidades2',
        'medidas2',
        'impuestos2',
        'extras',
        'extras2',
        'listas',
        'bodegas2',
        'identificaciones2',
        'tipos_empresa2',
        'prefijos2',
        'vendedores2'));
    }

    /**
     * Registrar una nueva nota de credito
     * Si hay items inventariable sumar los valores al inventario
     * @param Request $request
     * @return redirect
     */
    public function store(Request $request)
    {
        $factura = FacturaProveedores::find($request->factura);

/*         $NotaDebitoFactura = NotaDebitoFactura::where('factura', $factura->id)->get();

        if(!$NotaDebitoFactura->isEmpty()){
            if($NotaDebitoFactura->count() > 1)
            {
                $NotaDebitoFactura[1]->delete();
            }
            return back()->with('danger', 'ERROR: La factura de proveedor ya tiene una nota de débito asociada.');
        } */

        $empresa = auth()->user()->empresa;

        if ($request->total_value > Funcion::precision($factura->total()->total)) {
            return back()
                ->with('danger', 'ERROR: tiene que hacer una Nota Débito igual o menor al valor de la Factura de Proveedor.');
        }

        $ultimaNotaDeb = NotaDebito::where('proveedor', $request->proveedor)->where('empresa', auth()->user()->empresa)->latest()->first();

        if ($ultimaNotaDeb) {
            $minutes = $ultimaNotaDeb->created_at->diffInMinutes(now());

            if ($minutes < 1) {
                return back()->with('error', 'La Nota débito para el mismo cliente fue creada hace menos de un minuto.');
            }
        }

        $notaOld = NotaDebito::where('empresa', $empresa)->get()->last();

        try {

            DB::beginTransaction();

            if ($notaOld == null) {
                $notaOld = 0;
            } else {
                $notaOld = $notaOld->nro;
            }
            $notaOld += 1;
            $notac = new NotaDebito();
            $notac->nro = $notaOld;
            $notac->empresa = $empresa;
            $notac->proveedor = $request->proveedor;
            $notac->codigo = $request->factura;
            $notac->fecha = Carbon::parse($request->fecha)->format('Y-m-d');
            $notac->observaciones = mb_strtolower($request->observaciones);
            $notac->bodega = $request->bodega;
            $notac->tipo = $request->tipo;
            $notac->save();

            self::saveTrazabilidad($notac->id, 'NOTA DE DEBITO', self::saveTrazabilidad($factura->id, 'FACTURA DE COMPRA'));

            $bodega = Bodega::where('empresa', $empresa)->where('status', 1)->where('id', $request->bodega)->first();
            if (!$bodega) { //Si el valor seleccionado para bodega no existe, tomara la primera activa registrada
                $bodega = Bodega::where('empresa', $empresa)->where('status', 1)->first();
            }
            $montoFactura = 0;
            $montoRetenciones = 0;
            $impuesto = 0;
            $impuestoTmp = 0;
            $opt = 0;
            $z = 1;

            //Recorremos los items de la nota débito.
            foreach ($request->item as $i => $valor) {
                //$impuesto = Impuesto::where('id', $request->impuesto[$i])->first();
                $items = new ItemsNotaDebito();
                $items->nota = $notac->id;

                if ($request->type[$i] == 'inv') {
                    $producto = Inventario::where('id', $request->item[$i])->first();
                    $items->producto = $producto->id;
                    $items->tipo_item = 1;
                    //Si el producto es inventariable y existe esa bodega, agregara el valor registrado
                    $ajuste = ProductosBodega::where('empresa', $empresa)->where('bodega', $bodega->id)
                        ->where('producto', $producto->id)
                        ->first();
                    if ($ajuste) {
                        $ajuste->nro -= $request->cant[$i];
                        $ajuste->save();
                    }
                } else {
                    $item = $request->item[$i];
                    $categorias = Categoria::where('empresa', $empresa)->where('id', $item)->first();
                    $items->producto = $categorias->id;
                    $items->tipo_item = 2;
                }

                //MULTI IMPUESTOS
                $data  = $request->all();
                if (isset($data['impuesto' . $z])) {
                    if ($data['impuesto' . $z]) {
                        for ($x = 0; $x < count($data['impuesto' . $z]); $x++) {
                            $id_cat = 'id_impuesto_' . $x;
                            $cat = 'impuesto_' . $x;
                            $impuesto = Impuesto::where('id', $data['impuesto' . $z][$x])->first();
                            if ($impuesto) {
                                if ($x == 0) {
                                    $items->id_impuesto = $impuesto->id;
                                    $items->impuesto = $impuesto->porcentaje;
                                } elseif ($x > 0) {
                                    $items->$id_cat = $impuesto->id;
                                    $items->$cat = $impuesto->porcentaje;
                                }
                            }
                        }
                    }
                }
                //MULTI IMPUESTOS
                $items->producto = $request->item[$i];
                $items->ref = isset($request->ref[$i]) ? $request->ref[$i] : '';
                $items->precio = $this->precision($request->precio[$i]);
                $items->descripcion = $request->descripcion[$i];
                $items->cant = $request->cant[$i];
                $items->desc = $request->desc[$i];
                $items->save();
                $z++;
            }

            if ($request->retencion) {
                foreach ($request->retencion as $key => $value) {
                    if ($request->precio_reten[$key]) {
                        $retencion = Retencion::where('id', $request->retencion[$key])->first();
                        $reten = new NotaRetencion();
                        $reten->notas = $notac->id;
                        $reten->tipo = 2; //hace referencia a devoluciones tipo debito.
                        $reten->valor = $this->precision($request->precio_reten[$key]);
                        $reten->retencion = $retencion->porcentaje;
                        $reten->id_retencion = $retencion->id;
                        $reten->save();
                    }
                    $montoRetenciones += $request->precio_reten[$key];
                }
                $montoFactura = $this->precision($montoFactura) - $this->precision($montoRetenciones);
            }

            if ($request->factura) {
                $z = 1;

                //MULTI IMPUESTOS
                for ($i = 0; $i < count($request->precio); $i++) {
                    $montoFactura += ($request->cant[$i] * $request->precio[$i]);

                    if ($request->desc[$i] != null) {
                        $descuento = ($request->precio[$i] * $request->cant[$i]) * $request->desc[$i] / 100;
                        $montoFactura -= $descuento;
                    }

                    $data  = $request->all();

                    if (isset($data['impuesto' . $z])) {
                        if ($data['impuesto' . $z]) {
                            for ($x = 0; $x < count($data['impuesto' . $z]); $x++) {
                                $porcentaje = Impuesto::find($data['impuesto' . $z][$x])->porcentaje;
                                if ($porcentaje > 0) {
                                    $impuestoTmp += (($request->cant[$i] * $request->precio[$i] - $descuento) * $porcentaje) / 100;
                                }
                            }
                        }
                    }
                    $z++;
                }
                //MULTI IMPUESTOS
                $montoFactura += $impuestoTmp;

                $factura = FacturaProveedores::find($request->factura);
                $itemsF = new NotaDebitoFactura();
                $itemsF->nota = $notac->id;
                $itemsF->factura = $factura->id;
                if ($factura->pagado()) {
                    $total_fact = $factura->total()->total;
                    if ($total_fact == $montoFactura) {
                        $itemsF->pago = abs($this->precision($factura->pagado()));
                    } elseif (($montoFactura + $factura->pagado()) > $total_fact) {
                        //$itemsF->pago = abs($this->precision($total_fact - $factura->pagado() - $montoFactura));
                        $itemsF->pago = $this->precision($montoFactura);
                    } elseif (($montoFactura + $factura->pagado()) < $total_fact || ($montoFactura + $factura->pagado()) == $total_fact) {
                        $itemsF->pago = 0;
                    }
                } else {
                    $itemsF->pago = 0;
                }

                $montoFinal = $itemsF->pago;
                $itemsF->save();

                /*if ($this->precision($factura->porpagar())<=0) {
                $factura->estatus=0;
                $factura->save();
            }*/
            }

            $total_fact = $factura->total()->total;

            /*ESTATUS ABIERTA*/
            if ($factura->estatus == 1) {
                if ($total_fact == $montoFactura) {
                    if ($itemsF->pago > 0) {
                        $factura->estatus = 4; //Cerrada con Devolución
                        $bancos = Banco::where('empresa', $empresa)->where('nombre', 'SALDOS A FAVOR NOTAS DÉBITO')->first();
                        $nota = NotaDebito::find($notac->id);
                        $this->up_transaccion(5, $nota->id, $bancos->id, $nota->proveedor, 1, $montoFinal, $nota->fecha, $nota->observaciones);
                    } else {
                        $factura->estatus = 3; //Cerrada por Devolución
                    }
                }
                if ($total_fact > $montoFactura) {
                    if ($montoFactura + $factura->pagado() >= $total_fact) {
                        $factura->estatus = 4; //Cerrada con Devolución
                        $bancos = Banco::where('empresa', $empresa)->where('nombre', 'SALDOS A FAVOR NOTAS DÉBITO')->first();
                        $nota = NotaDebito::find($notac->id);
                        $this->up_transaccion(5, $nota->id, $bancos->id, $nota->proveedor, 1, $montoFinal, $nota->fecha, $nota->observaciones);
                    } else {
                        $factura->estatus = 5; //Abierta con Devolución
                    }
                }
            }

            /*ESTATUS CERRADA*/
            if ($factura->estatus == 0) {
                if ($total_fact == $montoFactura) {
                    if ($items->pago > 0) {
                        $factura->estatus = 4; //Cerrada con Devolución
                        $bancos = Banco::where('empresa', $empresa)->where('nombre', 'SALDOS A FAVOR NOTAS DÉBITO')->first();
                        $nota = NotaDebito::find($notac->id);
                        $this->up_transaccion(5, $nota->id, $bancos->id, $nota->proveedor, 1, $montoFinal, $nota->fecha, $nota->observaciones);
                    } else {
                        $factura->estatus = 3; //Cerrada por Devolución
                    }
                }
                if ($total_fact > $montoFactura) {
                    if ($itemsF->pago > 0) {
                        $factura->estatus = 4; //Cerrada con Devolución
                        $bancos = Banco::where('empresa', $empresa)->where('nombre', 'SALDOS A FAVOR NOTAS DÉBITO')->first();
                        $nota = NotaDebito::find($notac->id);
                        $this->up_transaccion(5, $nota->id, $bancos->id, $nota->proveedor, 1, $montoFinal, $nota->fecha, $nota->observaciones);
                    } else {
                        $factura->estatus = 5; //Abierta con Devolución
                    }
                }
            }

            $factura->save();

            DB::commit();

            $mensaje = 'Se ha creado satisfactoriamente la nota de débito';
            return redirect('empresa/notasdebito')->with('success', $mensaje)->with('nota_id', $notac->id);
        } catch (\Throwable $th) {
            \Log::error($th);
            DB::rollBack();
            return back()->with('error', 'Error en la línea '.$th->getLine().': '.$th->getMessage());
        }
    }

    public function show($id)
    {
        $this->getAllPermissions(Auth::user()->id);

        $user = auth()->user();

        $empresa = Empresa::select('id', 'nombre', 'moneda')
            ->where('id', $user->empresa)
            ->first();

        $nota = NotaDebito::where('empresa', $empresa->id)->where('id', $id)->first();

        if (!$nota) {
            return back()->with('error', 'No se ha encontrado la nota debito');
        }

        $retencionesNotas = NotaRetencion::join('retenciones as r', 'r.id', '=', 'notas_retenciones.id_retencion')
            ->where('notas', $nota->id)->where('r.empresa', $empresa->id)
            ->select('notas_retenciones.*')
            ->get();

        view()->share(['title' => 'Nota Débito #' . $nota->nro, 'invert' => true, 'icon' => '']);

        $items = ItemsNotaDebito::where('nota', $nota->id)->get();
        $facturas = NotaDebitoFactura::where('nota', $nota->id)->get();
        $DevolucionesDebito = DevolucionesDebito::where('nota', $nota->id)->get();

        return view('notasdebito.show', compact('nota', 'retencionesNotas', 'items', 'facturas', 'DevolucionesDebito', 'empresa'));
    }

    public function Imprimir($id)
    {
        /**
         * toma en cuenta que para ver los mismos
         * datos debemos hacer la misma consulta
         **/
        view()->share(['title' => 'Imprimir Nota de Debito']);

        $empresa = auth()->user()->empresaObj;


        $nota = NotaDebito::where('empresa', $empresa->id)->where('id', $id)->first();
        if ($nota) {
            $codqr = false;
            $CUDSvr = false;
            $items = ItemsNotaDebito::where('nota', $nota->id)->get();
            $itemscount = ItemsNotaDebito::where('nota', $nota->id)->count();
            $facturas = NotaDebitoFactura::where('nota', $nota->id)->get();
            /*$retenciones = FacturaRetencion::join('notas_factura as nf','nf.factura','=','factura_retenciones.factura')
                  ->join('retenciones','retenciones.id','=','factura_retenciones.id_retencion')
                  ->where('nf.nota',$nota->id)->get();*/

            $retenciones = NotaRetencion::select('notas_retenciones.*', 'retenciones.tipo as id_tipo')
                ->join('retenciones', 'retenciones.id', '=', 'notas_retenciones.id_retencion')
                ->where('notas', $nota->id)
                ->where('retenciones.empresa', $empresa->id)
                ->get();



            if($nota->emitida == 1){
                $infoEmpresa = Empresa::find($empresa->id);
                $data['Empresa'] = $infoEmpresa->toArray();

                $infoCliente = Contacto::find($nota->proveedor);
                $data['Cliente'] = $infoCliente->toArray();

                $impTotal = 0;

                foreach ($nota->total()->imp as $totalImp) {
                    if (isset($totalImp->total)) {
                        $impTotal = $totalImp->total;
                    }
                }

                $decimal = explode(".", $impTotal);
                if (isset($decimal[1])) {
                    $impTotal = round($impTotal, 2);
                }

                $items = ItemsNotaDebito::where('nota', $id)->get();

                if ($nota->tiempo_creacion) {
                    $horaFac = $nota->tiempo_creacion;
                } else {
                    $horaFac = $nota->created_at;
                }

                $totalIva = 0.00;
                $totalInc = 0.00;

                $CUDSvr  = $nota->info_cude($impTotal);
                $codqr = $nota->info_qr($impTotal);
            }

            $pdf = PDF::loadView('pdf.debito', compact('nota', 'items', 'facturas', 'itemscount', 'retenciones', 'empresa','codqr', 'CUDSvr'));
            return  response($pdf->stream())->withHeaders(['Content-Type' => 'application/pdf',]);
        }
    }

    /**
     * Formulario para modificar los datos de una  nota de debito
     * @param int $id
     * @return view
     */
    public function edit($id)
    {
        $this->getAllPermissions(Auth::user()->id);

        $user = auth()->user();

        $empresa = Empresa::select('id', 'nombre', 'moneda')
            ->where('id', $user->empresa)
            ->first();

        $nota = NotaDebito::where('empresa', $empresa->id)->where('id', $id)->first();

        $tipos = DB::table('tipos_nota_credito')->get();

        if (!$nota) {
            return back()->with('error', 'No se ha encontrado el documento');
        }

        view()->share(['title' => 'Modificar Nota Débito #' . $nota->nro, 'icon' => '']);

        $facturas = NotaDebitoFactura::where('nota', $nota->id)->get();

        $retencionesNotas = NotaRetencion::join('retenciones as r', 'r.id', '=', 'notas_retenciones.id_retencion')
            ->where('notas', $nota->id)->where('r.empresa', $empresa->id)
            ->select('notas_retenciones.*')
            ->get();

        $factura = array();
        foreach ($facturas as $key => $value) {
            $factura[] = $value->factura;
        }
        $facturas = FacturaProveedores::where('empresa', $empresa->id)->where('tipo', 1);
        $facturas = $facturas->where(function ($query) use ($factura) {
            $query->where('estatus', 1)
                ->orWhereIn('id', $factura);
        });

        $facturas = $facturas->where('proveedor', $nota->proveedor)
            ->OrderBy('id', 'desc')
            ->select(DB::raw('if(codigo, codigo, (CONCAT("Factura ", DATE_FORMAT(fecha_factura, "%e-%m-%Y")) ) ) as codigo'), 'id')
            ->get();

        $categorias = Categoria::where('empresa', $empresa->id)->where('estatus', 1)->whereNull('asociado')->get();

        $proveedores = Contacto::select('id', 'nombre', 'nit', 'empresa')
            ->where('empresa', $empresa->id)
            ->whereIn('tipo_contacto', [1, 2])
            ->get();

        $items = ItemsNotaDebito::where('nota', $nota->id)->get();


        $facturas_reg = NotaDebitoFactura::where('nota', $nota->id)->get();
        $bodega = Bodega::where('empresa', $empresa->id)->where('id', $nota->bodega)->first();

        $retenciones = Retencion::where('empresa', $empresa->id)->get();

        $inventario = collect([]);


        $bodegas = Bodega::where('empresa', $empresa->id)->where('status', 1)->get();
        $bancos = Banco::where('empresa', $empresa->id)->where('estatus', 1)->get();

        $impuestos = Impuesto::where('estado', 1)
            ->where(function ($query) use ($empresa) {
                $query->where('empresa', $empresa->id)
                    ->orWhere('empresa', null);
            })
            ->get();

        $DevolucionesDebito = DevolucionesDebito::where('nota', $nota->id)->get();

        $cod_factura =  isset($facturas_reg[0]) ?  $facturas_reg[0]->factura : null;


        $impuestosC = $impuestos;

        return view('notasdebito.edit', compact(
            'impuestosC',
            'empresa',
            'nota',
            'retenciones',
            'retencionesNotas',
            'items',
            'facturas',
            'inventario',
            'impuestos',
            'bancos',
            'bodegas',
            'DevolucionesDebito',
            'categorias',
            'proveedores',
            'facturas_reg',
            'cod_factura',
            'tipos'
        ));
    }

    /**
     * Modificar los datos de la nota de debito
     * @param Request $request
     * @return redirect
     */
    public function update($id, Request $request)
    {
        $opt = 0;
        $descuento = 0;
        $impuestoTmp = 0;
        $factura = FacturaProveedores::find($request->factura);


        if(!$factura){
            $mensaje = 'ERROR: La factura de proveedor no existe.';
            return back()->with('danger', $mensaje);
        }

        if ($request->total_value > Funcion::precision($factura->total()->total)) {
            $mensaje = 'ERROR: Ud. tiene que hacer una Nota Débito igual o menor al valor de la Factura de Proveedor.';
            return back()->with('danger', $mensaje);
        }

        $nota = NotaDebito::where('empresa', Auth::user()->empresa)->where('id', $id)->first();
        /* NotaDebitoFactura::where('nota', $nota->id)->delete(); */
        $montoFactura = 0;
        $montoRetenciones = 0;
        if ($nota) {
            //Recoloco los items los productos en la bodega
            $items = ItemsNotaDebito::join('inventario as inv', 'inv.id', '=', 'items_notas_debito.producto')->select('items_notas_debito.*')->where('items_notas_debito.nota', $nota->id)->where('inv.tipo_producto', 1)->get();
            foreach ($items as $item) {
                $ajuste = ProductosBodega::where('empresa', Auth::user()->empresa)->where('bodega', $request->bodega)->where('producto', $item->producto)->first();

                if ($ajuste) {
                    $ajuste->nro += $item->cant;
                    $ajuste->save();
                }
            }

            //Coloco el estatus de la factura en abierta
            $facturas_reg = NotaDebitoFactura::where('nota', $nota->id)->get();
            foreach ($facturas_reg as $factura) {
                /* $dato = $factura->factura();
                //$dato->estatus=1;
                $dato->save(); */

                $factura->pago = $request->total_value;
                $factura->save();
            }

            //Modifico los datos de la nota
            $nota->empresa = Auth::user()->empresa;
            $nota->proveedor = $request->proveedor;
            //$nota->codigo=$request->codigo;
            $nota->fecha = Carbon::parse($request->fecha)->format('Y-m-d');
            $nota->observaciones = mb_strtolower($request->observaciones);
            $nota->bodega = $request->bodega;
            $nota->tipo = $request->tipo;
            $nota->save();

            //Compruebo que existe la bodega y la uso
            $bodega = Bodega::where('empresa', Auth::user()->empresa)->where('status', 1)->where('id', $request->bodega)->first();
            if (!$bodega) { //Si el valor seleccionado para bodega no existe, tomara la primera activa registrada
                $bodega = Bodega::where('empresa', Auth::user()->empresa)->where('status', 1)->first();
            }

            $inner = array();
            //Recorro los Categoría/Ítem
            $z = 1;
            for ($i = 0; $i < count($request->item); $i++) {
                if(isset($request->impuesto[$i])){
                    $impuesto = Impuesto::where('id', $request->impuesto[$i])->first() ?? '';
                }else{
                    $impuesto = '';
                }
                $cat = 'id_item' . ($i + 1);
                $items = array();
                if ($request->$cat) { //Comprobar que exixte ese id
                    $items = ItemsNotaDebito::where('id', $request->$cat)->first();
                }

                if (!$items) {
                    $items = new ItemsNotaDebito();
                    $items->nota = $nota->id;
                }

                //Comprobar que el nro que se guarda el item
                //si es numerico es producto
                //si no es categoria ya que llega con el prefijo cat_
                if (is_numeric($request->item[$i])) {
                    $producto = Inventario::where('id', $request->item[$i])->first();
                    if (!$producto) {
                        continue;
                    }
                    $items->producto = $producto->id;
                    $items->tipo_item = 1;
                    if ($producto->tipo_producto == 1) {
                        //Si el producto es inventariable y existe esa bodega, agregara el valor registrado
                        $ajuste = ProductosBodega::where('empresa', Auth::user()->empresa)->where('bodega', $bodega->id)->where('producto', $producto->id)->first();
                        if ($ajuste) {
                            $ajuste->nro -= $request->cant[$i];
                            $ajuste->save();
                        }
                    }
                } else {
                    $item = explode('_', $request->item[$i])[1];
                    $categorias = Categoria::where('empresa', Auth::user()->empresa)->where('id', $item)->first();
                    if (!$categorias) {
                        continue;
                    }
                    $items->producto = $categorias->id;
                    $items->tipo_item = 2;
                }

                /*INICIO DE CALCULOS PARA SACAR EL SALDO A FAVOR AL CONTACTO*/
                /*if($request->descuento[$i]){
                    $descuento = ($request->precio[$i] * $request->cant[$i]) * $request->descuento[$i] / 100;
                    $precioItem = ($request->precio[$i] * $request->cant[$i]) - $descuento;

                    $impuestoItem = ($precioItem * $impuesto->porcentaje) / 100;
                    $tmp = $precioItem + $impuestoItem;

                    $montoFactura += $tmp;

                }else{
                    $precioItem = $request->precio[$i] * $request->cant[$i];
                    $impuestoItem = ($precioItem * $impuesto->porcentaje) / 100;
                    $montoFactura += $precioItem + $impuestoItem;
                }*/

                //MULTI IMPUESTOS
                $items->id_impuesto = null;
                $items->impuesto = null;
                $items->id_impuesto_1 = null;
                $items->impuesto_1 = null;
                $items->id_impuesto_2 = null;
                $items->impuesto_2 = null;
                $items->id_impuesto_3 = null;
                $items->impuesto_3 = null;
                $items->id_impuesto_4 = null;
                $items->impuesto_4 = null;
                $items->id_impuesto_5 = null;
                $items->impuesto_5 = null;
                $items->id_impuesto_6 = null;
                $items->impuesto_6 = null;
                $items->id_impuesto_7 = null;
                $items->impuesto_7 = null;

                $data  = $request->all();

                if (isset($data['impuesto' . $z])) {
                    if ($data['impuesto' . $z]) {
                        for ($x = 0; $x < count($data['impuesto' . $z]); $x++) {
                            $id_cat = 'id_impuesto_' . $x;
                            $cat = 'impuesto_' . $x;
                            $impuesto = Impuesto::where('id', $data['impuesto' . $z][$x])->first();
                            if ($impuesto) {
                                if ($x == 0) {
                                    $items->id_impuesto = $impuesto->id;
                                    $items->impuesto = $impuesto->porcentaje;
                                } elseif ($x > 0) {
                                    $items->$id_cat = $impuesto->id;
                                    $items->$cat = $impuesto->porcentaje;
                                }
                            }
                        }
                    }
                }
                //MULTI IMPUESTOS

                $items->precio = $this->precision($request->precio[$i]);
                $items->descripcion = $request->descripcion[$i];
                $items->cant = $request->cant[$i];
                $items->desc = isset($request->desc[$i]) ? $request->desc[$i] : null;
                $items->save();
                $inner[] = $items->id;
                $z++;
            }
            if ($request->retencion) {
                foreach ($request->retencion as $key => $value) {
                    if ($request->precio_reten[$key]) {
                        $retencion = Retencion::where('id', $request->retencion[$key])->first();
                        NotaRetencion::where('id_retencion', $retencion->id)->where('retencion', $retencion->porcentaje)->where('notas', $id)->delete();
                        $reten = new NotaRetencion();
                        $reten->notas = $nota->id;
                        $reten->tipo = 2; //hace referencia a devoluciones tipo debito.
                        $reten->valor = $this->precision($request->precio_reten[$key]);
                        $reten->retencion = $retencion->porcentaje;
                        $reten->id_retencion = $retencion->id;
                        $reten->save();
                    }
                    $montoRetenciones += $request->precio_reten[$key];
                }
                $montoFactura = $this->precision($montoFactura) - $this->precision($montoRetenciones);
            } else {
                NotaRetencion::where('notas', $id)->delete();
            }

            if (count($inner) > 0) {
                ItemsNotaDebito::where('nota', $nota->id)->whereNotIn('id', $inner)->delete();
            }

            //Pregunto si hay facturas asociadas
            if ($request->factura) {
                $z = 1;

                //MULTI IMPUESTOS
                for ($i = 0; $i < count($request->precio); $i++) {
                    $montoFactura += ($request->cant[$i] * $request->precio[$i]);

                    if (isset($request->descuento) && $request->descuento[$i] != null) {
                        $descuento = ($request->precio[$i] * $request->cant[$i]) * $request->descuento[$i] / 100;
                        $montoFactura -= $descuento;
                    }

                    $data  = $request->all();

                    if (isset($data['impuesto' . $z])) {
                        if ($data['impuesto' . $z]) {
                            for ($x = 0; $x < count($data['impuesto' . $z]); $x++) {
                                $porcentaje = Impuesto::find($data['impuesto' . $z][$x])->porcentaje;
                                if ($porcentaje > 0) {
                                    $impuestoTmp += (($request->cant[$i] * $request->precio[$i] - $descuento) * $porcentaje) / 100;
                                }
                            }
                        }
                    }
                    $z++;
                }
                //MULTI IMPUESTOS

                $montoFactura += $impuestoTmp;

                $factura = FacturaProveedores::find($request->factura);
                $NotaDebitoFactura = NotaDebitoFactura::where('nota', $nota->id)->get();
                $montoFinal = 0;
                if($NotaDebitoFactura->count() > 1 )
                {
                    $items = new NotaDebitoFactura();
                    $items->nota = $nota->id;
                    $items->factura = $factura->id;
                    //$items->pago =$this->precision($montoFactura) ;
                    if ($factura->pagado()) {
                        $total_fact = $factura->total()->total;
                        //($total_fact == $montoFactura) ? $items->pago = $factura->pagado() : $items->pago = $factura->pagado() - $montoFactura;
                        //(($montoFactura + $factura->pagado()) == $total_fact) ? $items->pago = $factura->pagado() : $items->pago = 0;
                        if ($total_fact == $montoFactura) {
                            $items->pago = abs($this->precision($factura->pagado()));
                        } elseif (($montoFactura + $factura->pagado()) > $total_fact) {
                            $items->pago = abs($this->precision($total_fact - $factura->pagado() - $montoFactura));
                        } elseif (($montoFactura + $factura->pagado()) < $total_fact) {
                            $items->pago = 0;
                        }
                    } else {
                        $items->pago = 0;
                    }
                    $montoFinal = $items->pago;
                    $items->save();
                }else{
                    $factura = FacturaProveedores::find($request->factura);
                    $NotaDebitoFactura = NotaDebitoFactura::where('nota', $nota->id)->first();

                    if ($factura->pagado() && $NotaDebitoFactura->pago ==0) {
                        $NotaDebitoFactura->pago = $nota->total()->total;
                        $NotaDebitoFactura->save();
                    }
                }
                if ($this->precision($factura->porpagar()) <= 0) {
                    //$factura->estatus=0;
                    $factura->save();
                }
            }

            $total_fact = $factura->total()->total;

            /*ESTATUS CERRADA POR DEVOLUCIÓN*/
            if ($factura->estatus == 3) {
                if ($total_fact == $montoFactura) {
                    $factura->estatus = 3; //Cerrada por Devolución
                }
                if ($total_fact > $montoFactura) {
                    $factura->estatus = 5; //Abierta con Devolución
                }
            } elseif /*ESTATUS CERRADA CON DEVOLUCIÓN*/ ($factura->estatus == 4) {
                if ($total_fact == $montoFactura) {
                    if ($factura->pagado() > 0) {
                        $factura->estatus = 4; //Cerrada con Devolución
                        $bancos = Banco::where('empresa', Auth::user()->empresa)->where('nombre', 'SALDOS A FAVOR NOTAS DÉBITO')->first();
                        $nota = NotaDebito::find($id);
                        $this->up_transaccion(5, $nota->id, $bancos->id, $nota->proveedor, 1, $montoFinal, $nota->fecha, $nota->observaciones);
                    } else {
                        $factura->estatus = 3; //Cerrada por Devolución
                    }
                }
                if ($total_fact > $montoFactura) {
                    if ($factura->pagado() > 0) {
                        if ($this->precision($factura->pagado() + $montoFactura) >= $total_fact) {
                            $factura->estatus = 4; //Cerrada con Devolución
                            $bancos = Banco::where('empresa', Auth::user()->empresa)->where('nombre', 'SALDOS A FAVOR NOTAS DÉBITO')->first();
                            $nota = NotaDebito::find($id);
                            $this->up_transaccion(5, $nota->id, $bancos->id, $nota->proveedor, 1, $montoFinal, $nota->fecha, $nota->observaciones);
                        } else {
                            $factura->estatus = 5; //Abierta con Devolución
                        }
                    } else {
                        $factura->estatus = 5; //Abierta con Devolución
                    }
                }
            } elseif /*ESTATUS ABIERTA CON DEVOLUCIÓN*/ ($factura->estatus == 5) {
                if ($total_fact == $montoFactura) {
                    if ($factura->pagado() > 0) {
                        if ($this->precision($factura->pagado() + $montoFactura) >= $total_fact) {
                            $factura->estatus = 4; //Cerrada con Devolución
                            $bancos = Banco::where('empresa', Auth::user()->empresa)->where('nombre', 'SALDOS A FAVOR NOTAS DÉBITO')->first();
                            $nota = NotaDebito::find($id);
                            $this->up_transaccion(5, $nota->id, $bancos->id, $nota->proveedor, 1, $montoFinal, $nota->fecha, $nota->observaciones);
                        } else {
                            $factura->estatus = 5; //Abierta con Devolución
                        }
                    } else {
                        $factura->estatus = 3; //Cerrada por Devolución
                    }
                }
                if ($total_fact > $montoFactura) {
                    if ($factura->pagado() > 0) {
                        if ($this->precision($factura->pagado() + $montoFactura) >= $total_fact) {
                            $factura->estatus = 4; //Cerrada con Devolución
                            $bancos = Banco::where('empresa', Auth::user()->empresa)->where('nombre', 'SALDOS A FAVOR NOTAS DÉBITO')->first();
                            $nota = NotaDebito::find($id);
                            $this->up_transaccion(5, $nota->id, $bancos->id, $nota->proveedor, 1, $montoFinal, $nota->fecha, $nota->observaciones);
                        } else {
                            $factura->estatus = 5; //Abierta con Devolución
                        }
                    } else {
                        $factura->estatus = 5; //Abierta con Devolución
                    }
                }
            }

            $factura->save();

            $mensaje = 'Se ha modificado satisfactoriamente la nota de débito';
            return redirect('empresa/notasdebito')->with('success', $mensaje)->with('nota_id', $nota->id);
        }
    }


    public function datatable_producto(Request $request, $producto)
    {
        // storing  request (ie, get/post) global array to a variable
        $requestData =  $request;
        $columns = array(
            // datatable column index  => database column name
            0 => 'notas_debito.nro',
            1 => 'nombrecliente',
            2 => 'notas_debito.fecha',
            3 => 'total',
            4 => 'porpagar',
            5 => 'acciones'
        );

        $desde = Carbon::parse($request->input('desde'));
        $hasta = Carbon::parse($request->input('hasta'));

        $facturas = NotaDebito::leftjoin('contactos as c', 'notas_debito.proveedor', '=', 'c.id')->select('notas_debito.*', DB::raw('c.nombre as nombrecliente'))
        ->where('notas_debito.empresa', Auth::user()->empresa)
        ->whereRaw('notas_debito.id in (Select distinct(nota) from items_notas_debito where producto=' . $producto . ')')
        ->when($request->desde, function ($query) use ($desde) {
            return $query->where('notas_debito.fecha', '>=', $desde);
        })
        ->when($request->hasta, function ($query) use ($hasta) {
            return $query->where('notas_debito.fecha', '<=', $hasta);
        });

        if ($requestData->search['value']) {
            // if there is a search parameter, $requestData['search']['value'] contains search parameter
            $facturas = $facturas->where(function ($query) use ($requestData) {
                $query->where('notas_debito.nro', 'like', '%' . $requestData->search['value'] . '%')
                    ->orwhere('c.nombre', 'like', '%' . $requestData->search['value'] . '%');
            });
        }
        $totalFiltered = $totalData = $facturas->count();
        $facturas->orderby($columns[$requestData['order'][0]['column']], $requestData['order'][0]['dir'])->skip($requestData['start'])->take($requestData['length']);
        $facturas = $facturas->get();

        $data = array();
        foreach ($facturas as $factura) {
            $objTotal = $factura->total()->total;
            $nestedData = array();
            $nestedData[] = '<a href="' . route('notasdebito.show', $factura->nro) . '">' . $factura->nro . '</a>';
            $nestedData[] = '<a href="' . route('contactos.show', $factura->proveedor) . '" target="_blanck">' . $factura->nombrecliente . '</a>';
            $nestedData[] = date('d-m-Y', strtotime($factura->fecha));
            $nestedData[] = Auth::user()->empresaObj->moneda . Funcion::Parsear($objTotal->total);
            $nestedData[] = Auth::user()->empresaObj->moneda . Funcion::Parsear($factura->por_aplicar());
            $nestedData[] = ($objTotal->$producto ?? '');
            $boton = '<a href="' . route('notasdebito.show', $factura->nro) . '"  class="btn btn-outline-info btn-icons" title="Ver"><i class="far fa-eye"></i></i></a>
          <a href="' . route('notasdebito.edit', $factura->nro) . '"  class="btn btn-outline-light btn-icons" title="Editar"><i class="fas fa-edit"></i></a>
          <form action="' . route('notasdebito.destroy', $factura->id) . '" method="post" class="delete_form" style="margin:  0;display: inline-block;" id="eliminar-notasdebito' . $factura->id . '">
              ' . csrf_field() . '
            <input name="_method" type="hidden" value="DELETE">
          </form>
          <button class="btn btn-outline-danger  btn-icons negative_paging" type="submit" title="Eliminar" onclick="confirmar(' . "'eliminar-notasdebito" . $factura->id . "', '¿Estas seguro que deseas eliminar nota de débito?', 'Se borrara de forma permanente');" . '"><i class="fas fa-times"></i></button>
              ';

            $nestedData[] = $boton;
            $data[] = $nestedData;
        }
        $json_data = array(
            "draw" => intval($requestData->draw),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw.
            "recordsTotal" => intval($totalData),  // total number of records
            "recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
            "data" => $data   // total data array
        );

        return json_encode($json_data);
    }

    public function datatable_cliente(Request $request, $contacto)
    { // storing  request (ie, get/post) global array to a variable
        $requestData =  $request;
        $columns = array(
            // datatable column index  => database column name
            0 => 'notas_debito.nro',
            1 => 'nombrecliente',
            2 => 'notas_debito.fecha',
            3 => 'total',
            4 => 'porpagar',
            5 => 'acciones'
        );
        $facturas = NotaDebito::leftjoin('contactos as c', 'notas_debito.proveedor', '=', 'c.id')
            ->select('notas_debito.*', DB::raw('c.nombre as nombrecliente'))
            ->orderBy('notas_debito.created_at', 'desc')
            ->where('notas_debito.empresa', Auth::user()->empresa)
            ->where('notas_debito.proveedor', $contacto);

        $totalResult = count($facturas->get());

        if ($requestData->search['value']) {
            // if there is a search parameter, $requestData['search']['value'] contains search parameter
            $facturas = $facturas->where(function ($query) use ($requestData) {
                $query->where('notas_debito.nro', 'like', '%' . $requestData->search['value'] . '%')
                    ->orwhere('c.nombre', 'like', '%' . $requestData->search['value'] . '%');
            });
        } else {
            $facturas->skip($requestData['start'])->take($requestData['length']);
        }

        $facturas->orderby($columns[$requestData['order'][0]['column']], $requestData['order'][0]['dir']);
        $facturas = $facturas->get();

        $data = array();
        foreach ($facturas as $factura) {
            $nestedData = array();
            $nestedData[] = '<a href="' . route('notasdebito.show', $factura->nro) . '">' . $factura->nro . '</a>';
            $nestedData[] = '<a href="' . route('contactos.show', $factura->proveedor) . '" target="_blanck">' . $factura->nombrecliente . '</a>';
            $nestedData[] = date('d-m-Y', strtotime($factura->fecha));
            $nestedData[] = Auth::user()->empresaObj->moneda . Funcion::Parsear($factura->total()->total);
            $nestedData[] = Auth::user()->empresaObj->moneda . Funcion::Parsear($factura->por_aplicar());
            $boton = '<a href="' . route('notasdebito.show', $factura->nro) . '"  class="btn btn-outline-info btn-icons" title="Ver"><i class="far fa-eye"></i></i></a>
          <a href="' . route('notasdebito.edit', $factura->nro) . '"  class="btn btn-outline-light btn-icons" title="Editar"><i class="fas fa-edit"></i></a>
          <form action="' . route('notasdebito.destroy', $factura->id) . '" method="post" class="delete_form" style="margin:  0;display: inline-block;" id="eliminar-notasdebito' . $factura->id . '">
              ' . csrf_field() . '
            <input name="_method" type="hidden" value="DELETE">
          </form>
          <button class="btn btn-outline-danger  btn-icons negative_paging" type="submit" title="Eliminar" onclick="confirmar(' . "'eliminar-notasdebito" . $factura->id . "', '¿Estas seguro que deseas eliminar nota de débito?', 'Se borrara de forma permanente');" . '"><i class="fas fa-times"></i></button>
              ';


            $nestedData[] = $boton;
            $data[] = $nestedData;
        }
        $json_data = array(
            "draw" => intval($requestData->draw),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw.
            "iTotalRecords" => intval(count($data)),  // total number of records
            "iTotalDisplayRecords" => intval($totalResult), // total number of records after searching, if there is no searching then totalFiltered = totalData
            "aaData" => $data   // total data array
        );

        return json_encode($json_data);
    }


    public function destroy($id)
    {
        $facturas_reg = NotaDebitoFactura::where('nota', $id)->get();
        foreach ($facturas_reg as $factura) {
            if ($factura) {
                $factura = $factura->factura();
                if ($factura->pagado() > 0) {
                    $mensaje = 'La nota de débito no puede ser eliminada, debe eliminar el pago asociado a la factura.';
                    return back()->with('message_denied', $mensaje);
                }
            }
        }
        $nota = NotaDebito::where('empresa', Auth::user()->empresa)->where('id', $id)->first();
        if ($nota) {
            //Recoloco los items los productos en la bodega
            $items = ItemsNotaDebito::join('inventario as inv', 'inv.id', '=', 'items_notas_debito.producto')->select('items_notas_debito.*')->where('items_notas_debito.nota', $nota->id)->where('inv.tipo_producto', 1)->get();
            foreach ($items as $item) {
                $ajuste = ProductosBodega::where('empresa', Auth::user()->empresa)->where('bodega', $nota->bodega)->where('producto', $item->producto)->first();
                if ($ajuste) {
                    $ajuste->nro -= $item->cant;
                    $ajuste->save();
                }
            }
            ItemsNotaDebito::where('nota', $nota->id)->delete();

            //Coloco el estatus de la factura en abierta
            $facturas_reg = NotaDebitoFactura::where('nota', $nota->id)->get();
            foreach ($facturas_reg as $factura) {
                $factura = $factura->factura();
                $total_fact = $factura->total()->total;
                if ($factura->pagado() == $total_fact) {
                    $factura->estatus = 0;
                } else {
                    $factura->estatus = 1;
                }
                $factura->save();
            }
            NotaDebitoFactura::where('nota', $nota->id)->delete();

            $items = DevolucionesDebito::where('nota', $nota->id)->get();
            foreach ($items as $key => $value) {
                $ingreso = Ingreso::where('empresa', Auth::user()->empresa)->where('nro_devolucion', $value->id)->first();
                //ingresos
                $this->destroy_transaccion(1, $ingreso->id);
                $ingreso->delete();
            }
            DevolucionesDebito::where('nota', $nota->id)->delete();
            $nota->delete();
            $mensaje = 'Se ha eliminado satisfactoriamente la nota de crédito';
            return back()->with('success', $mensaje);
        }
        return redirect('empresa/notasdebito')->with('success', 'No existe un registro con ese id');
    }


    /*public function items_fact($id){

        $factura = FacturaProveedores::where('empresa',Auth::user()->empresa)->where('id', $id)->where('tipo',1)->first();
        if ($factura) {

            $items = ItemsFacturaProv::where('factura',$factura->id)->get();
            $bodega = Bodega::where('empresa',Auth::user()->empresa)->where('id', $factura->bodega)->first();
            if (!$bodega) {$bodega = Bodega::where('empresa',Auth::user()->empresa)->where('status', 1)->first();}
            $inventario = Inventario::select('inventario.*', DB::raw('(Select nro from productos_bodegas where bodega='.$bodega->id.' and producto=inventario.id) as nro'))
                ->where('empresa',Auth::user()->empresa)
                ->where('status', 1)
                ->havingRaw('if(inventario.tipo_producto=1, id in (Select producto from productos_bodegas where bodega='.$bodega->id.'), true)')
                ->get();

            $retencionesFacturas=FacturaProveedoresRetenciones::where('factura', $factura->id)->get();
            $retenciones = Retencion::where('empresa',Auth::user()->empresa)->get();
            $impuestos = Impuesto::where('empresa',Auth::user()->empresa)->orWhere('empresa', null)->Where('estado', 1)->get();
            $categorias=Categoria::where('empresa',Auth::user()->empresa)->where('estatus', 1)->whereNull('asociado')->get();

            return view('notasdebito.items_fact')->with(compact('factura', 'items', 'inventario',  'impuestos',
                'categorias', 'retencionesFacturas', 'retenciones'
                ));
        }
    }*/

    public function items_fact($id)
    {
        $factura = FacturaProveedores::where('empresa', Auth::user()->empresa)->where('id', $id)->first();

        //$retencionesFacturas = FacturaProveedoresRetenciones::where('factura', $factura->id)->get();
        //$retenciones = Retencion::where('empresa',Auth::user()->empresa)->get();

        if ($factura) {
            $bodega = Bodega::where('empresa', Auth::user()->empresa)->where('id', $factura->bodega)->first();
            if (!$bodega) {
                $bodega = Bodega::where('empresa', Auth::user()->empresa)->where('status', 1)->first();
            }

            if($factura->items->first()->tipo_item == 1){
                $items = ItemsFacturaProv::select('items_factura_proveedor.*', 'inventario.producto as nombre', 'inventario.ref as refer')
                        ->leftJoin('inventario', 'inventario.id', '=', 'items_factura_proveedor.producto')
                        ->where('factura', $factura->id)
                        ->get();
            }else{
                $items = ItemsFacturaProv::select('items_factura_proveedor.*', 'categorias.nombre as nombre', 'categorias.codigo as ref')
                        ->leftJoin('categorias', 'categorias.id', '=', 'items_factura_proveedor.producto')
                        ->where('factura', $factura->id)
                        ->get();
            }

            $impuestos = Impuesto::where('empresa', Auth::user()->empresa)
                ->orWhere('empresa', null)
                ->Where('estado', 1)->get();

            foreach ($items as $item) {
                $item->precio = round($item->precio, 4);
                $item->impuesto = round($item->impuesto, 4);
                $item->cant = round($item->cant, 4);
                $item->desc = round($item->desc, 4);
            }

            foreach ($items as $item) {
                $item->precio = round($item->precio, 4);
                $item->impuesto = round($item->impuesto, 4);
                $item->cant = round($item->cant, 4);
                $item->desc = round($item->desc, 4);
            }

            $notasDebito = $factura->notas_debito();

            foreach ($notasDebito as $notasD) {
                $nota = $notasD->nota();
                $itemsNota = ItemsNotaDebito::where('nota', $nota->id)->get();

                foreach ($itemsNota as $itemN) {

                    $item = $items->where('producto', $itemN->producto)->first();
                    if ($item) {
                        $item->cant = $item->cant - round($itemN->cant, 4);
                    }
                }
            }

            foreach ($items as $key => $item) {
                if ($item->cant <= 0) {
                }
            }
        }
        return json_encode($items);
    }

    public function jsonDianNotaAjuste($id){

        try {

            $nota = NotaDebito::find($id);
            $operacionCodigo = "10"; //residente 10 no residente 11
            $empresa = Empresa::Find($nota->empresa);
            $cliente = $nota->clienteObj;
            $modoBTW = env('BTW_TEST_MODE') == 1 ? 'test' : 'prod';

            if (!$nota && !$empresa) {
                if(request()->ajax()){
                    return response()->json(['status'=>'error', 'message' => 'Nota débito o empresa no encontrada'], 404);
                }else{
                    return redirect('/empresa/notasdebito')->with('message_denied', 'Nota débito o empresa no encontrada');
                }
            }

            $documento = $nota->facturaNotaCredito->facturaObj;

            if (!$documento) {
                if(request()->ajax()){
                    return response()->json(['status'=>'error', 'message' => 'Nota débito relacionada no encontrada'], 404);
                }else{
                    return redirect('/empresa/notasdebito')->with('message_denied', 'Nota débito relacionada no encontrada');
                }
            }

            $resolucion = NumeracionFactura::where('empresa', Auth::user()->empresa)
            ->where('num_equivalente', 1)->where('nomina', 0)->where('preferida', 1)->first();

            if(!$resolucion){
                if(request()->ajax()){
                    return response()->json(['status'=>'error', 'message' => 'No hay resolucion de documento soporte activa, por favor verifique'], 404);
                }else{
                    return redirect('/empresa/notasdebito')->with('message_denied', 'No hay resolucion de documento soporte activa, por favor verifique');
                }
            }

            if($empresa->btw_login == null){
                if(request()->ajax()){
                    return response()->json(['status'=>'error', 'message' => 'La empresa no tiene configurado el login para el servicio de BTW'], 404);
                }else{
                    return redirect('/empresa/notasdebito')->with('message_denied', 'La empresa no tiene configurado el login para el servicio de BTW');
                }
            }

            // Construccion del json por partes.
            $jsonDocumentHead = DocumentSupportJsonBuilder::buildFromHeadAdjustmentNote($nota,$documento,$resolucion,$modoBTW);
            $jsonDocumentDetails = DocumentSupportJsonBuilder::buildFromDetails($nota,$resolucion,$modoBTW);
            $jsonDocumentCompany = DocumentSupportJsonBuilder::buildFromCompany($cliente,$empresa, $modoBTW, $operacionCodigo);
            $jsonDocumentCustomer = DocumentSupportJsonBuilder::buildFromCustomer($cliente,$empresa, $modoBTW, $nota);
            $jsonDocumentTaxes = DocumentSupportJsonBuilder::buildFromTaxes(false,$nota,$empresa,$modoBTW);
            $jsonDocumentReference = DocumentSupportJsonBuilder::buildFromReferenceDocument($documento,$nota,$cliente);
            $jsonDiscrepancyResponse = DocumentSupportJsonBuilder::buildFromDiscrepancyResponse($nota,$cliente);


            $fullJson = DocumentSupportJsonBuilder::buildFullInvoice([
                'head'              => $jsonDocumentHead,
                'details'           => $jsonDocumentDetails,
                'company'           => $jsonDocumentCompany,
                'customer'          => $jsonDocumentCustomer,
                'taxes'             => $jsonDocumentTaxes,
                'invcRef'           => $jsonDocumentReference,
                'discrepancyResponse' => $jsonDiscrepancyResponse,
                'mode'              => $modoBTW,
                'btw_login'         => $empresa->btw_login,
                'software'          => 2,
            ]);

            // Envio de json completo a microservicio de gestoru.
            $btw = new BTWService;
            $response = (object)$btw->sendDocumentBTW($fullJson);

            if(isset($response->status) && $response->status == 'success'){

                $nota->emitida = 1;
                $nota->dian_response = $response->cufe;
                $nota->uuid = $response->cufe;
                $nota->save();
                $mensaje = "Nota débito emitida correctamente con el cufe: " . $nota->dian_response;
                $mensajeCorreo = '';

                // Envio de correo con el zip.
                if($modoBTW == 'prod'){
                    $mensajeCorreo = $this->sendPdfEmailBTW($btw,$nota,$cliente,$empresa,2);
                }
                // Fin envio de correo con el zip.

                if(request()->ajax()){
                    return response()->json([
                        'status' => 'success',
                        'message' => $mensaje . " " . $mensajeCorreo,
                        'data' => $response
                    ]);
                }else{
                    return redirect('/empresa/notasdebito')->with('message_success', 'Nota débito emitida correctamente con el cufe: ' .$response->cufe);
                }
            }

            if(isset($response->success) && $response->success == false){

                if(isset($response->result)){

                    $message = $this->formatedResponseErrorBTW($response->result->descResponseDian);
                    if(request()->ajax()){
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Error en campos mandatorios.',
                        'error' => $message
                    ]);
                    }else{
                        return redirect('/empresa/notasdebito')->with('message_denied_btw', $message);
                    }
                }

                if(request()->ajax()){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Error al enviar la nota crédito',
                        'error' => $response->message
                    ], 500);
                }else{
                    return redirect('/empresa/notasdebito')->with('message_denied_btw', $response->message);
                }

            }else{

                if(isset($response->statusCode) && $response->statusCode == 500){

                    $message = $this->formatedResponseErrorBTW($response->th['btw_response']);

                    if(request()->ajax()){
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Error al procesar la solicitud',
                            'error' => $message
                        ], 500);
                    }else{
                        return redirect('/empresa/notasdebito')->with('message_denied_btw', $message);
                    }
                }

                return redirect()->back()->with('message_denied_btw', 'Error al procesar la solicitud, por favor intente nuevamente.');
            }

        } catch (\Throwable $th) {

            if(request()->ajax()){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al procesar la solicitud',
                    'error' => 'Error al procesar la solicitud: ' . $th->getMessage()],
                    500
                );
            }
            else{
                return redirect('/empresa/notasdebito')->with('message_denied_btw', $th->getMessage());
            }
        }

    }

    public function xmlNotaDebito($id)
    {
        $notaDebito = NotaDebito::find($id);

        $ResolucionNumeracion = NumeracionFactura::where('empresa', Auth::user()->empresa)->where('preferida', 1)->first();

        $infoEmpresa = Auth::user()->empresaObj;
        $data['Empresa'] = $infoEmpresa->toArray();

        $retenciones = NotaRetencion::select('notas_retenciones.*', 'retenciones.tipo as id_tipo')
            ->join('retenciones', 'retenciones.id', '=', 'notas_retenciones.id_retencion')
            ->where('notas', $notaDebito->id)
            ->where('retenciones.empresa', Auth::user()->empresa)
            ->get();

        //-------------- Factura Relacionada -----------------------//
        $nroFacturaRelacionada =  NotaDebitoFactura::where('nota', $id)->first()->factura;
        $FacturaRelacionada    = FacturaProveedores::find($nroFacturaRelacionada) ?? null;

        $impTotal = 0;
        if (isset($FacturaRelacionada)) {
            foreach ($FacturaRelacionada->total()->imp as $totalImp) {
                if (isset($totalImp->total)) {
                    $impTotal = $totalImp->total;
                }
            }
        }

        $decimal = explode(".", $impTotal);
        if (
            isset($decimal[1]) && $decimal[1] >= 50 || isset($decimal[1]) && $decimal[1] == 5 || isset($decimal[1]) && $decimal[1] == 4
            || isset($decimal[1]) && $decimal[1] == 3 || isset($decimal[1]) && $decimal[1] == 2 || isset($decimal[1]) && $decimal[1] == 1
        ) {
            $impTotal = round($impTotal, 2);
        } else {
            $impTotal = round($impTotal, 2);
        }
        /*
        if(auth()->user()->empresa == 114){
            //dd($impTotal);
                    //$CufeFactRelacionada  = $FacturaRelacionada->info_cufe($nroFacturaRelacionada, $impTotal);
                    //dd($CufeFactRelacionada);
        }
        */
        $CufeFactRelacionada  = $FacturaRelacionada->info_cufe($nroFacturaRelacionada, $impTotal);
        //--------------Fin Factura Relacionada -----------------------//


        $impTotal = 0;

        foreach ($notaDebito->total()->imp as $totalImp) {
            if (isset($totalImp->total)) {
                $impTotal = $totalImp->total;
            }
        }

        $decimal = explode(".", $impTotal);
        if (isset($decimal[1])) {
            $impTotal = round($impTotal, 2);
        }

        $items = ItemsNotaDebito::where('nota', $id)->get();

        if ($notaDebito->tiempo_creacion) {
            $horaFac = $notaDebito->tiempo_creacion;
        } else {
            $horaFac = $notaDebito->created_at;
        }

        $totalIva = 0.00;
        $totalInc = 0.00;

        // foreach ($notaDebito->total()->imp as $key => $imp) {
        //     if (isset($imp->total) && $imp->tipo == 1) {
        //         $totalIva = $impTotal;
        //     } elseif (isset($imp->total) && $imp->tipo == 3) {
        //         $totalInc = $impTotal;
        //     }
        // }

        $CUDEvr  = $notaDebito->info_cude($impTotal);
        // dd($CUDE);

        $infoCliente = Contacto::find($notaDebito->proveedor);
        $data['Cliente'] = $infoCliente->toArray();

        $responsabilidades_empresa = DB::table('empresa_responsabilidad as er')
            ->join('responsabilidades_facturacion as rf', 'rf.id', '=', 'er.id_responsabilidad')
            ->select('rf.*')
            ->where('er.id_empresa', '=', Auth::user()->empresa)->where('er.id_responsabilidad', 5)
            ->orWhere('er.id_responsabilidad', 7)->where('er.id_empresa', '=', Auth::user()->empresa)
            ->orWhere('er.id_responsabilidad', 12)->where('er.id_empresa', '=', Auth::user()->empresa)
            ->orWhere('er.id_responsabilidad', 20)->where('er.id_empresa', '=', Auth::user()->empresa)
            ->orWhere('er.id_responsabilidad', 29)->where('er.id_empresa', '=', Auth::user()->empresa)->get();

        $emails = $notaDebito->cliente()->email;
        if ($notaDebito->cliente()->asociados('number') > 0) {
            $email = $emails;
            $emails = array();
            if ($email) {
                $emails[] = $email;
            }
            foreach ($notaDebito->cliente()->asociados() as $asociado) {
                if ($asociado->notificacion == 1 && $asociado->email) {
                    $emails[] = $asociado->email;
                }
            }
        }

        $tituloCorreo =  $data['Empresa']['nit'] . ";" . $data['Empresa']['nombre'] . ";" . $notaDebito->nro . ";91;" . $data['Empresa']['nombre'];

        if (is_array($emails)) {
            $max = count($emails);
        } else {
            $max = 1;
        }


        if (!$emails || $max == 0) {
            return redirect('empresa/notasdebito/' . $notaDebito->nro)->with('error', 'El Cliente ni sus contactos asociados tienen correo registrado');
        }

        $isImpuesto = 1;
        // foreach($notaDebito->total()->imp as $impuesto){
        //     if(isset($impuesto->total)){
        //         $isImpuesto = 1;
        //     }
        // }

        // if (auth()->user()->empresa == 551) {
        //     return response()->view('templates.xml.92', compact('CUDEvr', 'ResolucionNumeracion', 'notaDebito', 'data', 'items', 'retenciones', 'FacturaRelacionada', 'CufeFactRelacionada', 'responsabilidades_empresa', 'emails', 'impTotal', 'isImpuesto'))
        //         ->header('Cache-Control', 'public')
        //         ->header('Content-Description', 'File Transfer')
        //         ->header('Content-Disposition', 'attachment; filename=ND-' . $notaDebito->nro . '.xml')
        //         ->header('Content-Transfer-Encoding', 'binary')
        //         ->header('Content-Type', 'text/xml');
        // }

        $xml = view('templates.xml.92', compact('CUDEvr', 'ResolucionNumeracion', 'notaDebito', 'data', 'items', 'retenciones', 'FacturaRelacionada', 'CufeFactRelacionada', 'responsabilidades_empresa', 'emails', 'impTotal', 'isImpuesto'));

        /*
        if(auth()->user()->empresa == 114){
            dd($xml->render());
        }
        */

        //-- Envío de datos a la DIAN --//
        $res = $this->EnviarDatosDian($xml);

        //-- Decodificación de respuesta de la DIAN --//
        $res = json_decode($res, true);
        // dd($res);
        if (!isset($res['statusCode']) && isset($res['message'])) {
            return redirect('/empresa/notasdebito')->with('message_denied', $res['message']);
        }

        $statusCode = Arr::exists($res, 'statusCode') ? $res['statusCode'] : null; //200

        if (!isset($statusCode)) {
            return back()->with('message_denied', isset($res['message']) ? $res['message'] : 'Error en la emisión del docuemento, intente nuevamente en un momento');
        }

        //-- Guardamos la respuesta de la dian cuando sea negativa --//
        if ($statusCode != 200) {
            $notaDebito->dian_response = $res['statusCode'];
            $notaDebito->save();
        }

        //-- Validación 1 del status code (Cuando hay un error) --//
        if ($statusCode != 200) {
            $message = $res['errorMessage'];
            $errorReason = $res['errorReason'];
            $statusCode =  $res['statusCode'];

            //Validamos si depronto la nota crédito fue emitida pero no quedamos con ningun registro de ella.
            $saveNoJson = $statusJson = $this->validateStatusDian(auth()->user()->empresaObj->nit, $notaDebito->nro, "95", "");

            //Decodificamos repsuesta y la guardamos en la variable status json
            $statusJson = json_decode($statusJson, true);

            if ($statusJson["statusCode"] != 200) {
                //Validamos enviando la solciitud de esta manera, ya que funciona de varios modos
                $res = $saveNoJson = $statusJson = $this->validateStatusDian(auth()->user()->empresaObj->nit, $notaDebito->nro, "95", "");

                //Decodificamos repsuesta y la guardamos en la variable status json
                $statusJson = json_decode($statusJson, true);
            }

            if ($statusJson["statusCode"] == 200) {
                $message = "Nota débito emitida correctamente por validación.";
                $notaDebito->emitida = 1;
                $notaDebito->dian_response = $statusJson["statusCode"];
                $notaDebito->fecha_expedicion = Carbon::now();
                $notaDebito->save();

                $this->generateXmlPdfEmail($statusJson['document'], $notaDebito, $emails, $data, $CUDEvr, $items, $ResolucionNumeracion, $tituloCorreo);
            } else {

                //Si no pasa despues de validar el estado de la dian, probablemente sea por que esta tirando error de "documento procesado anteriormente" así no esté procesado
                //entonces requerimos ir al xml colocar un espacio en el nro y colocar un espacio en el cude.
                $responseConEspacio = $this->reenvioXmlEspacio(
                    $notaDebito,
                    $impTotal,
                    $data,
                    $ResolucionNumeracion,
                    $items,
                    $retenciones,
                    $FacturaRelacionada,
                    $CufeFactRelacionada,
                    $responsabilidades_empresa,
                    $emails,
                    $isImpuesto
                );

                if (isset($responseConEspacio["statusCode"])) {

                    if ($responseConEspacio["statusCode"] == 200) {
                        $message = "Nota débito emitida correctamente";
                        $notaDebito->emitida = 1;
                        $notaDebito->dian_response = $responseConEspacio['statusCode'];
                        $notaDebito->notificacion = 1;
                        $notaDebito->fecha_expedicion = Carbon::now();
                        $notaDebito->save();

                        $this->generateXmlPdfEmail($responseConEspacio['document'], $notaDebito, $emails, $data, $CUDEvr, $items, $ResolucionNumeracion, $tituloCorreo);
                    } else {
                        return back()->with('message_denied', $message)->with('errorReason', $errorReason)->with('statusCode', $statusCode);
                    }
                } else {
                    return back()->with('message_denied', $message)->with('errorReason', $errorReason)->with('statusCode', $statusCode);
                }
            }
        }

        //-- estátus de que la factura ha sido aprobada --//
        if ($statusCode == 200) {
            $message = "Nota crédito emitida correctamente";
            $notaDebito->emitida = 1;
            $notaDebito->fecha_expedicion = Carbon::now();
            $notaDebito->save();

            $this->generateXmlPdfEmail($res['document'], $notaDebito, $emails, $data, $CUDEvr, $items, $ResolucionNumeracion, $tituloCorreo);
        }
        return back()->with('message_success', $message);
    }

    public function reenvioXmlEspacio($notaDebito, $impTotal, $data, $ResolucionNumeracion, $items, $retenciones, $FacturaRelacionada, $CufeFactRelacionada, $responsabilidades_empresa, $emails, $isImpuesto)
    {
        //Hacemos parche que nos ha servido hasta el momento para este tipo de errores
        $notaDebito->nro = "  " . $notaDebito->nro;

        $infoCude = [
            'Numfac' => $notaDebito->nro,
            'FecFac' => Carbon::parse($notaDebito->fecha)->format('Y-m-d'),
            'HorFac' => Carbon::parse($notaDebito->created_at)->format('H:i:s') . '-05:00',
            'ValFac' => number_format($notaDebito->total()->subtotal - $notaDebito->total()->descuento, 2, '.', ''),
            'CodImp' => '01',
            'ValImp' => number_format($impTotal, 2, '.', ''),
            'CodImp2' => '04',
            'ValImp2' => '0.00',
            'CodImp3' => '03',
            'ValImp3' => '0.00',
            'ValTot' => number_format($notaDebito->total()->subtotal + $notaDebito->impuestos_totales() - $notaDebito->total()->descuento, 2, '.', ''),
            'NitFE'  => $data['Empresa']['nit'],
            'NumAdq' => $notaDebito->cliente()->nit,
            'pin'    => 75315,
            'TipoAmb' => 1,
        ];

        $CUDE = $infoCude['Numfac'] . $infoCude['FecFac'] . $infoCude['HorFac'] . $infoCude['ValFac'] . $infoCude['CodImp'] . $infoCude['ValImp'] . $infoCude['CodImp2'] . $infoCude['ValImp2'] . $infoCude['CodImp3'] . $infoCude['ValImp3'] . $infoCude['ValTot'] . $infoCude['NitFE'] . $infoCude['NumAdq'] . $infoCude['pin'] . $infoCude['TipoAmb'];


        $CUDEvr = hash('sha384', $CUDE);

        // if(auth()->user()->empresa == 240){
        //  return response()->view('templates.xml.91',compact('CUDEvr','ResolucionNumeracion','notaDebito', 'data','items','retenciones','FacturaRelacionada','CufeFactRelacionada','responsabilidades_empresa','emails','impTotal','isImpuesto'))
        //     ->header('Cache-Control', 'public')
        //     ->header('Content-Description', 'File Transfer')
        //     ->header('Content-Disposition', 'attachment; filename=NC-'.$notaDebito->nro.'.xml')
        //     ->header('Content-Transfer-Encoding', 'binary')
        //     ->header('Content-Type', 'text/xml');
        // }

        $xml = view('templates.xml.92', compact('CUDEvr', 'ResolucionNumeracion', 'notaDebito', 'data', 'items', 'retenciones', 'FacturaRelacionada', 'CufeFactRelacionada', 'responsabilidades_empresa', 'emails', 'impTotal', 'isImpuesto'));

        $res = $this->EnviarDatosDian($xml);

        $res = json_decode($res, true);

        return $res;
    }

    /**
     * Metodo de generacion de xml,pdf y envio de email de una nota credito dian
     * Consultamos si una factura ya fue emititda y no quedamos con registro de ella, de ser así la guardamos, en bd, generamos el xml y enviamos el correo al cliente.
     */
    public function generateXmlPdfEmail($document, $notaDebito, $emails, $data, $CUDEvr, $items, $ResolucionNumeracion, $tituloCorreo)
    {

        $empresa = auth()->user()->empresaObj;
        $document = base64_decode($document);

        //-- Generación del archivo .xml mas el lugar donde se va a guardar --//
        $path = public_path() . '/xml/empresa' . auth()->user()->empresa;

        if (!File::exists($path)) {
            File::makeDirectory($path);
            $path = $path . "/ND";
            File::makeDirectory($path);
        } else {
            $path = public_path() . '/xml/empresa' . auth()->user()->empresa . "/NC";
            if (!File::exists($path)) {
                File::makeDirectory($path);
            }
        }

        $namexml = 'ND-' . $notaDebito->nro . ".xml";
        $ruta_xmlresponse = $path . "/" . $namexml;
        $file = fopen($ruta_xmlresponse, "w");
        fwrite($file, $document . PHP_EOL);
        fclose($file);

        //-- Construccion del pdf a enviar con el código qr + el envío del archivo xml --//
        if ($notaDebito) {

            /*..............................
        Construcción del código qr a la factura
        ................................*/
            $impuesto = 0;
            foreach ($notaDebito->total()->imp as $key => $imp) {
                if (isset($imp->total)) {
                    $impuesto = $imp->total;
                }
            }

            $codqr = "NumFac:" . $notaDebito->codigo . "\n" .
                "NitFac:"  . $data['Empresa']['nit']   . "\n" .
                "DocAdq:" .  $data['Cliente']['nit'] . "\n" .
                "FecFac:" . Carbon::parse($notaDebito->created_at)->format('Y-m-d') .  "\n" .
                "HoraFactura" . Carbon::parse($notaDebito->created_at)->format('H:i:s') . '-05:00' . "\n" .
                "ValorFactura:" .  number_format($notaDebito->total()->subtotal, 2, '.', '') . "\n" .
                "ValorIVA:" .  number_format($impuesto, 2, '.', '') . "\n" .
                "ValorOtrosImpuestos:" .  0.00 . "\n" .
                "ValorTotalFactura:" .  number_format($notaDebito->total()->subtotal + $notaDebito->impuestos_totales(), 2, '.', '') . "\n" .
                "CUDE:" . $CUDEvr;

            /*..............................
            Construcción del código qr a la factura
            ................................*/

            $itemscount = $items->count();
            $nota = $notaDebito;
            $facturas = NotaDebitoFactura::where('nota', $nota->id)->get();
            $retenciones = FacturaRetencion::join('notas_factura as nf', 'nf.factura', '=', 'factura_retenciones.factura')
                ->join('retenciones', 'retenciones.id', '=', 'factura_retenciones.id_retencion')
                ->where('nf.nota', $nota->id)->get();

            if ($nota->tipo_operacion == 3) {
                $detalle_recaudo = Factura::where('id', $facturas->first()->factura)->first();
                $nota->placa = $detalle_recaudo->placa;
                $detalle_recaudo = $detalle_recaudo->detalleRecaudo();
                $pdf = PDF::loadView('pdf.creditotercero', compact('nota', 'items', 'facturas', 'itemscount', 'codqr', 'CUDEvr', 'detalle_recaudo'))
                    ->save(public_path() . "/convertidor" . "/ND-" . $nota->nro . ".pdf");
            } else {
                $pdf = PDF::loadView('pdf.debito', compact('nota', 'items', 'facturas', 'retenciones', 'itemscount', 'codqr', 'CUDEvr', 'empresa'))
                    ->save(public_path() . "/convertidor" . "/ND-" . $nota->nro . ".pdf");
            }

            /*..............................
            Construcción del envío de correo electrónico
            ................................*/

            $data = array(
                'email' => 'info@gestordepartes.net',
            );
            $totalRecaudo = 0;
            if ($nota->total_recaudo != null) {
                $totalRecaudo = $nota->total_recaudo;
            }
            $total = Funcion::Parsear($nota->total()->total + $totalRecaudo);
            $cliente = $nota->cliente()->nombre;

            //Construccion del archivo zip.
            $zip = new ZipArchive();

            //Después creamos un archivo zip temporal que llamamos miarchivo.zip y que eliminaremos después de descargarlo.
            //Para indicarle que tiene que crearlo ya que no existe utilizamos el valor ZipArchive::CREATE.
            $nombreArchivoZip = "ND-" . $nota->nro . ".zip";

            $zip->open("convertidor/" . $nombreArchivoZip, ZipArchive::CREATE);

            if (!$zip->open($nombreArchivoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
                return ("Error abriendo ZIP en $nombreArchivoZip");
            }

            $ruta_pdf = public_path() . "/convertidor" . "/ND-" . $nota->nro . ".pdf";

            $zip->addFile($ruta_xmlresponse, "NC-" . $nota->nro . ".xml");
            $zip->addFile($ruta_pdf, "NC-" . $nota->nro . ".pdf");
            $resultado = $zip->close();

            Mail::send('emails.notascredito', compact('nota', 'total', 'cliente'), function ($message) use ($pdf, $emails, $ruta_xmlresponse, $nota, $nombreArchivoZip, $tituloCorreo) {
                $message->attach($nombreArchivoZip, ['as' => $nombreArchivoZip, 'mime' => 'application/octet-stream', 'Content-Transfer-Encoding' => 'Binary']);


                /*Peticiones de clientes que no quieren que se mande la factura de venta por fuera del zip (tal como si es permitido por la DIAN)*/
                if (config('app.name') == "Gestoru" && auth()->user()->empresa != 52) {
                    $message->attachData($pdf->output(), 'ND-' . $nota->nro . '.pdf', ['mime' => 'application/pdf']);
                }

                $message->from('info@gestordepartes.net', Auth::user()->empresaObj->nombre);
                $message->to(array_filter((array) $emails))->subject($tituloCorreo);
            });

            // Si quieres puedes eliminarlo después:
            if (isset($nombreArchivoZip)) {
                unlink($nombreArchivoZip);
                unlink($ruta_pdf);
            }
        }
    }

    public function validateTimeEmicion()
    {
        if (Auth::user()->empresaObj->estado_dian == 1) {
            $pendientes = NotaDebito::where('empresa', auth()->user()->empresa)
                ->where('emitida', 0)
                ->whereDate('fecha', '>=', '2019-11-01')
                ->get();
            return response()->json($pendientes);
        } else {
            return null;
        }
    }


    public function etiqueta($nota, EtiquetaEstado $etiqueta)
    {

        $notaDebito = NotaDebito::find($nota);

        try {
            if (isset($notaDebito) && isset($etiqueta)) {

                $notaDebito->update(['etiqueta_id' => $etiqueta->id]);
                $etiqueta->color;

                return response()->json([
                    'success' => true,
                    'etiqueta' => $etiqueta,
                    'message' => 'Etiqueta modificada con éxito'
                ]);
            }
            return response()->json([
                'success'  => false,
                'message'  => 'Hubo un error, intente nuevamente',
                'title'    => 'ERROR',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ]);
        }
    }
}
