<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Diploma_Carpeta;

class Diploma_CarpetaController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function getDiplomaCarpetas($idUnidad,$idTipo_tramite_unidad,$idPrograma){
        return Diploma_Carpeta::where('idUnidad',$idUnidad)
        ->where('idTipo_tramite_unidad',$idTipo_tramite_unidad)
        ->where('idPrograma',$idPrograma)
        ->where('estado',1)
        ->get();
    }
}
