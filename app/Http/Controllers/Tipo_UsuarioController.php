<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tipo_Usuario;

class Tipo_UsuarioController extends Controller
{
    public function GetRoles(){
        return $roles=Tipo_Usuario::where('idTipo_usuario','!=',1)->where('estado',1)
        ->orderBy('nombre','asc')->get();
        return response()->json($roles, 200);
    }
}
