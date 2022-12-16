<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Programa_Estudios_Carpeta;

class Programa_Estudios_CarpetaController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }
    public function getProgramaEstudios(){
        return Programa_Estudios_Carpeta::where('estado',1)->get();
    }
}
