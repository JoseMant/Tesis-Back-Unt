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

    public function index(){
        $resoluciones=Resolucion::where('estado',1)
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
            $resolucion->fecha=trim($request->fecha);
            $resolucion->estado =true;
            $resolucion->save();

            foreach ($request->cronogramas as $key => $value) {
                // return $value;
                $cronograma=Cronograma::find($value['idCronograma_carpeta']);
                $cronograma->idResolucion=$resolucion->idResolucion;
                $cronograma->update();
            }
            $resolucion->cronogramas=$request->cronogramas;
            // return $resolucion;
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
            $resolucion->update();

            //Eliminamos todas las relaciones de los cronogramas que pertenecen a esa resolucion
            $cronogramas=Cronograma::where('idResolucion',$id)->get();
            foreach ($cronogramas as $key => $value) {
                $value->idResolucion=null;
                $value->update();
            }
            // return $cronogramas;
            //agregamos las nuevas relaciones de cronogramas
            foreach ($request->cronogramas as $key => $value) {
                // return $value;
                $cronograma=Cronograma::find($value['idCronograma_carpeta']);
                $cronograma->idResolucion=$resolucion->idResolucion;
                $cronograma->update();
            }
            $resolucion->cronogramas=$request->cronogramas;
            // return $resolucion;
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
