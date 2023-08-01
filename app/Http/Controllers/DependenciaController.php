<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\DependenciaURAA;
use App\Escuela;
use App\ProgramaURAA;
use App\Mencion;
use App\Programa;

class DependenciaController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function getDependenciasByUnidad($idUnidad){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];
        $idTipo_usuario=$apy['idTipo_usuario'];
        DB::beginTransaction();
        try {
            if ($idTipo_usuario==8) {
                if ($idUnidad==1) {
                    $dependencias=DependenciaURAA::where('idDependencia',$idDependencia)->get();
                }elseif ($idUnidad==4) {
                    $dependencias=DependenciaURAA::where('idDependencia2',$idDependencia)->get();
                }
                // $dependencias=DependenciaURAA::where('idDependencia',$idDependencia)->get();
            }else {
                $dependencias=DependenciaURAA::where('idUnidad',$idUnidad)->get();
            }
            DB::commit();
            return response()->json($dependencias, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
        
    }

    public function getEscuelas($id){
        $programas = ProgramaURAA::where('idDependencia',$id)
        ->where('estado',true)
        ->get();
        return response()->json($programas, 200);
    }

    public function getDependenciaByPrograma($idDependencia_detalle){
        $dependencia_detalle=Escuela::find($idDependencia_detalle);
        $dependencia=DependenciaURAA::find($dependencia_detalle->idDependencia);
        return response()->json($dependencia, 200);
    }

    public function index() 
    {
        $dependencias = DependenciaURAA::all();
        return response()->json($dependencias, 200);
    }
}
