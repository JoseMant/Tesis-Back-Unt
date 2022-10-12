<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\DependenciaURAA;

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
}
