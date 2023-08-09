<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tipo_Documento;

class Tipo_DocumentoController extends Controller
{
    public function GetTipos_documentos(){
        return $tipos_documentos=Tipo_Documento::where('idTipo_documento','!=',0)
        ->get();
        return response()->json($tipos_documentos, 200);
    }
}
