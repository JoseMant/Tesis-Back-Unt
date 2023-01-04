<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\DependenciaURAA;
use App\Escuela;
use App\Mencion;
use App\Programa;

class DependenciaController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }
    public function getDependenciasByUnidad($idUnidad){
        DB::beginTransaction();
        try {
            $dependencias=DependenciaURAA::where('idUnidad',$idUnidad)->get();
            DB::commit();
            return response()->json($dependencias, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
        
    }


    public function getEscuelas($id){
        $dependencia=DependenciaURAA::find($id);
        if ($dependencia->idUnidad==1) {
            $escuelas =Escuela::where('idDependencia',$id)->where('estado',true)->get();
        }else if ($dependencia->idUnidad==2) {
            
        }else if ($dependencia->idUnidad==3) {
            
        }else{
        }
        return response()->json($escuelas, 200);
    }
}
