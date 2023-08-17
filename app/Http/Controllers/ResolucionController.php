<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Resolucion;
use App\Cronograma;

class ResolucionController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function index(Request $request){
        $resoluciones=Resolucion::select('*', DB::raw('YEAR(fecha) as anio'))
        ->where('estado',1)
        ->where(function($query) use ($request)
        {
            $query->where('fecha','LIKE', '%'.$request->query('search').'%')
            ->orWhere('nro_resolucion','LIKE', '%'.$request->query('search').'%');
        })
        ->orderBy('fecha','desc')
        ->get();
        foreach ($resoluciones as $key => $resolucion) {
            $resolucion->cronogramas=Cronograma::select('cronograma_carpeta.*','dependencia.nombre as dependencia',
            DB::raw("(case 
                    when cronograma_carpeta.idTipo_tramite_unidad = 15 then CONCAT(unidad.descripcion,'-','BACHILLER') 
                    when cronograma_carpeta.idTipo_tramite_unidad = 16 then CONCAT(unidad.descripcion,'-','TITULO PROFESIONAL') 
                    when cronograma_carpeta.idTipo_tramite_unidad = 34 then CONCAT(unidad.descripcion,'-','TITULO DE SEGUNDA ESPECIALIDAD PROFESIONAL') 
                end) as unidad"))
            ->join('dependencia','dependencia.idDependencia','cronograma_carpeta.idDependencia')
            ->join('unidad','unidad.idUnidad','cronograma_carpeta.idUnidad')
            ->where('idResolucion',$resolucion->idResolucion)->where('cronograma_carpeta.estado',1)->get();
        }
        return response()->json($resoluciones, 200);
    }

    public function store(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
           
            $resolucionValidate=Resolucion::where('nro_resolucion',$request->nro_resolucion)->first();
            if ($resolucionValidate) {
                return response()->json( ['status'=>400,'message'=>'La resolución ya se encuentra registrada'],400);
            }
            $resolucion=new Resolucion;
            $resolucion->nro_resolucion=trim($request->nro_resolucion);
            if(substr($resolucion->nro_resolucion,-4)!="/UNT"){
                return response()->json( ['status'=>400,'message'=>'El número de resolucion debe terminar en /UNT'],400);
            }
            $resolucion->fecha=trim($request->fecha);
            $resolucion->estado =true;
            $resolucion->save();

            if ($request->cronogramas) {
                foreach ($request->cronogramas as $key => $value) {
                    // return $value;
                    $cronograma=Cronograma::find($value['idCronograma_carpeta']);
                    $cronograma->idResolucion=$resolucion->idResolucion;
                    $cronograma->update();
                }
            }else {
                return response()->json(['status' => '400', 'message' => "Asignar algún cronograma para la resolución"], 400);
            }
            $resolucion->anio = substr($resolucion->fecha, 0, 4);
            $resolucion->cronogramas=$request->cronogramas;
            DB::commit();
            return response()->json($resolucion, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request,$id){
        DB::beginTransaction();
        try {
            // return $request->all();
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
           
            $resolucion=Resolucion::find($id);
            $resolucion->nro_resolucion=trim($request->nro_resolucion);
            $resolucion->fecha=trim($request->fecha);
            $resolucion->estado =trim($request->estado);
            $resolucion->save();

            if ($request->cronogramas) {
                //Eliminamos todas las relaciones de los cronogramas que pertenecen a esa resolucion
                $cronogramas=Cronograma::where('idResolucion',$id)->get();
                foreach ($cronogramas as $key => $value) {
                    $value->idResolucion=null;
                    $value->save();
                }
                //agregamos las nuevas relaciones de cronogramas
                foreach ($request->cronogramas as $key => $value) {
                    $cronograma=Cronograma::find($value['idCronograma_carpeta']);
                    $cronograma->idResolucion=$resolucion->idResolucion;
                    $cronograma->save();
                }
            } else {
                return response()->json(['status' => '400', 'message' => "Asignar algún cronograma para la resolución"], 400);
            }
            
            $resolucion->anio = substr($resolucion->fecha, 0, 4);
            $resolucion->cronogramas=$request->cronogramas;
            DB::commit();
            return response()->json($resolucion, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function getResolucionesLibres($idOficio){
        $resoluciones=Resolucion::
        where(function($query) use ($idOficio)
        {
            if ($idOficio!=0) {
                $query->where('idOficio',null)
                ->orWhere('idOficio',$idOficio);
            }else {
                $query->where('idOficio',null);
            }
            
        })
        ->where('estado',1)
        ->orderBy('resolucion.nro_resolucion')
        ->get();
        return response()->json($resoluciones, 200);

    }
}
