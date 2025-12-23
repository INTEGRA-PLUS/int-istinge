<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Auth;
use App\Contrato;
use App\Funcion;
use App\User;
use DB;

class CajaNap extends Model
{
    protected $table = "caja_naps";
    protected $primaryKey = 'id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
         'nombre','spliter_asociado','cant_puertos','ubicacion','coordenadas','caja_naps_disponible', 'descripcion', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'
    ];

    protected $appends = ['uso', 'session'];

    public function getUsoAttribute()
    {
        return $this->uso();
    }

    public function getSessionAttribute()
    {
        return $this->getAllPermissions(Auth::user()->id);
    }

    public function getAllPermissions($id)
    {
        if(Auth::user()->rol>=2){
            if (DB::table('permisos_usuarios')->select('id_permiso')->where('id_usuario', $id)->count() > 0 ) {
                $permisos = DB::table('permisos_usuarios')->select('id_permiso')->where('id_usuario', $id)->get();
                foreach ($permisos as $key => $value) {
                    $_SESSION['permisos'][$permisos[$key]->id_permiso] = '1';
                }
                return $_SESSION['permisos'];
            }
            else return null;
        }
    }

    public function status($class=false){
        if($class){
            return $this->status == '1' ? 'success' : 'danger';
        }
        return $this->status == '1' ? 'Habilitado' : 'Deshabilitado';
    }

    public function uso(){
        $cont=0;
        $cont+=Contrato::where('nodo', $this->id)->count();
        $cont+=AP::where('nodo', $this->id)->count();
        return $cont;
    }

    public function created_by(){
        return User::find($this->created_by);
    }

    public function updated_by(){
        return User::find($this->updated_by);
    }

    public function nodos(){
        return DB::table('nodos')->where('status', 1)->get();
    }

    /**
     * Obtiene el primer puerto disponible de la caja NAP
     *
     * @param int|null $excluirContratoId ID del contrato a excluir de la búsqueda (útil al editar)
     * @return int|null Retorna el número del puerto disponible o null si no hay puertos disponibles
     */
    public function obtenerPuertoDisponible($excluirContratoId = null)
    {
        // Obtener todos los puertos ocupados para esta caja NAP
        $query = Contrato::where('cajanap_id', $this->id)
            ->whereNotNull('cajanap_puerto');

        // Excluir un contrato específico si se está editando
        if ($excluirContratoId) {
            $query->where('id', '!=', $excluirContratoId);
        }

        $puertosOcupados = $query->pluck('cajanap_puerto')->toArray();

        // Generar lista de todos los puertos posibles (1 hasta cant_puertos)
        $todosLosPuertos = range(1, $this->cant_puertos);

        // Encontrar los puertos disponibles
        $puertosDisponibles = array_diff($todosLosPuertos, $puertosOcupados);

        // Retornar el primer puerto disponible o null si no hay
        return !empty($puertosDisponibles) ? min($puertosDisponibles) : null;
    }
}
