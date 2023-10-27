<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\DependenciaSGA;
use App\Dependencia;
use Illuminate\Support\Facades\DB;


class DependenciaSGAController extends Controller
{   
    public function __construct()
    {
        $this->middleware('jwt');
    }
    public function DepartamentosByDependencia($idDependencia){
        // return $idDependencia;
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $departamentos = DependenciaSGA::where('tde_id', 16)->where('sdep_id',$idDependencia)->get();
        return response()->json($departamentos, 200);
        
    }
    public function index(){
        return DependenciaSGA::select('dependencia.*')->where('tde_id',16)->orderBy('dep_nombre','asc')->get();
    }

    public function getDependenciasSGA(){
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $dependencias=DependenciaSGA::where('tde_id', 2)->orderBy('dep_nombre','asc')->get();
        return response()->json($dependencias, 200);
    }
   

}
