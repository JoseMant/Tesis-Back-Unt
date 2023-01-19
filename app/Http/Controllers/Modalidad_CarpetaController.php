<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Modalidad_Carpeta;

class Modalidad_CarpetaController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function getModalidadGrado($idTipo_tramite_unidad){
        return Modalidad_Carpeta::where('idTipo_tramite_unidad',$idTipo_tramite_unidad)
        ->where('estado',1)->get();
    }
}
