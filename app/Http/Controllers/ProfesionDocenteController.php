<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProfesionDocente;
use App\UsuarioSUNT;

class ProfesionDocenteController extends Controller
{
    public function index()
    {
        return ProfesionDocente::select('pon_id','pon_nombre')->get();
    }

    public function prueba(){
        $hola=date('Y-m-d');
        echo $hola;
    }

}
