<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Oficio;
use App\Resolucion;

class OficioController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }
    
    public function index(){
        $oficios=Oficio::where('estado',1)
        ->get();
        foreach ($oficios as $key => $oficio) {
            $oficio->resoluciones=Resolucion::join('tipo_resolucion','tipo_resolucion.idTipo_resolucion','resolucion.idTipo_resolucion')
            ->where('resolucion.idOficio',$oficio->idOficio)->where('resolucion.estado',1)->get();
        }
        return response()->json($oficios, 200);
    }

    public function store(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
           
            $oficioValidate=Oficio::where('nro_oficio',$request->nro_oficio)->first();
            if ($oficioValidate) {
                return response()->json( ['status'=>400,'message'=>'El oficio ya se encuentra registrado'],400);
            }
            $oficio=new Oficio;
            $oficio->nro_oficio=trim($request->nro_oficio);
            $oficio->fecha=trim($request->fecha);
            $oficio->estado =true;
            $oficio->save();
            
            foreach ($request->resoluciones as $key => $value) {
                // return $value;
                $resolucion=Resolucion::find($value['idResolucion']);
                $resolucion->idOficio=$oficio->idOficio;
                $resolucion->update();
            }
            $oficio->resoluciones=$request->resoluciones;

            DB::commit();
            return response()->json($oficio, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request,$id){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
           
            $oficio=Oficio::find($id);
            $oficio->nro_oficio=trim($request->nro_oficio);
            $oficio->fecha=trim($request->fecha);
            $oficio->estado =trim($request->estado);
            $oficio->update();
            
            //Eliminamos todas las relaciones de los resoluciones que pertenecen a ese oficio
            $resoluciones=Resolucion::where('idOficio',$id)->get();
            foreach ($resoluciones as $key => $value) {
                $value->idOficio=null;
                $value->update();
            }
            //agregamos las nuevas relaciones de resoluciones
            foreach ($request->resoluciones as $key => $value) {
                // return $value;
                $resolucion=Resolucion::find($value['idResolucion']);
                $resolucion->idOficio=$oficio->idOficio;
                $resolucion->update();
            }
            $oficio->resoluciones=$request->resoluciones;
            
            DB::commit();
            return response()->json($oficio, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
}
