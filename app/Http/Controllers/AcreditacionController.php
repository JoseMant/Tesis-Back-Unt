<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Acreditacion;
use App\Escuela;
class AcreditacionController extends Controller
{
    public function index(){
        $acreditaciones=Acreditacion::select('acreditacion.*','dependencia.nombre as dependencia','unidad.descripcion as unidad','unidad.idUnidad')
        ->join('dependencia','acreditacion.idDependencia','dependencia.idDependencia')
        ->join('unidad','acreditacion.idUnidad','unidad.idUnidad')
        // ->join(function($query)
        //     {
        //         if ($query->idUnidad==1) {
        //             $query->join('escuela','acreditacion.idDependencia_detalle','escuela.idEscuela');
        //         }
        //     })
        ->where('acreditacion.estado',1)
        ->get();
        foreach ($acreditaciones as $key => $acreditacion) {
            if ($acreditacion->idUnidad==1) {
                $escuela=Escuela::find($acreditacion->idDependencia_detalle);
                $acreditacion->escuela=$escuela->nombre;
            }
        }
        DB::commit();
        return response()->json($acreditaciones, 200);
    }


    public function store(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
           
            $acreditada_validate=Acreditacion::where('idUnidad',$request->idUnidad)
            ->where('idDependencia',$request->idDependencia)->where('idDependencia_detalle',$request->idDependencia_detalle)
            ->first();

            if ($acreditada_validate) {
                return response()->json(['status' => '400', 'message' => 'Esta resolución ya se encuentra registrada'], 400);
            }
            $acreditada=new Acreditacion;
            $acreditada->idUnidad=trim($request->idUnidad);
            $acreditada->idDependencia=trim($request->idDependencia);
            $acreditada->idDependencia_detalle=trim($request->idDependencia_detalle);
            $acreditada->fecha_inicio=trim($request->fecha_inicio);
            $acreditada->fecha_fin=trim($request->fecha_fin);
            $acreditada->empresa_acreditadora=trim($request->empresa_acreditadora);
            $acreditada->save();
            
            DB::commit();
            return response()->json($acreditada, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
}
