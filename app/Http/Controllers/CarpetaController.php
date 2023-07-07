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
        // DB::raw("(case 
        //             when tramite.idUnidad = 1 then (select nombre from escuela where idEscuela=tramite.idDependencia_detalle)  
        //             when tramite.idUnidad = 4 then  (select denominacion from mencion where idMencion=tramite.idDependencia_detalle)
        //         end) as programa"),
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

    public function getCarpetaByCodigoDiploma(Request $request){
        $tramite=Tramite::select('tramite.idTramite','usuario.nombres','usuario.apellidos','usuario.nro_documento','tramite.sede','tipo_tramite_unidad.diploma_obtenido',
        'modalidad_carpeta.acto_academico as modalidadSustentancion','tramite_detalle.nro_libro','tramite_detalle.folio','tramite_detalle.nro_registro','resolucion.nro_resolucion',
        DB::raw("(case 
                    when tramite.idUnidad = 1 then dependencia.denominacion  
                    when tramite.idUnidad = 4 then  (select denominacion from dependencia d where d.idDependencia=dependencia.idDependencia2)
                end) as facultad"),
        'programa.nombre as programa', 'tramite.nro_matricula', 'diploma_carpeta.descripcion as denominacion', 'tramite_detalle.codigo_diploma','cronograma_carpeta.fecha_colacion')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa','programa.idPrograma','tramite.idPrograma')
        ->join('tramite_detalle', 'tramite_detalle.idTramite_detalle', 'tramite.idTramite_detalle')
        ->join('diploma_carpeta', 'tramite_detalle.idDiploma_carpeta', 'diploma_carpeta.idDiploma_carpeta')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite_detalle.idTipo_tramite_unidad')
        ->join('modalidad_carpeta','modalidad_carpeta.idModalidad_carpeta','tramite_detalle.idModalidad_carpeta')
        ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
        ->where('tramite_detalle.codigo_diploma', $request->codigo_diploma)
        ->first();

        if (!$tramite) return response()->json(['status' => '400', 'message' => "No se encuentra carpeta con ese código de diploma"], 400);

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
