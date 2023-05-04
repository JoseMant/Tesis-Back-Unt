<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Escuela;
use App\User;
use App\Tramite;
use App\Tramite_Detalle;
use App\Diploma_Carpeta;
use Illuminate\Support\Facades\DB;

class CarpetaController extends Controller
{
    public function getDataPersona(Request $request){
        $tramite=Tramite::select('usuario.nombres','usuario.apellidos','usuario.nro_documento','tramite.sede','dependencia.nombre as Facultad','tramite.nro_matricula', 'tramite.idTramite_detalle', 'tramite.idDependencia_detalle','tramite.idUnidad','tramite_detalle.idDiploma_carpeta', 'tramite_detalle.codigo_certificado', 'cronograma_carpeta.fecha_colacion')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('tramite_detalle', 'tramite_detalle.idTramite_detalle', 'tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->where('tramite.idTramite', $request->id)
        ->first();

        if($tramite->idUnidad == 1){
            $escuela=Escuela::select('escuela.nombre')
            ->where('escuela.idEscuela', $tramite->idDependencia_detalle)
            ->first();
            $tramite->escuela=$escuela->nombre;

            $diploma=Diploma_Carpeta::select('diploma_carpeta.descripcion')
            ->where('diploma_carpeta.idDiploma_carpeta', $tramite->idDiploma_carpeta)
            ->first();
            $tramite->diploma=$diploma->descripcion;
            
        }
        
        // else {
        //     $mencion=Mencion
        //     $tramite->mencion=$mencion->nombre;
        // }
   
        
        return $tramite;
        // ->where(function ($query){
        //     $query->where('tramite.idTipo_tramite_unidad', 15)
        //           ->orWhere('tramite.idTipo_tramite_unidad', 16);
        //         })
    }
}
