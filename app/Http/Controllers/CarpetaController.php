<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Escuela;
use App\User;
use App\Tramite;
use App\Tramite_Detalle;
use App\Diploma_Carpeta;
use App\Tramite_Requisito;
use Illuminate\Support\Facades\DB;

class CarpetaController extends Controller
{
    public function getDataPersona(Request $request){
        $tramite=Tramite::select('tramite.idTramite','usuario.nombres','usuario.apellidos','usuario.nro_documento','tramite.sede',
        DB::raw("(case 
                    when tramite.idUnidad = 1 then dependencia.denominacion  
                    when tramite.idUnidad = 4 then  (select denominacion from dependencia d where d.idDependencia=dependencia.idDependencia2)
                end) as facultad"),
        DB::raw("(case 
                    when tramite.idUnidad = 1 then (select nombre from escuela where idEscuela=tramite.idDependencia_detalle)  
                    when tramite.idUnidad = 4 then  (select denominacion from mencion where idMencion=tramite.idDependencia_detalle)
                end) as programa"),
        'tramite.nro_matricula', 'diploma_carpeta.descripcion as denominacion', 'tramite_detalle.codigo_diploma','cronograma_carpeta.fecha_colacion')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('tramite_detalle', 'tramite_detalle.idTramite_detalle', 'tramite.idTramite_detalle')
        ->join('diploma_carpeta', 'tramite_detalle.idDiploma_carpeta', 'diploma_carpeta.idDiploma_carpeta')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->where('tramite.idTramite', $request->id)
        ->first();


        $requisito=Tramite_Requisito::select('tramite_requisito.archivo')
        ->where(function($query){
            $query->where('tramite_requisito.idRequisito',15)
            ->orWhere('tramite_requisito.idRequisito',23)
            ->orWhere('tramite_requisito.idRequisito',44)
            ->orWhere('tramite_requisito.idRequisito',52)
            ->orWhere('tramite_requisito.idRequisito',61);
        })
        ->where('idTramite',$tramite->idTramite)
        ->first();

        $tramite->foto=$requisito->archivo;
        return $tramite;
    }
}
