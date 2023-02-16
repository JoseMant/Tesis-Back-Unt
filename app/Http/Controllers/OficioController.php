<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Oficio;

class OficioController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }
    
    public function index(){
        $oficios=Oficio::where('estado',1)
        ->get();
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
            
            if($request->hasFile("archivoPdf")){
                $file=$request->file("archivoPdf");
                // $nombre = $file->getClientOriginalName();
                $nombre = $request->nro_oficio.'.'.$file->guessExtension();
                $nombreBD = "/storage/oficios/".$nombre;
                if($file->guessExtension()=="pdf"){
                  $file->storeAs('public/oficios', $nombre);
                  $oficio->archivo = $nombreBD;
                }else {
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' => "Subir archivo del oficio en pdf"], 400);
                }
            }
            // $resolucion->archivo = "archivo";
            $oficio->estado =true;
            $oficio->save();
            
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
            
            if($request->hasFile("archivo")){
                $file=$request->file("archivo");
                $nombre = $request->nro_oficio.'.'.$file->guessExtension();
                $nombreBD = "/storage/oficios/".$nombre;
                if($file->guessExtension()=="pdf"){
                  $file->storeAs('public/oficios', $nombre);
                  $oficio->archivo = $nombreBD;
                }else {
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' => "Subir archivo del oficio en pdf"], 400);
                }
            }
            $oficio->estado =trim($request->estado);
            $oficio->update();
            
            DB::commit();
            return response()->json($oficio, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
}
