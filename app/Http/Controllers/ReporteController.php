<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Tramite;
use App\Tramite_Requisito;
use App\ProgramaURAA;
use App\Mencion;
use App\DependenciaURAA;
use App\Tramite_Detalle;
use Codedge\Fpdf\Fpdf\Fpdf;
use App\Exports\ReporteGradoExport;
use Maatwebsite\Excel\Facades\Excel;


class ReporteController extends Controller
{
    protected $pdf;

    public function __construct(\App\PDF_Fut $pdf)
    {
        $this->pdf = $pdf;
        $this->middleware('jwt', ['except' => ['expedientesPDF','crearExcel']]);
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
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'historial_estado.fecha as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
                        $query->where('tramite.idPrograma',$idDependencia_usuario);
                    }elseif($idTipo_usuario==17){
                        $query->where('tramite.idDependencia',$idDependencia_usuario);
                    }
                }
            })
            // ->where('tramite.idPrograma',$idDependencia_usuario)
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
                        $query->where('tramite.idPrograma',$idDependencia_usuario);
                    }elseif($idTipo_usuario==17){
                        $query->where('tramite.idDependencia',$idDependencia_usuario);
                    }
                }
            })
            ->count();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'historial_estado.fecha as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
                        $query->where('tramite.idPrograma',$idDependencia_usuario);
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
                        $query->where('tramite.idPrograma',$idDependencia_usuario);
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

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'historial_estado.fecha as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','tramite.nro_matricula',
            'dependencia.nombre as facultad',
            'usuario.nro_documento','tramite.idUnidad','tipo_tramite.idTipo_tramite'
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
                    // echo $request->idTipo_tramite_unidad;
                    $query->where('tramite.idTipo_tramite_unidad',$request->idTipo_tramite_unidad);
                }
                if ($request->cronograma!=0) {
                    $query->where('cronograma_carpeta.fecha_colacion',$request->cronograma);
                }
            })
            ->where(function($query) use($idDependencia)
            {
                if ($idDependencia) {
                    if ($idDependencia==15) {
                        $query->where('tramite.idPrograma',41)
                        ->orWhere('tramite.idPrograma',42)
                        ->orWhere('tramite.idPrograma',43)
                        ->orWhere('tramite.idPrograma',44)
                        ->orWhere('tramite.idPrograma',45)
                        ->orWhere('tramite.idPrograma',46);
                    }elseif($idDependencia==11){
                        $query->where('tramite.idPrograma',11)
                        ->orWhere('tramite.idPrograma',47);
                    }else{
                        $query->where('tramite.idPrograma',$idDependencia);
                    }
                }
            })
            ->orderBy('usuario.apellidos','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();

            // TRÁMITES POR USUARIO
            $total=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
                if ($request->cronograma!=0) {
                    $query->where('cronograma_carpeta.fecha_colacion',$request->cronograma);
                }
            })
            ->where(function($query) use($request, $idDependencia)
            {
                if ($idDependencia) {
                    if ($idDependencia==15) {
                        $query->where('tramite.idPrograma',41)
                        ->orWhere('tramite.idPrograma',42)
                        ->orWhere('tramite.idPrograma',43)
                        ->orWhere('tramite.idPrograma',44)
                        ->orWhere('tramite.idPrograma',45)
                        ->orWhere('tramite.idPrograma',46);
                    }elseif($idDependencia==11){
                        $query->where('tramite.idPrograma',11)
                        ->orWhere('tramite.idPrograma',47);
                    }else {
                        $query->where('tramite.idPrograma',$idDependencia);
                    }
                }
            })
            ->orderBy('usuario.apellidos','asc')
            ->count();
        }elseif ($idTipo_usuario==8) {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
                if ($request->idPrograma!=0) {
                    $query->where('tramite.idPrograma',$request->idPrograma);
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
            $total=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
                if ($request->idPrograma!=0) {
                    $query->where('tramite.idPrograma',$request->idPrograma);
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
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
                if ($request->idPrograma!=0) {
                    $query->where('tramite.idPrograma',$request->idPrograma);
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
            $total=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
                if ($request->idPrograma!=0) {
                    $query->where('tramite.idPrograma',$request->idPrograma);
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

    public function getProgramas($idDependencia){
        $programas = ProgramaURAA::where('idDependencia',$idDependencia)->get();
        return response()->json($programas, 200);
    }

    public function reporteExpediente(Request $request){
        // return $request->all();
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $dni=$apy['nro_documento'];
        $idTipo_usuario=$apy['idTipo_usuario'];
        $idDependencia=$apy['idDependencia'];
        
        $tramites=Tramite::select('tramite.nro_matricula','tramite_detalle.codigo_diploma',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'tramite_detalle.folio',
        'cronograma_carpeta.fecha_colacion','programa.nombre as programa')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','programa.idPrograma','tramite.idPrograma')
        ->where('tipo_tramite.idTipo_tramite',2)
        ->where('tramite.idEstado_tramite','!=',29)
        ->where('tramite_detalle.codigo_diploma','!=',null)
        ->where(function($query) use($request)
        {
            if ($request->idUnidad!=0) {
                $query->where('tramite.idUnidad',$request->idUnidad);
            }
            if ($request->idDependencia!=0) {
                $query->where('tramite.idDependencia',$request->idDependencia);
            }
            if ($request->idPrograma!=0) {
                $query->where('tramite.idPrograma',$request->idPrograma);
            }
            if ($request->idTipo_tramite_unidad!=0) {
                $query->where('tramite.idTipo_tramite_unidad',$request->idTipo_tramite_unidad);
            }
            if ($request->cronograma!=0) {
                $query->where('cronograma_carpeta.fecha_colacion',$request->anio);
            }
        })
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();
        
        $total=Tramite::select('tramite.nro_matricula','tramite_detalle.codigo_diploma',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'tramite_detalle.folio',
        'cronograma_carpeta.fecha_colacion')
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
            if ($request->idPrograma!=0) {
                $query->where('tramite.idPrograma',$request->idPrograma);
            }
            if ($request->idTipo_tramite_unidad!=0) {
                $query->where('tramite.idTipo_tramite_unidad',$request->idTipo_tramite_unidad);
            }
            if ($request->cronograma!=0) {
                $query->where('cronograma_carpeta.fecha_colacion',$request->anio);
            }
        })
        ->count();
        
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


    public function expedientesPDF(Request $request){
        // return $request->all();
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        // $token = JWTAuth::getToken();
        // $apy = JWTAuth::getPayload($token);
        // $idUsuario=$apy['idUsuario'];
        // $dni=$apy['nro_documento'];
        // $idTipo_usuario=$apy['idTipo_usuario'];
        // $idDependencia=$apy['idDependencia'];
        
        $tramites=Tramite::select('tramite.nro_matricula','tramite_detalle.codigo_diploma as codigo_diploma',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'tramite_detalle.folio as folio',
        'cronograma_carpeta.fecha_colacion as fecha','programa.nombre as programa','dependencia.nombre as dependencia','tipo_tramite_unidad.descripcion as descripcion')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','programa.idPrograma','tramite.idPrograma')
        ->where('tipo_tramite.idTipo_tramite',2)
        ->where('tramite.idEstado_tramite','!=',29)
        ->where('tramite_detalle.codigo_diploma','!=',null)
        ->where(function($query) use($request)
        {
            if ($request->idUnidad!=0) {
                $query->where('tramite.idUnidad',$request->idUnidad);
            }
            if ($request->idDependencia!=0) {
                $query->where('tramite.idDependencia',$request->idDependencia);
            }
            if ($request->idPrograma!=0) {
                $query->where('tramite.idPrograma',$request->idPrograma);
            }
            if ($request->idTipo_tramite_unidad!=0) {
                $query->where('tramite.idTipo_tramite_unidad',$request->idTipo_tramite_unidad);
            }
            if ($request->cronograma!=0) {
                $query->where('cronograma_carpeta.fecha_colacion',$request->anio);
            }
        })
        ->orderby('programa')
        ->orderby('solicitante')
        ->get();

        $this->pdf->AliasNbPages('A4');
        $this->pdf->AddPage('P');
        $pag=1;

        $this->pdf->SetFont('Arial','', 9);
        $this->pdf->SetXY(10,10);
        $this->pdf->Cell(65, 4,'UNIVERSIDAD NACIONAL DE TRUJILLO',0,0,'C');
        $this->pdf->SetXY(10,14);
        $this->pdf->Cell(65, 4,utf8_decode('UNIDAD DE REGISTROS ACADEMICOS'),0,0,'C');
        $this->pdf->SetXY(10,18);
        $this->pdf->Cell(40, 4,utf8_decode('SECCIÓN DE INFORMÁTICA Y SISTEMAS'),0,0,'L');

        $this->pdf->SetXY(-65,10);
        $this->pdf->Cell(80, 4,'FECHA : '.date("j/ n/ Y"),0,0,'C');
        $this->pdf->SetXY(-65,14);
        $this->pdf->Cell(80, 4,'HORA : '.date("H:i:s"),0,0,'C');
        $this->pdf->SetXY(-65,18);
        $this->pdf->Cell(80, 4,'PAG: '.$pag,0,0,'C');
        //TITULO
        $this->pdf->SetFont('Arial','B', 10);
        $this->pdf->SetXY(0,25);
        $this->pdf->Cell(210, 4,utf8_decode('RELACIÓN DE EXPEDIENTES QUE PASAN AL SERVICIO DE ARCHIVO'),0,0,'C');
        
        //
        $this->pdf->SetFont('Arial','', 8);
        $this->pdf->SetXY(5,34);
        $this->pdf->Cell(30, 4,utf8_decode('FACULTAD                :'),0,0,'L');
        $this->pdf->SetXY(5,38);
        $this->pdf->Cell(30, 4,utf8_decode('ESCUELA                  :'),0,0,'L');
        $this->pdf->SetXY(5,42);
        $this->pdf->Cell(30, 4,utf8_decode('GRADO Y/O TÍTULO:'),0,0,'L');
        $this->pdf->SetXY(35,34);
        $this->pdf->Cell(150, 4,utf8_decode($tramites[0]['dependencia']),0,0,'L');
        $this->pdf->SetXY(35,38);
        $this->pdf->Cell(150, 4,utf8_decode($tramites[0]['programa']),0,0,'L');
        $this->pdf->SetXY(35,42);
        $this->pdf->Cell(150, 4,utf8_decode($tramites[0]['descripcion']),0,0,'L');
        //TABLA
        //#
        $this->pdf->SetFont('Arial','B', 7);
        $this->pdf->SetXY(5,50);
        $this->pdf->multiCell(10,3.5,'NUM.ORD.',1,'C');
        //NRO.MATRICULA
        $this->pdf->SetXY(15,50);
        $this->pdf->multiCell(25, 3.5,utf8_decode('NÚMERO DE MATRÍCULA'),1,'C');
        //#FICHA
        $this->pdf->SetXY(40,50);
        $this->pdf->multiCell(30, 3.5,utf8_decode('NÚMERO DE'),'T','C');
        $this->pdf->SetXY(40,53.5);
        $this->pdf->multiCell(30, 3.5,utf8_decode('FICHA'),'B','C');
        //SOLICITANTE
        $this->pdf->SetXY(70,50);
        $this->pdf->Cell(100, 7,'APELLIDOS Y NOMBRES',1,0,'C');
        //FOLIO
        $this->pdf->SetXY(170,50);
        $this->pdf->Cell(10, 7,'FOLIO',1,0,'C');
        //FECHA
        $this->pdf->SetXY(180,50);
        $this->pdf->Cell(25, 7,'FECHA',1,0,'C');

        $salto=0;
        $i=0;
        $inicioY=57;
        
        $this->pdf->SetFont('Arial','', 8);
        foreach ($tramites as $key => $tramite) {
        
            //#
            $this->pdf->SetXY(5,$inicioY+$salto);
            $this->pdf->Cell(10, 6,$i+1,0,0,'C');
            //nro_matricula
            $this->pdf->SetXY(15,$inicioY+$salto);
            $this->pdf->Cell(25, 6,$tramite->nro_matricula,0,0,'C');
            //codigo_diploma
            $this->pdf->SetXY(40,$inicioY+$salto);
            $this->pdf->Cell(30, 6,$tramite->codigo_diploma,0,0,'C');
            //solicitante
            $this->pdf->SetXY(70,$inicioY+$salto);
            $this->pdf->Cell(100, 6,utf8_decode($tramite->solicitante),0,'L');
            //folio
            $this->pdf->SetXY(170,$inicioY+$salto);
            $this->pdf->Cell(10, 6,$tramite->folio,0,0,'C');
            //fecha
            $this->pdf->SetXY(180,$inicioY+$salto);
            $this->pdf->Cell(25, 6,$tramite->fecha,0,0,'C');
            $salto+=6;
            $i+=1;
            if($key!=0&&$key<(count($tramites)-1)&&$tramites[$key]['programa']!=$tramites[$key+1]['programa']){
                $this->pdf->AddPage('P');
                $pag++;
                $this->pdf->SetFont('Arial','', 9);
                $this->pdf->SetXY(10,10);
                $this->pdf->Cell(65, 4,'UNIVERSIDAD NACIONAL DE TRUJILLO',0,0,'C');
                $this->pdf->SetXY(10,14);
                $this->pdf->Cell(65, 4,utf8_decode('UNIDAD DE REGISTROS ACADEMICOS'),0,0,'C');
                $this->pdf->SetXY(10,18);
                $this->pdf->Cell(40, 4,utf8_decode('SECCIÓN DE INFORMÁTICA Y SISTEMAS'),0,0,'L');

                $this->pdf->SetXY(-65,10);
                $this->pdf->Cell(80, 4,'FECHA : '.date("j/ n/ Y"),0,0,'C');
                $this->pdf->SetXY(-65,14);
                $this->pdf->Cell(80, 4,'HORA : '.date("H:i:s"),0,0,'C');
                $this->pdf->SetXY(-65,18);
                 $this->pdf->Cell(80, 4,'PAG: '.$pag,0,0,'C');
                //TITULO
                $this->pdf->SetFont('Arial','B', 10);
                $this->pdf->SetXY(0,25);
                $this->pdf->Cell(210, 4,utf8_decode('RELACIÓN DE EXPEDIENTES QUE PASAN AL SERVICIO DE ARCHIVO'),0,0,'C');
                
                //
                $this->pdf->SetFont('Arial','', 8);
                $this->pdf->SetXY(5,34);
                $this->pdf->Cell(30, 4,utf8_decode('FACULTAD                :'),0,0,'L');
                $this->pdf->SetXY(5,38);
                $this->pdf->Cell(30, 4,utf8_decode('ESCUELA                  :'),0,0,'L');
                $this->pdf->SetXY(5,42);
                $this->pdf->Cell(30, 4,utf8_decode('GRADO Y/O TÍTULO:'),0,0,'L');
                $this->pdf->SetXY(35,34);
                $this->pdf->Cell(150, 4,utf8_decode($tramites[$key+1]['dependencia']),0,0,'L');
                $this->pdf->SetXY(35,38);
                $this->pdf->Cell(150, 4,utf8_decode($tramites[$key+1]['programa']),0,0,'L');
                $this->pdf->SetXY(35,42);
                $this->pdf->Cell(150, 4,utf8_decode($tramites[$key+1]['descripcion']),0,0,'L');
                //TABLA
                //#
                $this->pdf->SetFont('Arial','B', 7);
                $this->pdf->SetXY(5,50);
                $this->pdf->multiCell(10,3.5,'NUM.ORD.',1,'C');
                //NRO.MATRICULA
                $this->pdf->SetXY(15,50);
                $this->pdf->multiCell(25, 3.5,utf8_decode('NÚMERO DE MATRÍCULA'),1,'C');
                //#FICHA
                $this->pdf->SetXY(40,50);
                $this->pdf->multiCell(30, 3.5,utf8_decode('NÚMERO DE'),'T','C');
                $this->pdf->SetXY(40,53.5);
                $this->pdf->multiCell(30, 3.5,utf8_decode('FICHA'),'B','C');
                //SOLICITANTE
                $this->pdf->SetXY(70,50);
                $this->pdf->Cell(100, 7,'APELLIDOS Y NOMBRES',1,0,'C');
                //FOLIO
                $this->pdf->SetXY(170,50);
                $this->pdf->Cell(10, 7,'FOLIO',1,0,'C');
                //FECHA
                $this->pdf->SetXY(180,50);
                $this->pdf->Cell(25, 7,'FECHA',1,0,'C');

                $salto=0;
                $i=0;
                $inicioY=57;
                $this->pdf->SetFont('Arial','', 8);
            }
            if (($inicioY+$salto)>=273) {
                $this->pdf->AddPage('P');
                $inicioY=57;
                $salto=0;
                $pag++;
                $this->pdf->SetFont('Arial','', 9);
                $this->pdf->SetXY(10,10);
                $this->pdf->Cell(65, 4,'UNIVERSIDAD NACIONAL DE TRUJILLO',0,0,'C');
                $this->pdf->SetXY(10,14);
                $this->pdf->Cell(65, 4,utf8_decode('UNIDAD DE REGISTROS ACADEMICOS'),0,0,'C');
                $this->pdf->SetXY(10,18);
                $this->pdf->Cell(40, 4,utf8_decode('SECCIÓN DE INFORMÁTICA Y SISTEMAS'),0,0,'L');

                $this->pdf->SetXY(-65,10);
                $this->pdf->Cell(80, 4,'FECHA : '.date("j/ n/ Y"),0,0,'C');
                $this->pdf->SetXY(-65,14);
                $this->pdf->Cell(80, 4,'HORA : '.date("H:i:s"),0,0,'C');
                $this->pdf->SetXY(-65,18);
                 $this->pdf->Cell(80, 4,'PAG: '.$pag,0,0,'C');
                //TITULO
                $this->pdf->SetFont('Arial','B', 10);
                $this->pdf->SetXY(0,25);
                $this->pdf->Cell(210, 4,utf8_decode('RELACIÓN DE EXPEDIENTES QUE PASAN AL SERVICIO DE ARCHIVO'),0,0,'C');

                $this->pdf->SetFont('Arial','', 8);
                $this->pdf->SetXY(5,34);
                $this->pdf->Cell(30, 4,utf8_decode('FACULTAD                :'),0,0,'L');
                $this->pdf->SetXY(5,38);
                $this->pdf->Cell(30, 4,utf8_decode('ESCUELA                  :'),0,0,'L');
                $this->pdf->SetXY(5,42);
                $this->pdf->Cell(30, 4,utf8_decode('GRADO Y/O TÍTULO:'),0,0,'L');
                $this->pdf->SetXY(35,34);
                $this->pdf->Cell(150, 4,utf8_decode($tramites[$key+1]['dependencia']),0,0,'L');
                $this->pdf->SetXY(35,38);
                $this->pdf->Cell(150, 4,utf8_decode($tramites[$key+1]['programa']),0,0,'L');
                $this->pdf->SetXY(35,42);
                $this->pdf->Cell(150, 4,utf8_decode($tramites[$key+1]['descripcion']),0,0,'L');
                //TABLA
                //#
                $this->pdf->SetFont('Arial','B', 7);
                $this->pdf->SetXY(5,50);
                $this->pdf->multiCell(10,3.5,'NUM.ORD.',1,'C');
                //NRO.MATRICULA
                $this->pdf->SetXY(15,50);
                $this->pdf->multiCell(25, 3.5,utf8_decode('NÚMERO DE MATRÍCULA'),1,'C');
                //#FICHA
                $this->pdf->SetXY(40,50);
                $this->pdf->multiCell(30, 3.5,utf8_decode('NÚMERO DE'),'T','C');
                $this->pdf->SetXY(40,53.5);
                $this->pdf->multiCell(30, 3.5,utf8_decode('FICHA'),'B','C');
                //SOLICITANTE
                $this->pdf->SetXY(70,50);
                $this->pdf->Cell(100, 7,'APELLIDOS Y NOMBRES',1,0,'C');
                //FOLIO
                $this->pdf->SetXY(170,50);
                $this->pdf->Cell(10, 7,'FOLIO',1,0,'C');
                //FECHA
                $this->pdf->SetXY(180,50);
                $this->pdf->Cell(25, 7,'FECHA',1,0,'C');

                $this->pdf->SetFont('Arial','', 8);
            }
        }

        return response($this->pdf->Output('i',"Reporte_carnets_recibos".".pdf", false))
        ->header('Content-Type', 'application/pdf');  

    }


    public function crearExcel($idDependencia,$cronograma){
       
        // return $idDependencia."-".$cronograma;

        DB::beginTransaction();
        try {
            // Declarando la respuesta a exportar
            $response=array();
            // Seleccionando la dependencia que será la cabecera general y añadiendo a response
            $dependencia=DependenciaURAA::where('idDependencia',$idDependencia)->first();
            $response[0] = [""," COLACIÓN DEL ".$cronograma." DE LA ".$dependencia->nombre];

            // Declarando variable con información de inicio de cada programa y la cantidad de filas que ocupa
            $datos=array();

            // Declarando variable que indicará en qué fila de Response se almacenará cada array
            $cont_cells=0;

            // Declarando variable que indicará el key en la variable datos de cada programa que se imprimirá 
            // Obteniendo las escuelas pertenecientes a la dependencia
            $programas=ProgramaURAA::where('idDependencia',$idDependencia)->get();

            foreach ($programas as $key => $programa) {
                // // Obteniendo los trámites de cada programa pertenecientes a la colación seleccionada
                $tramites=Tramite::select('tramite.nro_tramite','tramite.nro_matricula',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),
                'estado_tramite.descripcion',DB::raw('CONCAT(asignado.apellidos," ",asignado.nombres) as asignado'))
                ->join('usuario','tramite.idUsuario','usuario.idUsuario')
                ->join('usuario as asignado','tramite.idUsuario_asignado','asignado.idUsuario')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->where('tramite.idTipo_tramite_unidad',37)
                ->where('tramite.idEstado_tramite','!=',29)
                ->where('tramite.idEstado_tramite','!=',15)
                ->where('tramite.idPrograma',$programa->idPrograma)
                ->where('cronograma_carpeta.fecha_colacion',$cronograma)
                ->get();

                if (count($tramites)>0) {
                    // Añadiendo dos espacios antes de empezar cada programa
                    $cont_cells++;
                    $response[$cont_cells]=[""];
                    // Añadiendo la cabecera con el nombre del programa
                    $cont_cells++;
                    $response[$cont_cells]=["","CERTIFICADOS PENDIENTES DE ".$programa->nombre];
                    // Añadiendo información referente a la fila de inicio y cantidad de datos de cada programa
                    $datos[$key]=[$cont_cells,count($tramites)];
                    // Agregando las cabeceras de cada programa
                    $cont_cells++;
                    $response[$cont_cells]=["","N°","N° TRÁMITE","N° MATRÍCULA","EGRESADOS","ESTADO","ASIGNADO","OBSERVACIONES"];

                    foreach ($tramites as $key => $tramite) {
                        $cont_cells++;
                        $response[$cont_cells]=["",$key+1,$tramite->nro_tramite,$tramite->nro_matricula,$tramite->solicitante,$tramite->descripcion,$tramite->asignado];
                    }

                }

            }
            
            $descarga=Excel::download(new ReporteGradoExport($response,$datos), 'REPORTE.xlsx');
            return $descarga;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }

    }
    

}

