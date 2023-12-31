<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Escuela;
use App\User;
use App\Tramite;
use App\Tramite_Detalle;
use App\Resolucion;
use App\Cronograma;
use App\Diploma_Carpeta;
use App\Tramite_Requisito;
use App\Historial_Estado;
use App\Graduado;
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
                if ($cronograma->visible) {
                    $cronograma->visible=false;
                    $cronograma->save();
                    // buscando el cronograma más cercano para activarlo
                    $cronogramasSig=Cronograma::where('idDependencia',$cronograma->idDependencia)
                    ->where('idTipo_tramite_unidad',$cronograma->idTipo_tramite_unidad)
                    ->where('fecha_colacion','>',$cronograma->fecha_colacion)
                    ->orderBy('fecha_colacion') //Si no se pone el order, traerá todos las colaciones mayores, pero la más próxima será por id y no por fecha de colación.   
                    ->first();
                    if ($cronogramasSig) {
                        $cronogramasSig->visible=true;
                        $cronogramasSig->save();
                    }
                }
            }

            if ($resolucion->tipo_emision=='O') {
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
            }else {
                // Obteniendo todas las carpetas que se van a finalizar
                $tramites=Tramite::select('tramite.idTramite','tramite.idEstado_tramite')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('resolucion', 'resolucion.idResolucion', 'tramite_detalle.idResolucion_rectoral')
                ->where('tramite.idEstado_tramite',44)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',42)
                    ->orWhere('tramite.idTipo_tramite_unidad',43)
                    ->orWhere('tramite.idTipo_tramite_unidad',44)
                    ->orWhere('tramite.idTipo_tramite_unidad',47)
                    ->orWhere('tramite.idTipo_tramite_unidad',48)
                    ->orWhere('tramite.idTipo_tramite_unidad',49);
                })
                ->where('resolucion.idResolucion',$resolucion->idResolucion)
                ->get();
            }

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

        if ($resolucion->tipo_emision=='O') {
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.idEstado_tramite', 'tramite.nro_tramite','tramite.nro_matricula', 'tramite.exonerado_archivo', 
            'tramite.idTipo_tramite_unidad', 'tramite_detalle.codigo_diploma', 'tramite_detalle.diploma_final',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 
            'resolucion.idResolucion','tramite_detalle.*','tramite.sede','tramite.created_at as fecha','usuario.nro_documento','tramite.uuid',
            'voucher.archivo as voucher','tipo_tramite_unidad.idTipo_tramite')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
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
        }else {
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.idEstado_tramite', 'tramite.nro_tramite','tramite.nro_matricula', 'tramite.exonerado_archivo', 
            'tramite.idTipo_tramite_unidad', 'tramite_detalle.codigo_diploma', 'tramite_detalle.diploma_final',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 
            'resolucion.idResolucion','tramite_detalle.*','tramite.sede','tramite.created_at as fecha','usuario.nro_documento','tramite.uuid',
            'voucher.archivo as voucher','tipo_tramite_unidad.idTipo_tramite')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('resolucion', 'resolucion.idResolucion', 'tramite_detalle.idResolucion_rectoral')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idEstado_tramite',15)
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
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
            ->join('resolucion', 'resolucion.idResolucion', 'tramite_detalle.idResolucion_rectoral')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->where('tramite.idEstado_tramite',15)
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
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
        }
        
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();

            $tramite->fut="fut/".$tramite->uuid;
        }

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
        $diplomas = [];
        $tramites=Tramite::select('tramite.idTramite','usuario.nombres','usuario.apellidos','usuario.nro_documento','tramite.sede',
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
                $query->where('usuario.nro_documento', 'LIKE', '%'.$request->query('search').'%');
            } else if ($request->query('tipo')=="apellidos") {
                $query->where('usuario.apellidos', 'LIKE', '%'.$request->query('search').'%');
            } else if ($request->query('tipo')=="nombres") {
                $query->where('usuario.nombres', 'LIKE', '%'.$request->query('search').'%');
            } else {
                return response()->json(['status' => '400', 'message' => "Búsqueda incorrecta"], 400);
            }
        })
        ->get();
        if (count($tramites)) {
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
                array_push($diplomas, $tramite);
            }
        }

        $tramites_diploma = Graduado::select('graduado.idgraduado as idTramite', 'alumno.Nom_alumno as nombres', DB::raw("CONCAT(alumno.Pat_alumno,' ',alumno.Mat_alumno) AS apellidos"), 
        'alumno.Nro_documento as nro_documento', 'sedes.Des_sede as sede', 'tipoficha.Nom_ficha as tipo_tramite', 'actoacad.Nom_acto as modalidadSustentancion',
        'graduado.num_libro as nro_libro', 'graduado.num_folio as folio', 'graduado.num_registro as nro_registro', 'graduado.num_reso_r as nro_resolucion',
        'facultad.Nom_facultad as facultad', 'escuela.Nom_escuela as programa', 'graduado.cod_alumno as nro_matricula', 
        'diplomas.Des_diploma_h as denominacion', // Corregir para diplomas masculino y femenino
        'graduado.cod_ficha as codigo_diploma',
        'graduado.fec_expe_d as fecha_colacion', //Validar si es fecha de colación
        //Detectar cuando es original y duplicado
        'graduado.grad_foto as foto' //Configurar para leer fotos
        )
        
        ->join('alumno', 'alumno.Cod_alumno', 'graduado.cod_alumno')
        ->join('sedes', 'sedes.Cod_general', 'alumno.Cod_sede')
        ->join('tipoficha','tipoficha.Tip_ficha','graduado.tipo_ficha')
        ->join('actoacad','actoacad.Cod_acto','graduado.cod_acto')
        ->join('escuela','escuela.Cod_escuela','alumno.Cod_escuela')
        ->join('facultad','facultad.Cod_facultad','escuela.Cod_facultad')
        ->join('diplomas', 'diplomas.Cod_diploma', 'graduado.Cod_diploma')
        ->where(function($query) use ($request)
        {
            if ($request->query('tipo')=="codigo_diploma") {
                $query->where('graduado.cod_ficha', 'LIKE', $request->query('search'));
            } else if ($request->query('tipo')=="nro_documento") {
                $query->where('alumno.Nro_documento', 'LIKE', '%'.$request->query('search').'%');
            } else if ($request->query('tipo')=="apellidos") {
                $query->where('alumno.Pat_alumno', 'LIKE', '%'.$request->query('search').'%')
                ->orWhere('alumno.Mat_alumno', 'LIKE', '%'.$request->query('search').'%');
            } else if ($request->query('tipo')=="nombres") {
                $query->where('alumno.Nom_alumno', 'LIKE', '%'.$request->query('search').'%');
            } else {
                return response()->json(['status' => '400', 'message' => "Búsqueda incorrecta"], 400);
            }
        })
        ->get();
        if (count($tramites_diploma)) {
            foreach ($tramites_diploma as $key => $tramite) {
                array_push($diplomas, $tramite);
            }
        }

        if (!$diplomas) return response()->json(['status' => '400', 'message' => "No se encuentra carpeta con esa búsqueda"], 400);

        return $diplomas;
    }

    public function setHistorialEstado($idTramite, $idEstado_actual, $idEstado_nuevo, $idUsuario)
    {
        $historial_estados = new Historial_Estado;
        $historial_estados->idTramite = $idTramite;
        $historial_estados->idEstado_actual = $idEstado_actual;
        $historial_estados->idEstado_nuevo = $idEstado_nuevo;
        $historial_estados->idUsuario = $idUsuario;
        $historial_estados->fecha = date('Y-m-d h:i:s');
        return $historial_estados;
    }
}
