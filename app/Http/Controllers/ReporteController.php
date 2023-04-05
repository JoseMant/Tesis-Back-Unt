<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Tramite;
use App\Tramite_Requisito;
use App\Escuela;
use App\Mencion;
use App\DependenciaURAA;


class ReporteController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }
    public function enviadoFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $dni=$apy['nro_documento'];
        $idTipo_usuario=$apy['idTipo_usuario'];
        $idDependencia_usuario=$apy['idDependencia'];
        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'historial_estado.fecha as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final','tramite_detalle.codigo_diploma',
            'tramite_detalle.impresion'/*,'resolucion.nro_resolucion'*/,'tramite_detalle.nro_libro','tramite_detalle.folio'
            ,'tramite_detalle.nro_registro','tramite_detalle.observacion_diploma','tramite.idEstado_tramite','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where('historial_estado.idEstado_actual',31)
            ->where('historial_estado.idEstado_nuevo',34)
            ->where(function($query) use ($idTipo_usuario,$idDependencia_usuario)
            {
                if ($idDependencia_usuario) {
                    if ($idTipo_usuario==5) {
                        $query->where('tramite.idDependencia_detalle',$idDependencia_usuario);
                    }elseif($idTipo_usuario==17){
                        $query->where('tramite.idDependencia',$idDependencia_usuario);
                    }
                }
            })
            // ->where('tramite.idDependencia_detalle',$idDependencia_usuario)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();

            
            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('historial_estado.idEstado_actual',31)
            ->where('historial_estado.idEstado_nuevo',34)
            ->where(function($query) use ($idTipo_usuario,$idDependencia_usuario)
            {
                if ($idDependencia_usuario) {
                    if ($idTipo_usuario==5) {
                        $query->where('tramite.idDependencia_detalle',$idDependencia_usuario);
                    }elseif($idTipo_usuario==17){
                        $query->where('tramite.idDependencia',$idDependencia_usuario);
                    }
                }
            })
            ->count();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'historial_estado.fecha as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final','tramite_detalle.codigo_diploma',
            'tramite_detalle.impresion'/*,'resolucion.nro_resolucion'*/,'tramite_detalle.nro_libro','tramite_detalle.folio'
            ,'tramite_detalle.nro_registro','tramite_detalle.observacion_diploma','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('historial_estado.idEstado_actual',31)
            ->where('historial_estado.idEstado_nuevo',34)
            ->where(function($query) use ($idTipo_usuario,$idDependencia_usuario)
            {
                if ($idDependencia_usuario) {
                    if ($idTipo_usuario==5) {
                        $query->where('tramite.idDependencia_detalle',$idDependencia_usuario);
                    }elseif($idTipo_usuario==17){
                        $query->where('tramite.idDependencia',$idDependencia_usuario);
                    }
                }
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get(); 

            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('historial_estado.idEstado_actual',31)
            ->where('historial_estado.idEstado_nuevo',34)
            ->where(function($query) use ($idTipo_usuario,$idDependencia_usuario)
            {
                if ($idDependencia_usuario) {
                    if ($idTipo_usuario==5) {
                        $query->where('tramite.idDependencia_detalle',$idDependencia_usuario);
                    }elseif($idTipo_usuario==17){
                        $query->where('tramite.idDependencia',$idDependencia_usuario);
                    }
                }
            })
            ->count();
        }

        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200', 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }

    public function enviadoUra(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $dni=$apy['nro_documento'];
        // $idTipo_usuario=$apy['idTipo_usuario'];
        $idDependencia_usuario=$apy['idDependencia'];
        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'historial_estado.fecha as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final','tramite_detalle.codigo_diploma',
            'tramite_detalle.impresion'/*,'resolucion.nro_resolucion'*/,'tramite_detalle.nro_libro','tramite_detalle.folio'
            ,'tramite_detalle.nro_registro','tramite_detalle.observacion_diploma','tramite.idEstado_tramite','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where('historial_estado.idEstado_actual',38)
            ->where('historial_estado.idEstado_nuevo',7)
            ->where(function($query) use ($idDependencia_usuario)
            {
                if ($idDependencia_usuario) {
                    $query->where('tramite.idDependencia',$idDependencia_usuario)
                    ->orWhere('dependencia.idDependencia2',$idDependencia_usuario);
                }
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();

            
            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('historial_estado.idEstado_actual',38)
            ->where('historial_estado.idEstado_nuevo',7)
            ->where(function($query) use ($idDependencia_usuario)
            {
                if ($idDependencia_usuario) {
                    $query->where('tramite.idDependencia',$idDependencia_usuario)
                    ->orWhere('dependencia.idDependencia2',$idDependencia_usuario);
                }
            })
            ->count();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'historial_estado.fecha as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final','tramite_detalle.codigo_diploma',
            'tramite_detalle.impresion'/*,'resolucion.nro_resolucion'*/,'tramite_detalle.nro_libro','tramite_detalle.folio'
            ,'tramite_detalle.nro_registro','tramite_detalle.observacion_diploma','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('historial_estado.idEstado_actual',38)
            ->where('historial_estado.idEstado_nuevo',7)
            ->where(function($query) use ($idDependencia_usuario)
            {
                if ($idDependencia_usuario) {
                    $query->where('tramite.idDependencia',$idDependencia_usuario)
                    ->orWhere('dependencia.idDependencia2',$idDependencia_usuario);
                }
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get(); 

            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('historial_estado.idEstado_actual',38)
            ->where('historial_estado.idEstado_nuevo',7)
            ->where(function($query) use ($idDependencia_usuario)
            {
                if ($idDependencia_usuario) {
                    $query->where('tramite.idDependencia',$idDependencia_usuario)
                    ->orWhere('dependencia.idDependencia2',$idDependencia_usuario);
                }
            })
            ->count();
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->idTramite;
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->programa=$dependenciaDetalle->nombre;
        }
        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200', 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }

    public function enviadoSecretariaGeneral(Request $request){
        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final','tramite_detalle.codigo_diploma',
            'tramite_detalle.impresion'/*,'resolucion.nro_resolucion'*/,'tramite_detalle.nro_libro','tramite_detalle.folio'
            ,'tramite_detalle.nro_registro','tramite_detalle.observacion_diploma','tramite.idEstado_tramite','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where('historial_estado.idEstado_actual',39)
            ->where('historial_estado.idEstado_nuevo',41)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();

            
            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('historial_estado.idEstado_actual',39)
            ->where('historial_estado.idEstado_nuevo',41)
            ->count();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final','tramite_detalle.codigo_diploma',
            'tramite_detalle.impresion'/*,'resolucion.nro_resolucion'*/,'tramite_detalle.nro_libro','tramite_detalle.folio'
            ,'tramite_detalle.nro_registro','tramite_detalle.observacion_diploma','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('historial_estado.idEstado_actual',39)
            ->where('historial_estado.idEstado_nuevo',41)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get(); 

            
            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('historial_estado.idEstado_actual',39)
            ->where('historial_estado.idEstado_nuevo',41)
            ->count();
        }

        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200', 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }


    public function reporteCarpeta(Request $request){
        // return $request->all();
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $dni=$apy['nro_documento'];
        $idTipo_usuario=$apy['idTipo_usuario'];
        $idDependencia=$apy['idDependencia'];
        if ($idTipo_usuario==5) {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','tramite.idUnidad','tipo_tramite.idTipo_tramite'
            ,'cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where(function($query) use($request, $idDependencia)
            {
                if ($request->idTipo_tramite_unidad!=0) {
                    $query->where('tramite.idTipo_tramite_unidad',$request->idTipo_tramite_unidad);
                }
                if ($idDependencia) {
                    $query->where('tramite.idDependencia_detalle',$idDependencia);
                }
                if ($request->cronograma!=0) {
                    $query->where('cronograma_carpeta.fecha_colacion',$request->cronograma);
                }
            })
            ->orderBy('usuario.apellidos','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();

            // TRÁMITES POR USUARIO
            $total=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','tramite.idUnidad','tipo_tramite.idTipo_tramite'
            ,'cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where(function($query) use($request, $idDependencia)
            {
                if ($request->idTipo_tramite_unidad!=0) {
                    $query->where('tramite.idTipo_tramite_unidad',$request->idTipo_tramite_unidad);
                }
                if ($idDependencia) {
                    $query->where('tramite.idDependencia_detalle',$idDependencia);
                }
                if ($request->cronograma!=0) {
                    $query->where('cronograma_carpeta.fecha_colacion',$request->cronograma);
                }
            })
            ->orderBy('usuario.apellidos','asc')
            ->count();
        }elseif ($idTipo_usuario==8) {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','tramite.idUnidad','tipo_tramite.idTipo_tramite'
            ,'cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where(function($query) use($request, $idDependencia)
            {
                if ($request->idTipo_tramite_unidad!=0) {
                    $query->where('tramite.idTipo_tramite_unidad',$request->idTipo_tramite_unidad);
                }
                if ($request->idDependencia_detalle!=0) {
                    $query->where('tramite.idDependencia_detalle',$request->idDependencia_detalle);
                }
                if ($request->cronograma!=0) {
                    $query->where('cronograma_carpeta.fecha_colacion',$request->cronograma);
                }
                if ($request->idUnidad!=0) {
                    if ($request->idUnidad==1) {
                        $query->where('tramite.idDependencia',$idDependencia);
                    }elseif ($request->idUnidad==4) {
                        $query->where('dependencia.idDependencia2',$idDependencia);
                        
                    }
                }else {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->orderBy('usuario.apellidos','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();

            // TRÁMITES POR USUARIO
            $total=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','tramite.idUnidad','tipo_tramite.idTipo_tramite'
            ,'cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where(function($query) use($request, $idDependencia)
            {
                if ($request->idTipo_tramite_unidad!=0) {
                    $query->where('tramite.idTipo_tramite_unidad',$request->idTipo_tramite_unidad);
                }
                if ($request->idDependencia_detalle!=0) {
                    $query->where('tramite.idDependencia_detalle',$request->idDependencia_detalle);
                }
                if ($request->cronograma!=0) {
                    $query->where('cronograma_carpeta.fecha_colacion',$request->cronograma);
                }
                if ($request->idUnidad!=0) {
                    if ($request->idUnidad==1) {
                        $query->where('tramite.idDependencia',$idDependencia);
                    }elseif ($request->idUnidad==4) {
                        $query->where('dependencia.idDependencia2',$idDependencia);
                        
                    }
                }else {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->orderBy('usuario.apellidos','asc')
            ->count();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','tramite.idUnidad','tipo_tramite.idTipo_tramite'
            ,'cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where(function($query) use($request)
            {
                if ($request->idUnidad!=0) {
                    $query->where('tramite.idUnidad',$request->idUnidad);
                }
                if ($request->idDependencia!=0) {
                    $query->where('tramite.idDependencia',$request->idDependencia);
                }
                if ($request->idDependencia_detalle!=0) {
                    $query->where('tramite.idDependencia_detalle',$request->idDependencia_detalle);
                }
                if ($request->idTipo_tramite_unidad!=0) {
                    $query->where('tramite.idTipo_tramite_unidad',$request->idTipo_tramite_unidad);
                }
                if ($request->cronograma!=0) {
                    $query->where('cronograma_carpeta.fecha_colacion',$request->cronograma);
                }
            })
            ->orderBy('usuario.apellidos','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();


            // TRÁMITES POR USUARIO
            $total=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','tramite.idUnidad','tipo_tramite.idTipo_tramite'
            ,'cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite','estado_tramite.descripcion as estado')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where(function($query) use($request)
            {
                if ($request->idUnidad!=0) {
                    $query->where('tramite.idUnidad',$request->idUnidad);
                }
                if ($request->idDependencia!=0) {
                    $query->where('tramite.idDependencia',$request->idDependencia);
                }
                if ($request->idDependencia_detalle!=0) {
                    $query->where('tramite.idDependencia_detalle',$request->idDependencia_detalle);
                }
                if ($request->idTipo_tramite_unidad!=0) {
                    $query->where('tramite.idTipo_tramite_unidad',$request->idTipo_tramite_unidad);
                }
                if ($request->cronograma!=0) {
                    $query->where('cronograma_carpeta.fecha_colacion',$request->cronograma);
                }
            })
            ->count();
        }

        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200', 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }


    public function getDependencias_Detalle($idDependencia){
        $dependencia=DependenciaURAA::find($idDependencia);
        if ($dependencia) {
            if ($dependencia->idUnidad==1) {
                $dependencias_detalle=Escuela::select('idEscuela as idDependencia_detalle','nombre')->where('idDependencia',$idDependencia)->where('estado',1)->get();
            }elseif ($dependencia->idUnidad==4) {
                $dependencias_detalle=Mencion::select('idMencion as idDependencia_detalle','nombre')->where('idDependencia',$idDependencia)->where('estado',1)->get();
            }
            return response()->json($dependencias_detalle, 200);
        }else {
            // return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
            return response()->json(['status' => '400', 'message' =>"Dependencia no encontrada"], 400);
        }
    }
}
