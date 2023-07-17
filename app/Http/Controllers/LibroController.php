<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Libro;
use App\DependenciaURAA;
use App\Unidad;

class LibroController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function getLibrosByTipoTramiteUnidad($idTipo_tramite_unidad){
        DB::beginTransaction();
        try {
            return $libros=Libro::where('idTipo_tramite_unidad',$idTipo_tramite_unidad)
            ->groupBy('nro_libro')
            ->pluck('nro_libro');
            DB::commit();
            return response()->json($libros, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
        
    }
}


