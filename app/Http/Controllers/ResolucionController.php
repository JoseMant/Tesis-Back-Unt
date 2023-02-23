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
            $resolucion->cronogramas=Cronograma::select('cronograma_carpeta.*','dependencia.nombre','unidad.descripcion')
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
            
            // if($request->hasFile("archivoPdf")){
            //     $file=$request->file("archivoPdf");
            //     // $nombre = $file->getClientOriginalName();
            //     $nombre = $request->nro_resolucion;
            //     $nombreBD = "/storage/resoluciones/".$nombre;
            //     if($file->guessExtension()=="pdf"){
            //       $file->storeAs('public/resoluciones', $nombre);
            //       $resolucion->archivo = $nombreBD;
            //     }else {
            //         DB::rollback();
            //         return response()->json(['status' => '400', 'message' => "Subir archivo del comprobante de pago en pdf"], 400);
            //     }
            // }
            // $resolucion->archivo = "archivo";
            $resolucion->estado =true;
            $resolucion->save();
            
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
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
           
            $resolucion=Resolucion::find($id);
            $resolucion->nro_resolucion=trim($request->nro_resolucion);
            $resolucion->fecha=trim($request->fecha);
            
            // if($request->hasFile("archivo")){
            //     // return "ingresé al voucher";
            //     $file=$request->file("archivo");
            //     $nombre = $file->getClientOriginalName();
            //     $nombreBD = "/storage/resoluciones/".$nombre;
            //     if($file->guessExtension()=="pdf"){
            //       $file->storeAs('public/resoluciones', $nombre);
            //       $resolucion->archivo = $nombreBD;
            //     }else {
            //         DB::rollback();
            //         return response()->json(['status' => '400', 'message' => "Subir archivo del comprobante de pago en pdf"], 400);
            //     }
            // }

            // $resolucion->archivo = "archivo editado";
            $resolucion->estado =trim($request->estado);
            $resolucion->update();
            
            DB::commit();
            return response()->json($resolucion, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
}
