<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Escuela;
use App\User;
use App\Tramite;
use App\Tramite_Detalle;
use App\Resolucion;
use App\Diploma_Carpeta;
use App\Tramite_Requisito;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class CarpetaController extends Controller
{

    public function finalizarCarpetas(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            // Obteniendo la resolución que se va a finalizar
            $resolucion=Resolucion::find($request->idResolucion);
            
            //Obteniendo los cronogramas que se van a finalizar
            $cronogramas=Cronograma::where('idResolucion',$resolucion->idResolucion)->get();
            foreach ($cronogramas as $cronograma) {
                $cronograma->visible=false;
                $cronograma->save();
                // buscando el cronograma más cercano para activarlo
                $cronogramasSig=Cronograma::where('idDependencia',$cronograma->idDependencia)
                ->where('idTipo_tramite_unidad',$cronograma->idTipo_tramite_unidad)
                ->where('fecha_colacion','>',$cronograma->fecha_colacion)
                ->orderBy('fecha_colacion') //Si no se pone el order, traerá todos las colaciones mayores, pero la más próxima será por id y no por fecha de colación.   
                ->first();
                $cronogramasSig->visible=true;
                $cronogramasSig->save();
            }

            // Obteniendo todas las carpetas que se van a finalizar
            $tramites=Tramite::select('tramite.idTramite','tramite.idEstado_tramite')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',44)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->get();

            foreach ($tramites as $tramite) {
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 15, $idUsuario);
                $historial_estado->save();
                $tramite->idEstado_tramite=15;
                $tramite->save();
            }

            DB::commit();
            return response()->json($tramites, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function getFinalizados(Request $request,$idResolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        $resolucion=Resolucion::find($idResolucion);

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
        'tramite.idEstado_tramite', 'tramite.nro_tramite','tramite.nro_matricula', 'tramite.exonerado_archivo', 
        'tramite.idTipo_tramite_unidad', 'tramite_detalle.codigo_diploma', 'tramite_detalle.diploma_final',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 
        'resolucion.idResolucion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->where('tramite.idEstado_tramite',15)
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',15)
            ->orWhere('tramite.idTipo_tramite_unidad',16)
            ->orWhere('tramite.idTipo_tramite_unidad',34);
        })
        ->where('resolucion.idResolucion',$resolucion->idResolucion)
        ->where(function($query) use ($request)
        {
            $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->orderBy('tramite.idTipo_tramite_unidad','asc')
        ->orderBy('dependencia.nombre','asc')
        ->orderBy('programa.nombre','asc')
        ->orderBy('usuario.apellidos','asc')
        ->orderBy('usuario.nombres','asc')
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->where('tramite.idEstado_tramite',15)
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',15)
            ->orWhere('tramite.idTipo_tramite_unidad',16)
            ->orWhere('tramite.idTipo_tramite_unidad',34);
        })
        ->where('resolucion.idResolucion',$resolucion->idResolucion)
        ->where(function($query) use ($request)
        {
            $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->count();
        
        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200', 'data' =>$tramites,'resolucion' =>$resolucion,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }

    public function getDataPersona(Request $request){
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
        ->where('tramite.idTramite', $request->id)
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

    public function getCarpetaBySearch(Request $request){
        return $tramites=Tramite::select('tramite.idTramite','usuario.nombres','usuario.apellidos','usuario.nro_documento','tramite.sede',
        'tipo_tramite_unidad.descripcion as tipo_tramite',
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
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('modalidad_carpeta','modalidad_carpeta.idModalidad_carpeta','tramite_detalle.idModalidad_carpeta')
        ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
        ->where('tipo_tramite_unidad.idTipo_tramite', 2)
        ->where(function($query) use ($request)
        {
            if ($request->query('tipo')=="codigo_diploma") {
                $query->where('tramite_detalle.codigo_diploma', 'LIKE', $request->query('search'));
            } else if ($request->query('tipo')=="nro_documento") {
                $query->where('usuario.nro_documento', 'LIKE', '%'.$request->query('search'));
            } else if ($request->query('tipo')=="apellidos") {
                $query->where('usuario.apellidos', 'LIKE', '%'.$request->query('search').'%');
            } else if ($request->query('tipo')=="nombres") {
                $query->where('usuario.nombres', 'LIKE', '%'.$request->query('search').'%');
            } else {
                return response()->json(['status' => '400', 'message' => "Búsqueda incorrecta"], 400);
            }
        })
        ->get();

        if (!$tramite) return response()->json(['status' => '400', 'message' => "No se encuentra carpeta con esa búsqueda"], 400);

        foreach ($tramites as $key => $tramite) {
            $requisito=Tramite_Requisito::select('tramite_requisito.archivo')
            ->where(function($query) {
                $query->where('tramite_requisito.idRequisito',15)
                ->orWhere('tramite_requisito.idRequisito',23)
                ->orWhere('tramite_requisito.idRequisito',44)
                ->orWhere('tramite_requisito.idRequisito',52)
                ->orWhere('tramite_requisito.idRequisito',61);
            })
            ->where('idTramite',$tramite->idTramite)
            ->first();
    
            $tramite->foto=$requisito->archivo;
        }

        return $tramites;
    }
}
