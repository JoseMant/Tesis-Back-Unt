<?php

namespace App\Http\Controllers;

use App\Dependencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Tramite;
use App\Tramite_Requisito;
use App\ProgramaURAA;
use App\Mencion;
use App\DependenciaURAA;
use App\Tramite_Detalle;
use App\Usuario_Programa;
use App\MatriculaSUV;
use App\Alumno;
use App\PersonaSga;
use Codedge\Fpdf\Fpdf\Fpdf;
use App\Exports\ReporteGradoExport;
use App\Exports\ReporteGradoObservadosExport;
use App\Exports\ReporteDecanatoExport;
use App\Exports\ReporteCarpetasAptasExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ReporteController extends Controller
{
    protected $pdf;

    public function __construct(\App\PDF_Fut $pdf)
    {
        $this->pdf = $pdf;
        $this->middleware('jwt', ['except' => ['expedientesPDF','crearExcelCertificadosPendientes','crearExcelCertificadosObservados','crearExcelCarpetasAptas','crearPDF'
        ,'reporteAprobados','aptosColacion','indicadores','certificadosObservados','indicadorGrados','indicadorCarpetas']]);
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

    public function tramitesEspera(Request $request){
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $dni=$apy['nro_documento'];
        $idTipo_usuario=$apy['idTipo_usuario'];
        $idDependencia=$apy['idDependencia'];
        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        // TRÁMITES POR USUARIO
        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','tramite.nro_matricula','tipo_tramite_unidad.costo',
        'dependencia.nombre as facultad','programa.nombre as programa','voucher.entidad','voucher.fecha_operacion',
        'usuario.nro_documento','tramite.idUnidad','tipo_tramite.idTipo_tramite'
        ,'cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite','estado_tramite.descripcion as estado')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa','programa.idPrograma','tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('voucher','voucher.idVoucher','tramite.idVoucher')
        ->where('tramite.idEstado_tramite',28)
        ->where(function($query) use($request,$usuario_programas,$idTipo_usuario,$idDependencia)
            {
                if ($idTipo_usuario==5||$idTipo_usuario==17) {
                    $query->whereIn('tramite.idPrograma',$usuario_programas);
                }elseif($idTipo_usuario==8){
                    $query->where('tramite.idDpendencia',$idDependencia);

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
        ->where('tramite.idEstado_tramite',28)
        ->orderBy('usuario.apellidos','asc')
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
            $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','tramite.nro_matricula',
            'dependencia.nombre as facultad','programa.nombre as programa',
            'usuario.nro_documento','tramite.idUnidad','tipo_tramite.idTipo_tramite'
            ,'cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite','estado_tramite.descripcion as estado')
            ->join('programa','programa.idPrograma','tramite.idPrograma')
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
            ->where(function($query) use($usuario_programas)
            {
                if ($usuario_programas) {
                 $query->whereIn('tramite.idPrograma',$usuario_programas);
                    
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
        } elseif ($idTipo_usuario==17) {
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
            ->where('tramite.idDependencia', $idDependencia)
            ->where(function($query) use($request)
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
            ->where('tramite.idDependencia', $idDependencia)
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
            })
            ->orderBy('usuario.apellidos','asc')
            ->count();
        } else {
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
        foreach ($programas as $key => $programa) {
            if ($programa->idDependencia==10 && $programa->idPrograma!=8 && $programa->idPrograma!=13 && $programa->idPrograma!=14 && $programa->idPrograma!=15) {
                $programa->nombre=substr($programa->nombre, 22);
            }
        }
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


    public function crearExcelCertificadosPendientes($idDependencia,$cronograma){

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
            $programas=ProgramaURAA::where('idDependencia',$idDependencia)->where('estado',true)->orderBy('nombre','asc')->get();

            foreach ($programas as $key => $programa) {
                // // Obteniendo los trámites de cada programa pertenecientes a la colación seleccionada
                $tramites=Tramite::select('tramite.nro_tramite','tramite.nro_matricula',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),
                'estado_tramite.descripcion',DB::raw('CONCAT(asignado.apellidos," ",asignado.nombres) as asignado'),'tramite.idEstado_tramite')
                ->join('usuario','tramite.idUsuario','usuario.idUsuario')
                ->join('usuario as asignado','tramite.idUsuario_asignado','asignado.idUsuario')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->where('tramite.idTipo_tramite_unidad',37)
                ->where('tramite.idEstado_tramite','!=',29)
                ->where('tramite.idEstado_tramite','!=',15)
                ->where('tramite.idEstado_tramite','!=',13)
                ->where('tramite.idPrograma',$programa->idPrograma)
                ->where('cronograma_carpeta.fecha_colacion',$cronograma)
                ->where('tramite.estado',true)
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
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
                        // Personalizando los mensajes del reporte
                        if ($tramite->idEstado_tramite==5) {
                            $tramite->descripcion="PENDIENTE DE ASIGNACIÓN A ENCARGADO";
                        }elseif ($tramite->idEstado_tramite==7) {
                            $tramite->descripcion="PENDIENTE DE VALIDACIÓN DE FOTOGRAFÍA";
                        }elseif ($tramite->idEstado_tramite==8) {
                            $tramite->descripcion="PENDIENTE DE GENERACIÓN DE CERTIFICADO";
                        }

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
    public function crearExcelCarpetasAptas($idDependencia,$idTipo_tramite_unidad,$cronograma){

        DB::beginTransaction();
        try {
            // Declarando las respuesta a exportar, tanto para la hoja principal como para los programas
            $response=array();
            $responseProgramas=array();

            // Seleccionando la dependencia que será la cabecera general y añadiendo a response
            $dependencia=DependenciaURAA::where('idDependencia',$idDependencia)->first();
            if ($idTipo_tramite_unidad==15) {
                $response[0] = [""," RELACIÓN DE BACHILLERES PARA LA COLACION ".$cronograma];
            }elseif ($idTipo_tramite_unidad==16) {
                $response[0] = [""," RELACIÓN DE TÍTULOS PROFESIONALES PARA LA COLACION ".$cronograma];
            }elseif ($idTipo_tramite_unidad==34) {
                $response[0] = [""," RELACIÓN DE TÍTULOS PROFESIONALES DE SEGUNDA ESPECIALIDAD PARA LA COLACION ".$cronograma];
            }
            $response[1] = ["",$dependencia->nombre];
            $response[2] = [""];

            $cabecera=null;
            if ($idTipo_tramite_unidad==15) {
                $cabecera=["","ITEM","APELLIDOS Y NOMBRES"," MODALIDAD ","ESCUELA PROFESIONAL","N° MATRICULA"];
            }else {
                $cabecera=["","ITEM","APELLIDOS Y NOMBRES"," MODALIDAD ","ESCUELA PROFESIONAL","N° MATRICULA"," TÉSIS "," FECHA "];
            }

            $response[3] = $cabecera;


            // Declarando variable con información de inicio de cada programa y la cantidad de filas que ocupa
            $datos=array();

            // Declarando variable que indicará en qué fila de Response se almacenará cada array
            $cont_cells=3;

            // Declarando variable que indicará el key en la variable datos de cada programa que se imprimirá

            // Obteniendo los programas pertenecientes a la dependencia
            $programas=ProgramaURAA::where('idDependencia',$idDependencia)->where('estado',true)->orderBy('nombre','asc')->get();

            // Declarando el contador de trámites
            $contTramites=1;

            foreach ($programas as $key => $programa) {

                // Obteniendo los trámites de cada programa pertenecientes a la colación seleccionada
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
                ,'tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','tramite.idUnidad','tipo_tramite.idTipo_tramite'
                ,'cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite','estado_tramite.descripcion as estado','programa.nombre as programa')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('historial_estado','tramite.idTramite','historial_estado.idTramite')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('programa','programa.idPrograma','tramite.idPrograma')
                ->where('tipo_tramite.idTipo_tramite',2)
                ->where('tramite.idEstado_tramite','!=',29)
                ->where('historial_estado.idEstado_actual',21)
                ->where('historial_estado.idEstado_nuevo',32)
                ->where('tramite.idPrograma',$programa->idPrograma)
                ->where('tramite.idTipo_tramite_unidad',$idTipo_tramite_unidad)
                ->where('cronograma_carpeta.fecha_colacion',$cronograma)
                ->orderBy('usuario.apellidos','asc')
                ->get();
                
                if (count($tramites)>0) {
                    // Datos para las hojas de cada programa
                    $arrayPrograma=array();
                    if ($idTipo_tramite_unidad==15) {
                        $arrayPrograma[0] = [""," RELACIÓN DE BACHILLERES DE LA ESCUELA PROFESIONAL DE"];
                    }elseif ($idTipo_tramite_unidad==16) {
                        $arrayPrograma[0] = [""," RELACIÓN DE TÍTULOS PROFESIONALES DE LA ESCUELA PROFESIONAL DE"];
                    }elseif ($idTipo_tramite_unidad==34) {
                        $arrayPrograma[0] = [""," RELACIÓN DE TÍTULOS PROFESIONALES DE SEGUNDA ESPECIALIDAD DE LA ESCUELA PROFESIONAL DE"];
                    }
                    // $arrayPrograma[0]=["","RELACIÓN DE BACHILLERES DE LA ESCUELA PROFESIONAL DE"];
                    $arrayPrograma[1]=["",$programa->nombre];
                    $arrayPrograma[2]=[""," COLACION ".$cronograma];
                    $arrayPrograma[3]=[""];
                    $arrayPrograma[4]=$cabecera;

                    $contaCellPrograma=4;

                    // $datos[$key]=[$cont_cells,count($tramites)];
                    array_push($datos,[$cont_cells,count($tramites)]);
                    foreach ($tramites as $keyTramite => $tramite) {
                        // Array de la hoja principal
                        $cont_cells++;
                        if ($idTipo_tramite_unidad==15) {
                            $response[$cont_cells]=["",$contTramites,$tramite->solicitante," AUTOMÁTICO ",$tramite->programa,$tramite->nro_matricula];
                        }else {
                            $response[$cont_cells]=["",$contTramites,$tramite->solicitante," ",$tramite->programa,$tramite->nro_matricula];
                        }
                        $contTramites++;
                        
                        // Array de cada programa
                        $contaCellPrograma++;
                        if ($idTipo_tramite_unidad==15) {
                            $arrayPrograma[$contaCellPrograma]=["",$keyTramite+1,$tramite->solicitante," AUTOMÁTICO ",$tramite->programa,$tramite->nro_matricula];
                        }else {
                            $arrayPrograma[$contaCellPrograma]=["",$keyTramite+1,$tramite->solicitante," ",$tramite->programa,$tramite->nro_matricula];
                        }
                        
                    }
                    $responseProgramas[$key]=$arrayPrograma;
                }
            }
            // return $datos[0][1];
            // return $responseProgramas;
            $descarga=Excel::download(new ReporteCarpetasAptasExport($response,$datos,$idTipo_tramite_unidad,$cronograma,$responseProgramas), 'CARPETAS APTAS.xlsx');
            return $descarga;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }

    }
    public function crearExcelCertificadosObservados($idDependencia,$cronograma){

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
            $programas=ProgramaURAA::where('idDependencia',$idDependencia)->where('estado',true)->orderBy('nombre','asc')->get();

            foreach ($programas as $key => $programa) {
                // // Obteniendo los trámites de cada programa pertenecientes a la colación seleccionada
                $tramites=Tramite::select('tramite.nro_tramite','tramite.nro_matricula',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),
                DB::raw('CONCAT(requisito.nombre,": ",tramite_requisito.comentario) as comentario'))
                ->join('usuario','tramite.idUsuario','usuario.idUsuario')
                ->join('tipo_tramite_unidad','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('historial_estado','tramite.idTramite','historial_estado.idTramite')
                ->join('tramite_requisito','tramite.idTramite','tramite_requisito.idTramite')
                ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
                ->where('tipo_tramite_unidad.idTipo_tramite',2)
                ->where(function($query)
                {
                    $query->where('tramite.idEstado_tramite',9)
                    ->orWhere('tramite.idEstado_tramite',30)
                    ->orWhere('tramite.idEstado_tramite',32);
                })
                ->where(function($query)
                {
                    $query->where('historial_estado.idEstado_actual',7)
                    ->orWhere('historial_estado.idEstado_actual',9);
                })
                ->where(function($query)
                {
                    $query->where('historial_estado.idEstado_nuevo',9)
                    ->orWhere('historial_estado.idEstado_nuevo',30)
                    ->orWhere('historial_estado.idEstado_nuevo',32);
                })
                ->where('tramite_requisito.des_estado_requisito',"RECHAZADO")
                ->where('tramite.idPrograma',$programa->idPrograma)
                ->where('cronograma_carpeta.fecha_colacion',$cronograma)
                ->where('tramite.estado',true)
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->groupBy('nro_tramite')
                ->groupBy('nro_matricula')
                ->groupBy('apellidos')
                ->groupBy('nombres')
                ->groupBy('comentario')
                ->get();

                if (count($tramites)>0) {
                    // Añadiendo dos espacios antes de empezar cada programa
                    $cont_cells++;
                    $response[$cont_cells]=[""];
                    // Añadiendo la cabecera con el nombre del programa
                    $cont_cells++;
                    $response[$cont_cells]=["","CARPETAS OBSERVADAS DE ".$programa->nombre];
                    // Añadiendo información referente a la fila de inicio y cantidad de datos de cada programa
                    $datos[$key]=[$cont_cells,count($tramites)];
                    // Agregando las cabeceras de cada programa
                    $cont_cells++;
                    $response[$cont_cells]=["","N°","N° TRÁMITE","N° MATRÍCULA","EGRESADOS","OBSERVACIÓN"];

                    foreach ($tramites as $key => $tramite) {
                        // Personalizando los mensajes del reporte
                        if ($tramite->idEstado_tramite==5) {
                            $tramite->descripcion="PENDIENTE DE ASIGNACIÓN A ENCARGADO";
                        }elseif ($tramite->idEstado_tramite==7) {
                            $tramite->descripcion="PENDIENTE DE VALIDACIÓN DE FOTOGRAFÍA";
                        }elseif ($tramite->idEstado_tramite==8) {
                            $tramite->descripcion="PENDIENTE DE GENERACIÓN DE CERTIFICADO";
                        }

                        $cont_cells++;
                        $response[$cont_cells]=["",$key+1,$tramite->nro_tramite,$tramite->nro_matricula,$tramite->solicitante,$tramite->comentario];
                    }

                }

            }

            $descarga=Excel::download(new ReporteGradoObservadosExport($response,$datos), 'REPORTE.xlsx');
            return $descarga;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }

    }

    public function certificadosObservadosCarpetasPDF($idDependencia,$cronograma){

        $tramites=Tramite::select('tramite.nro_tramite','tramite.nro_matricula',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),
        'estado_tramite.descripcion as descripcion',DB::raw('CONCAT(asignado.apellidos," ",asignado.nombres) as asignado'),'programa.nombre as programa',
        'tramite.idEstado_tramite')
        ->join('usuario','tramite.idUsuario','usuario.idUsuario')
        ->join('usuario as asignado','tramite.idUsuario_asignado','asignado.idUsuario')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','programa.idPrograma','tramite.idPrograma')
        ->where('tramite.idTipo_tramite_unidad',37)
        ->where('tramite.idEstado_tramite','!=',29)
        ->where('tramite.idEstado_tramite','!=',15)
        ->where('tramite.idDependencia',$idDependencia)
        ->where('cronograma_carpeta.fecha_colacion',$cronograma)
        ->orderby('programa')
        ->orderby('solicitante')
        ->get();

        $this->pdf->AliasNbPages('A4');
        $this->pdf->AddPage('L');

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

        $this->pdf->SetFont('Arial','B', 13);

        $dependencia=DependenciaURAA::find($idDependencia);
        $this->pdf->SetXY(5,30);
        $this->pdf->Cell(297, 4,utf8_decode(' COLACIÓN DEL '.$cronograma.' DE LA '.$dependencia->nombre),0,0,'C');

        if (count($tramites)>0) {
            $this->pdf->SetFont('Arial','BU', 8);
            $this->pdf->SetXY(5,34);
            $this->pdf->Cell(297, 8,utf8_decode('CERTIFICADOS PENDIENTES DE '.$tramites[0]['programa']),0,0,'C');
    
            $this->pdf->SetFont('Arial','B', 7);
            $this->pdf->SetXY(5,42);
            $this->pdf->Cell(5,4,utf8_decode('N°'),1,'C');
            //N° TRÁMITE
            $this->pdf->SetXY(10,42);
            $this->pdf->Cell(15,4,utf8_decode('N°TRÁMITE'),1,'C');
            //N° MATRÍCULA
            $this->pdf->SetXY(25,42);
            $this->pdf->Cell(20,4,utf8_decode('N°MATRÍCULA'),1,'C');
            //EGRESADOS
            $this->pdf->SetXY(45,42);
            $this->pdf->Cell(70,4,utf8_decode('EGRESADOS'),1,'C');
            //ESTADO
            $this->pdf->SetXY(115,42);
            $this->pdf->Cell(70, 4,'ESTADO',1,0,'C');
            //ASIGNADO
            $this->pdf->SetXY(185,42);
            $this->pdf->Cell(50, 4,'ASIGNADO',1,0,'C');
            //OBSERVACION
            $this->pdf->SetXY(235,42);
            $this->pdf->Cell(57, 4,'OBSERVACION',1,0,'C');
    
            $salto=0;
            $i=0;
            $inicioY=46;
            $this->pdf->SetFont('Arial','', 7);
            foreach ($tramites as $key => $tramite) {
    
    
                    $this->pdf->SetXY(5,$inicioY+$salto);
                    $this->pdf->Cell(5,4,$i+1,1,'C');
                    //N° TRÁMITE
                    $this->pdf->SetXY(10,$inicioY+$salto);
                    $this->pdf->Cell(15,4,$tramite->nro_tramite,1,'C');
                    //N° MATRÍCULA
                    $this->pdf->SetXY(25,$inicioY+$salto);
                    $this->pdf->Cell(20,4,$tramite->nro_matricula,1,'C');
                    //EGRESADOS
                    $this->pdf->SetXY(45,$inicioY+$salto);
                    $this->pdf->Cell(70,4,utf8_decode($tramite->solicitante),1,'C');
                    //ESTADO
                    // Personalizando los mensajes del reporte
                    if ($tramite->idEstado_tramite==5) {
                        $tramite->descripcion="PENDIENTE DE ASIGNACIÓN A ENCARGADO";
                    }elseif ($tramite->idEstado_tramite==7) {
                        $tramite->descripcion="PENDIENTE DE VALIDACIÓN DE FOTOGRAFÍA";
                    }elseif ($tramite->idEstado_tramite==8) {
                        $tramite->descripcion="PENDIENTE DE GENERACIÓN DE CERTIFICADO";
                    }
                    $this->pdf->SetXY(115,$inicioY+$salto);
                    $this->pdf->Cell(70, 4,utf8_decode($tramite->descripcion),1,0,'C');
                    //ASIGNADO
                    $this->pdf->SetXY(185,$inicioY+$salto);
                    $this->pdf->Cell(50, 4,utf8_decode($tramite->asignado),1,0,'C');
                    //OBSERVACION
                    $this->pdf->SetXY(235,$inicioY+$salto);
                    $this->pdf->Cell(57, 4,'',1,0,'C');
                    $salto+=4;
                    $i+=1;
                    if($key<(count($tramites)-1)&&$tramites[$key]['programa']!=$tramites[$key+1]['programa']){
                        // if($key==0){
                        //     $key=-1;
                        // }
                        $i=0;
                        $this->pdf->SetFont('Arial','BU', 8);
                        $this->pdf->SetXY(5,$inicioY+$salto);
                        $this->pdf->Cell(297, 8,utf8_decode('CERTIFICADOS PENDIENTES DE '.$tramites[$key+1]['programa']),0,0,'C');
                        $salto+=8;
                        $this->pdf->SetFont('Arial','B', 7);
                        $this->pdf->SetXY(5,$inicioY+$salto);
                        $this->pdf->Cell(5,4,utf8_decode('N°'),1,'C');
                        //N° TRÁMITE
                        $this->pdf->SetXY(10,$inicioY+$salto);
                        $this->pdf->Cell(15,4,utf8_decode('N°TRÁMITE'),1,'C');
                        //N° MATRÍCULA
                        $this->pdf->SetXY(25,$inicioY+$salto);
                        $this->pdf->Cell(20,4,utf8_decode('N°MATRÍCULA'),1,'C');
                        //EGRESADOS
                        $this->pdf->SetXY(45,$inicioY+$salto);
                        $this->pdf->Cell(70,4,'EGRESADOS',1,'C');
                        //ESTADO
                        $this->pdf->SetXY(115,$inicioY+$salto);
                        $this->pdf->Cell(70, 4,'ESTADO',1,0,'C');
                        //ASIGNADO
                        $this->pdf->SetXY(185,$inicioY+$salto);
                        $this->pdf->Cell(50, 4,'ASIGNADO',1,0,'C');
                        //OBSERVACION
                        $this->pdf->SetXY(235,$inicioY+$salto);
                        $this->pdf->Cell(57, 4,'OBSERVACION',1,0,'C');
                        $this->pdf->SetFont('Arial','', 7);
    
                        $salto+=4;
                    }
                    if (($inicioY+$salto)>=182) {
                        $this->pdf->AddPage('L');
                        $inicioY=46;
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
    
                        $this->pdf->SetFont('Arial','B', 13);
    
                        $this->pdf->SetXY(5,30);
                        $this->pdf->Cell(297, 4,utf8_decode(' COLACIÓN DEL '.$cronograma.' DE LA '.$dependencia->nombre),0,0,'C');
    
                        $this->pdf->SetFont('Arial','', 7);
                    }
    
            }
        }

        return response($this->pdf->Output('i',"certificados_carpetas".".pdf", false))
        ->header('Content-Type', 'application/pdf');

    }

    public function reporteAprobados(Request $request){
        
        $token = JWTAuth::setToken($request->access);
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idTipo_usuario=$apy['idTipo_usuario'];

        // Obteniendo los certificados trabajados hoy
        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher','tramite.uuid')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
        ->where('historial_estado.idEstado_actual',10)
        ->where('historial_estado.idEstado_nuevo',11)
        ->where('tipo_tramite_unidad.idTipo_tramite',1)
        ->where(function($query) use($request){
            // Parseando la fecha
            $fecha = Carbon::parse($request->fecha);
            $dia = $fecha->day;
            $mes = $fecha->month;
            $año = $fecha->year;
            if ($fecha) {
                $query->whereDay('historial_estado.fecha',$dia)
                ->whereMonth('historial_estado.fecha',$mes)
                ->whereYear('historial_estado.fecha',$año);
            }
        })
        ->where(function($query) use($idTipo_usuario,$idUsuario){
            if ($idTipo_usuario==2) {
                $query->where('historial_estado.idUsuario',$idUsuario);
            }
        })
        // ->where('historial_estado.idUsuario',$idUsuario)
        ->get();

        $this->pdf->AliasNbPages();
        $this->pdf->AddPage('L');

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
        //TITULO
        $this->pdf->SetFont('Arial','B', 10);
        $this->pdf->SetXY(0,25);
        $this->pdf->Cell(297, 4,utf8_decode('CERTIFICADOS APROBADOS'),0,0,'C');
        
        
        $this->pdf->SetFont('Arial','B', 7);
        //TABLA
        //SEDE
        $this->pdf->SetXY(5,30);
        $this->pdf->Cell(5, 5,'#',1,0,'C');
        $this->pdf->SetXY(10,30);
        $this->pdf->Cell(20, 5,utf8_decode('NRO. TRÁMITE'),1,0,'C');
        //ESCUELA
        $this->pdf->SetXY(30,30);
        $this->pdf->Cell(70, 5,'SOLICITANTE',1,0,'C');
        //#CARNETS
        $this->pdf->SetXY(100,30);
        $this->pdf->Cell(65, 5,utf8_decode('TRÁMITE'),1,0,'C');
        //FIRMA
        $this->pdf->SetXY(165,30);
        $this->pdf->Cell(35, 5,'UNIDAD',1,0,'C');
        $this->pdf->SetXY(200,30);
        $this->pdf->Cell(20, 5,utf8_decode('# MATRÍCULA'),1,0,'C');
        $this->pdf->SetXY(220,30);
        $this->pdf->Cell(50, 5,'DEPENDENCIA',1,0,'C');
        $this->pdf->SetXY(270,30);
        $this->pdf->Cell(20, 5,'FECHA',1,0,'C');


        $this->pdf->SetFont('Arial','', 7);
        

        $salto=0;
        $i=0;
        $inicioY=35;
        $totalcarnets=0;
        foreach ($tramites as $key => $tramite) {
            $totalcarnets=$totalcarnets+$tramite->carnets;
            
            //TABLA
            //SEDE
            $this->pdf->SetXY(5,$inicioY+$salto);
            $this->pdf->Cell(5, 7,$i+1,1,0,'C');
            $this->pdf->SetXY(10,$inicioY+$salto);
            $this->pdf->Cell(20, 7,$tramite->nro_tramite,1,0,'C');
            //ESCUELA
            $this->pdf->SetXY(30,$inicioY+$salto);
            $this->pdf->Cell(70, 7,utf8_decode($tramite->solicitante),1,0,'L');
            //#CARNETS
            $this->pdf->SetXY(100,$inicioY+$salto);
            $this->pdf->Cell(65, 7,utf8_decode($tramite->tramite),1,0,'L');
            //FIRMA
            $this->pdf->SetXY(165,$inicioY+$salto);
            $this->pdf->Cell(35, 7,utf8_decode($tramite->unidad),1,0,'C');
            $this->pdf->SetXY(200,$inicioY+$salto);
            $this->pdf->Cell(20, 7,$tramite->nro_matricula,1,0,'C');
            $this->pdf->SetFont('Arial','', 6);
            $this->pdf->SetXY(220,$inicioY+$salto);
            $this->pdf->multiCell(50, 3.5,utf8_decode($tramite->dependencia),1,'C');
            $this->pdf->SetFont('Arial','', 7);
            $this->pdf->SetXY(270,$inicioY+$salto);
            $fecha=strtotime($tramite->fecha);
            $this->pdf->Cell(20, 7,date("Y m d",$fecha),1,0,'C');

            $salto+=7;
            $i+=1;
            
            if (($inicioY+$salto)>=269) {
                $this->pdf->AddPage();
                $inicioY=17;
                $salto=0;
                $this->pdf->SetFont('Arial','B', 7);
                    //TABLA
                    //SEDE
                    $this->pdf->SetXY(5,30);
                    $this->pdf->Cell(5, 5,'#',1,0,'C');
                    $this->pdf->SetXY(10,30);
                    $this->pdf->Cell(30, 5,utf8_decode('NRO. TRÁMITE'),1,0,'C');
                    //ESCUELA
                    $this->pdf->SetXY(40,30);
                    $this->pdf->Cell(70, 5,'SOLICITANTE',1,0,'C');
                    //#CARNETS
                    $this->pdf->SetXY(110,30);
                    $this->pdf->Cell(70, 5,utf8_decode('TRÁMITE'),1,0,'C');
                    //FIRMA
                    $this->pdf->SetXY(180,30);
                    $this->pdf->Cell(30, 5,'UNIDAD',1,0,'C');
                    $this->pdf->SetXY(210,30);
                    $this->pdf->Cell(30, 5,utf8_decode('NRO. MATRÍCULA'),1,0,'C');
                    $this->pdf->SetXY(240,30);
                    $this->pdf->Cell(30, 5,'DEPENDENCIA',1,0,'C');
                    $this->pdf->SetXY(270,30);
                    $this->pdf->Cell(20, 5,'FECHA',1,0,'C');


                    $this->pdf->SetFont('Arial','', 7);
            }
        }

        return response($this->pdf->Output('i',"Reporte_carnets_recibos".".pdf", false))
        ->header('Content-Type', 'application/pdf');
    }

    public function reporteCarpetasAptas(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $dni=$apy['nro_documento'];
        $idTipo_usuario=$apy['idTipo_usuario'];
        $idDependencia=$apy['idDependencia'];
        if ($idTipo_usuario==8) {
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
            ->join('historial_estado','tramite.idTramite','historial_estado.idTramite')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where('historial_estado.idEstado_actual',21)
            ->where('historial_estado.idEstado_nuevo',32)
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
            ->join('historial_estado','tramite.idTramite','historial_estado.idTramite')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where('historial_estado.idEstado_actual',21)
            ->where('historial_estado.idEstado_nuevo',32)
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
            ->join('historial_estado','tramite.idTramite','historial_estado.idTramite')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where('historial_estado.idEstado_actual',21)
            ->where('historial_estado.idEstado_nuevo',32)
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
            ->join('historial_estado','tramite.idTramite','historial_estado.idTramite')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where('historial_estado.idEstado_actual',21)
            ->where('historial_estado.idEstado_nuevo',32)
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


    public function aptosColacion($idDependencia,$cronograma){
        DB::beginTransaction();
        try {
            // Declarando la respuesta a exportar
            $response=array();
            // Seleccionando la dependencia que será la cabecera general y añadiendo a response
            $dependencia=DependenciaURAA::where('idDependencia',$idDependencia)->first();
            if ($dependencia->idUnidad==4) {
                $dependencia=DependenciaURAA::where('idDependencia',$dependencia->idDependencia2)->first();   
            }
            $response[0] = ["","CARPETAS APTAS PARA LA COLACIÓN DEL ".$cronograma." DE LA ".$dependencia->denominacion];

            // Declarando variable con información de inicio de cada programa y la cantidad de filas que ocupa
            $datos=array();

            // Declarando variable que indicará en qué fila de Response se almacenará cada array
            $cont_cells=0;

            // Declarando variable que indicará el key en la variable datos de cada programa que se imprimirá
            // Obteniendo las escuelas pertenecientes a la dependencia
            $programas=ProgramaURAA::where('idDependencia',$idDependencia)->where('estado',true)->orderBy('nombre','asc')->get();

            foreach ($programas as $key => $programa) {
                // Obteniendo los trámites de TITULOS DE PREGRADO cada programa pertenecientes a la colación seleccionada
                $titulos=Tramite::select(DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'programa.nombre as programa','tramite.nro_matricula')
                ->join('usuario','tramite.idUsuario','usuario.idUsuario')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('historial_estado','tramite.idTramite','historial_estado.idTramite')
                ->join('programa','tramite.idPrograma','programa.idPrograma')
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                // ->where('tramite.idTipo_tramite_unidad',16)
                ->where('tramite.idEstado_tramite','!=',29)
                ->where('historial_estado.idEstado_actual',21)
                ->where('historial_estado.idEstado_nuevo',32)
                ->where('tramite.idPrograma',$programa->idPrograma)
                ->where('cronograma_carpeta.fecha_colacion',$cronograma)
                ->where('tramite.estado',true)
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->get();

                if (count($titulos)>0) {
                    // Añadiendo un espacio antes de empezar cada programa
                    $cont_cells++;
                    $response[$cont_cells]=[""];
                    // Añadiendo la cabecera con el nombre del programa
                    $cont_cells++;
                    if ($dependencia->idUnidad==4) {
                        $response[$cont_cells]=["","RELACIÓN DE TÍTULOS PROFESIONALES DE ".$programa->denominacion];
                    }else {
                        $response[$cont_cells]=["","RELACIÓN DE TÍTULOS PROFESIONALES DE LA ".$programa->denominacion];
                    }
                    
                    // Añadiendo información referente a la fila de inicio y cantidad de datos de cada programa
                    $datos[$key]=[$cont_cells,count($titulos)];
                    // Agregando las cabeceras de cada programa
                    $cont_cells++;
                    $response[$cont_cells]=["","ITEM","NOMBRES Y APELLIDOS","MODALIDAD","ESC. PROFESIONAL","N° MATRÍCULA","TÉSIS","FECHA"];

                    foreach ($titulos as $key => $tramite) {
                        $cont_cells++;
                        $response[$cont_cells]=["",$key+1,$tramite->solicitante,"",$tramite->programa,$tramite->nro_matricula,"",""];
                    }

                }

            }

            $descarga=Excel::download(new ReporteDecanatoExport($response,$datos), 'REPORTE '.$dependencia->nombre.'.xlsx');
            return $descarga;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function certificadosObservados(Request $request){
        
        $token = JWTAuth::setToken($request->accessToken);
        $apy = JWTAuth::getPayload($token);
        $idDependencia=$request->idDependencia;
        $idTipo_usuario=$apy['idTipo_usuario'];
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.nro_tramite','tramite.nro_matricula',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'usuario.celular',
        'tramite_requisito.comentario','requisito.nombre',
        'estado_tramite.descripcion as descripcion',DB::raw('CONCAT(asignado.apellidos," ",asignado.nombres) as asignado'),'programa.nombre as programa',
        'tramite.idEstado_tramite')
        ->join('usuario','tramite.idUsuario','usuario.idUsuario')
        ->join('usuario as asignado','tramite.idUsuario_asignado','asignado.idUsuario')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('tipo_tramite_unidad','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
        ->join('programa','programa.idPrograma','tramite.idPrograma')
        ->join('tramite_requisito','tramite_requisito.idTramite','tramite.idTramite')
        ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
        ->where('tipo_tramite_unidad.idTipo_tramite',1)
        ->where('tramite.idEstado_tramite',9)
        ->where('tramite.idDependencia',$idDependencia)
        ->where(function($query) use($idTipo_usuario,$idUsuario)
        {
            if ($idTipo_usuario==2) {
                $query->where('tramite.idUsuario_asignado',$idUsuario);
            }
        })
        ->where(function($query)
        {
            $query->where('requisito.idRequisito',1)
            ->orWhere('requisito.idRequisito',2)
            ->orWhere('requisito.idRequisito',3)
            ->orWhere('requisito.idRequisito',4)
            ->orWhere('requisito.idRequisito',5)
            ->orWhere('requisito.idRequisito',6)
            ->orWhere('requisito.idRequisito',7)
            ->orWhere('requisito.idRequisito',8)
            ->orWhere('requisito.idRequisito',9)
            ->orWhere('requisito.idRequisito',10)
            ->orWhere('requisito.idRequisito',11)
            ->orWhere('requisito.idRequisito',12)
            ->orWhere('requisito.idRequisito',13)
            ->orWhere('requisito.idRequisito',14)
            ->orWhere('requisito.idRequisito',15)
            ->orWhere('requisito.idRequisito',61);
        })
        ->orderby('programa')
        ->orderby('solicitante')
        ->get();

        $this->pdf->AliasNbPages('A4');
        $this->pdf->AddPage('L');

        $pag=1;

        $tramitet=15;
        $matriculat=20;
        $egresadost=60;
        $estadot=70;
        $celulart=40;

        $observaciont=148;
        $observacionx=145;

        if($idTipo_usuario==1){
            $tramitet=15;
            $matriculat=20;
            $egresadost=60;
            $estadot=70;
            $celulart=20;

            $observaciont=110;
            $observacionx=185;

            $asignadot=60;

        }

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

        $this->pdf->SetFont('Arial','B', 13);

        $dependencia=DependenciaURAA::find($idDependencia);
        $this->pdf->SetXY(5,30);
        $this->pdf->Cell(297, 4,utf8_decode(' CERTIFICADOS OBSERVADOS DE '.$dependencia->nombre),0,0,'C');

        if (count($tramites)>0) {
            $this->pdf->SetFont('Arial','BU', 8);
            $this->pdf->SetXY(5,34);
            $this->pdf->Cell(297, 8,utf8_decode('CERTIFICADOS OBSERVADOS DE '.$tramites[0]['programa']),0,0,'C');
    
            $this->pdf->SetFont('Arial','B', 7);
            $this->pdf->SetXY(5,42);
            $this->pdf->Cell(5,4,utf8_decode('N°'),1,'C');
            //N° TRÁMITE
            $this->pdf->SetXY(10,42);
            $this->pdf->Cell($tramitet,4,utf8_decode('N°TRÁMITE'),1,'C');
            //N° MATRÍCULA
            $this->pdf->SetXY(25,42);
            $this->pdf->Cell($matriculat,4,utf8_decode('N°MATRÍCULA'),1,'C');
            //EGRESADOS
            $this->pdf->SetXY(45,42);
            $this->pdf->Cell($egresadost,4,utf8_decode('EGRESADOS'),1,'C');
            //ESTADO
            $this->pdf->SetXY(105,42);
            $this->pdf->Cell($celulart, 4,'CELULAR',1,0,'C');
            
            if($idTipo_usuario==1){
                //ASIGNADO
                $this->pdf->SetXY(125,42);
                $this->pdf->Cell($asignadot, 4,'ASIGNADO',1,0,'C');
            }
            //OBSERVACION
            $this->pdf->SetXY($observacionx,42);
            $this->pdf->Cell($observaciont, 4,'OBSERVACION',1,0,'C');
    
            $salto=0;
            $i=0;
            $inicioY=46;
            $this->pdf->SetFont('Arial','', 7);
            foreach ($tramites as $key => $tramite) {
    
    
                    $this->pdf->SetXY(5,$inicioY+$salto);
                    $this->pdf->Cell(5,4,$i+1,1,'C');
                    //N° TRÁMITE
                    $this->pdf->SetXY(10,$inicioY+$salto);
                    $this->pdf->Cell($tramitet,4,$tramite->nro_tramite,1,'C');
                    //N° MATRÍCULA
                    $this->pdf->SetXY(25,$inicioY+$salto);
                    $this->pdf->Cell($matriculat,4,$tramite->nro_matricula,1,'C');
                    //EGRESADOS
                    $this->pdf->SetXY(45,$inicioY+$salto);
                    $this->pdf->Cell($egresadost,4,utf8_decode($tramite->solicitante),1,'C');
                    //ESTADO
                    $this->pdf->SetXY(105,$inicioY+$salto);
                    $this->pdf->Cell($celulart, 4,utf8_decode($tramite->celular),1,0,'C');
                     if($idTipo_usuario==1){
                        //ASIGNADO
                        $this->pdf->SetXY(125,$inicioY+$salto);
                        $this->pdf->Cell($asignadot, 4,utf8_decode($tramite->asignado),1,0,'L');
                        if(strlen($tramite->comentario)>80){ 
                            $tramite->comentario=substr($tramite->comentario,0,75).'...';
                        }
                     }else if(strlen($tramite->comentario)>110){
                        
                        $tramite->comentario=substr($tramite->comentario,0,110).'...';
                     }else{
                        $tramite->comentario=substr($tramite->comentario,0,110);
                     }
                    //OBSERVACION
                    $this->pdf->SetXY($observacionx,$inicioY+$salto);
                    $this->pdf->Cell($observaciont, 4,$tramite->nombre.': '.ucfirst(strtolower(utf8_decode($tramite->comentario))),1,0,'L');
                    $salto+=4;
                    $i+=1;
                    if($key<(count($tramites)-1)&&$tramites[$key]['programa']!=$tramites[$key+1]['programa']){
                        // if($key==0){
                        //     $key=-1;
                        // }
                        $i=0;
                        $this->pdf->SetFont('Arial','BU', 8);
                        $this->pdf->SetXY(5,$inicioY+$salto);
                        $this->pdf->Cell(297, 8,utf8_decode('CERTIFICADOS OBSERVADOS DE '.$tramites[$key+1]['programa']),0,0,'C');
                        $salto+=8;
                        $this->pdf->SetFont('Arial','B', 7);
                        $this->pdf->SetXY(5,$inicioY+$salto);
                        $this->pdf->Cell(5,4,utf8_decode('N°'),1,'C');
                        //N° TRÁMITE
                        $this->pdf->SetXY(10,$inicioY+$salto);
                        $this->pdf->Cell($tramitet,4,utf8_decode('N°TRÁMITE'),1,'C');
                        //N° MATRÍCULA
                        $this->pdf->SetXY(25,$inicioY+$salto);
                        $this->pdf->Cell($matriculat,4,utf8_decode('N°MATRÍCULA'),1,'C');
                        //EGRESADOS
                        $this->pdf->SetXY(45,$inicioY+$salto);
                        $this->pdf->Cell($egresadost,4,'EGRESADOS',1,'C');
                        //ESTADO
                        $this->pdf->SetXY(105,$inicioY+$salto);
                        $this->pdf->Cell($celulart, 4,'CELULAR',1,0,'C');
                        if($idTipo_usuario==1){
                            //ASIGNADO
                            $this->pdf->SetXY(125,$inicioY+$salto);
                            $this->pdf->Cell($asignadot, 4,'ASIGNADO',1,0,'C');
                        }   
                        //OBSERVACION
                        $this->pdf->SetXY($observacionx,$inicioY+$salto);
                        $this->pdf->Cell($observaciont, 4,'OBSERVACION',1,0,'C');
                        $this->pdf->SetFont('Arial','', 7);
    
                        $salto+=4;
                    }
                    if (($inicioY+$salto)>=182) {
                        $this->pdf->AddPage('L');
                        $inicioY=46;
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
    
                        $this->pdf->SetFont('Arial','B', 13);
    
                        $this->pdf->SetXY(5,30);
                        $this->pdf->Cell(297, 4,utf8_decode(' CERTIFICADOS OBSERVADOS DE '.$dependencia->nombre),0,0,'C');
    
                        $this->pdf->SetFont('Arial','', 7);
                    }
    
            }
        }

        return response($this->pdf->Output('i',"certificados_observados".".pdf", false))
        ->header('Content-Type', 'application/pdf');
    }

    public function indicadores(Request $request){
        return [
            "certificadosIndicador1"=>$this->indicadorCertificados(2023),
            "gradosIndicador1"=>$this->indicadorCarpetas()
        ];
    }

    public function indicadorCertificados($anio){
        $response=array();
        $overview=array();
        $series=array();
        $inicio=1;
        $fin=3;
        for ($i=1; $i <= 4; $i++) { 

            // Overview -----------------------------------------------------
            $certificados=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->where('tipo_tramite_unidad.idTipo_tramite', 1)
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad','!=', 37)
            ->where('tramite.idEstado_tramite','!=',29)
            ->whereYear('created_at', '2023')
            ->whereMonth('created_at','>=', $inicio)
            ->whereMonth('created_at','<=', $fin)
            ->count();

            $certificados_dentro_plazo=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
            ->where('tipo_tramite_unidad.idTipo_tramite', 1)
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad','!=', 37)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where(DB::raw('TIMESTAMPDIFF(DAY, tramite.created_at,historial_estado.fecha)'),'<=',5)
            ->where('historial_estado.idEstado_actual',14)
            ->where('historial_estado.idEstado_nuevo',15)
            ->whereYear('created_at', '2023')
            ->whereMonth('created_at','>=', $inicio)
            ->whereMonth('created_at','<=', $fin)
            ->count();

            $terminados=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->where('tipo_tramite_unidad.idTipo_tramite', 1)
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad','!=', 37)
            ->where('tramite.idEstado_tramite',15)
            ->whereYear('created_at', '2023')
            ->whereMonth('created_at','>=', $inicio)
            ->whereMonth('created_at','<=', $fin)
            ->count();

            $pendientes=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->where('tipo_tramite_unidad.idTipo_tramite', 1)
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad','!=', 37)
            ->where('tramite.idEstado_tramite','!=',15)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where('tramite.idEstado_tramite','!=',9)
            ->where('tramite.idEstado_tramite','!=',4)
            ->whereYear('created_at', '2023')
            ->whereMonth('created_at','>=', $inicio)
            ->whereMonth('created_at','<=', $fin)
            ->count();

            $observados=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->where('tipo_tramite_unidad.idTipo_tramite', 1)
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad','!=', 37)
            ->where(function($query)
            {
                $query->where('tramite.idEstado_tramite',9)
                ->orWhere('tramite.idEstado_tramite',4);
            })
            ->whereYear('created_at', '2023')
            ->whereMonth('created_at','>=', $inicio)
            ->whereMonth('created_at','<=', $fin)
            ->count();

            $indicador=0;
            if ($certificados>0) {
                $indicador=($certificados_dentro_plazo/$certificados)*100;
            }

            $semestre=[
                "certificados"=>$certificados,
                "certificados-dentro"=>$certificados_dentro_plazo,
                "indicador"=>round($indicador,0),
                "terminados"=>$terminados,
                "pendientes"=>$pendientes,
                "observados"=>$observados
            ];
            
            $nombre="trimestre-".$i;
            $overview[$nombre]=$semestre;

            // Series --------------------------------------
            $solicitados=array();
            $dentro_plazo=array();
            for ($j=$inicio; $j <=$fin ; $j++) { 
                // Solicitados
                $solicitadosMensual=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->where('tipo_tramite_unidad.idTipo_tramite', 1)
                ->where('tipo_tramite_unidad.idTipo_tramite_unidad','!=', 37)
                ->where('tramite.idEstado_tramite','!=',29)
                ->whereYear('created_at', '2023')
                ->whereMonth('created_at',$j)
                ->count();
                array_push($solicitados,$solicitadosMensual);

                // Dentro del plazo
                $dentro_plazoMensual=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('historial_estado','historial_estado.idTramite','tramite.idTramite')
                ->where('tipo_tramite_unidad.idTipo_tramite', 1)
                ->where('tipo_tramite_unidad.idTipo_tramite_unidad','!=', 37)
                ->where('tramite.idEstado_tramite','!=',29)
                ->where(DB::raw('TIMESTAMPDIFF(DAY, tramite.created_at,historial_estado.fecha)'),'<=',5)
                ->where('historial_estado.idEstado_actual',14)
                ->where('historial_estado.idEstado_nuevo',15)
                ->whereYear('created_at', '2023')
                ->whereMonth('created_at',$j)
                ->count();

                array_push($dentro_plazo,$dentro_plazoMensual);
            }

            $semestre=[
                [
                    "name"=>"Certificados Solicitados",
                    "type"=>"line",
                    "data"=>$solicitados
                ],
                [
                    "name"=>"Certificados dentro del plazo",
                    "type"=>"column",
                    "data"=>$dentro_plazo
                ]
            ];

            $series[$nombre]=$semestre;

            if ($fin==12) {
                break;
            }



            $inicio=$inicio+3;
            $fin=$fin+3;
        }
        $response=[
            "overview"=>$overview,
            "labels"=>[
                "trimestre-1"=>["Enero","Febrero","Marzo"],
                "trimestre-2"=>["Abril","Mayo","Junio"],
                "trimestre-3"=>["Julio","Agosto","Setiembre"],
                "trimestre-4"=>["Octubre","Noviembre","Diciembre"],
            ],
            "series"=>$series
        ];
        return $response;
    }

    public function indicadorGrados(){
        $overview=array();
        $labels=array();
        $series=array();
        $anio=2016;
        // Seleccionando todas las facultades sin excepción
        $dependencias=DependenciaURAA::select('denominacion','idDependencia')->where('idUnidad',1)->where('estado',1)->get();
        foreach ($dependencias as $key => $dependencia) {
            // añadimos los labels  
            array_push($labels,$dependencia->denominacion);
        }        
        for ($i=0; $i <=3 ; $i++) { 
            $anio_ingreso=$anio+$i;
            $ingresantesFacultad=array(); // array de ingresantes por facultad que van en las series
            $egresadosFacultad=array(); // array de egresados por facultad que van en las series
            
            // PROGRAMAS DEL SUV SON 40
            foreach ($dependencias as $dependencia) {

                // Total de ingresantes por facultad
                $contIngresantes=0;
                // Total de egresados por facultad
                $contEgresados=0;
                            

                // Programas DEL SUV 
                $programas=ProgramaURAA::where('idUnidad',1)->where('idDependencia',$dependencia->idDependencia)->whereNotNull('idSUV_PREG')->pluck("idSUV_PREG");
                
                foreach ($programas as $programa) {
                    
                    // INGRESANTES SUV
                    $ingresantesPrograma=Alumno::join('patrimonio.area', 'alumno.idarea','area.idarea')
                    ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
                    ->where('alumno.idalumno','LIKE','%'.$anio_ingreso)
                    ->where('estructura.idestructura',$programa)
                    ->count();
    
                    $contIngresantes+=$ingresantesPrograma;

                    // EGRESADOS SUV
                    $subQueryEgresados=Alumno::select('alumno.idalumno',DB::raw('max(matricula.mat_fecha) as fecha_egreso'))
                    ->join('matriculas.matricula','matricula.idalumno', 'alumno.idalumno')
                    ->join('patrimonio.area', 'alumno.idarea','area.idarea')
                    ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
                    ->where('alumno.alu_estado',6)
                    ->where('estructura.idestructura',21)
                    ->where('alumno.idalumno','LIKE','%'.$anio_ingreso) // Egresados con el mismo año de ingreso que los ingresantes
                    ->groupBy('alumno.idalumno')
                    ;

                    $egresadosPrograma=Alumno::select('subQueryEgresados.idalumno','subQueryEgresados.fecha_egreso')
                    ->joinSub($subQueryEgresados, 'subQueryEgresados', 
                    function($join){
                        $join->on('subQueryEgresados.idalumno','alumno.idalumno');
                    })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

                    $contEgresados+=$egresadosPrograma;

                }

                
                // Programas DEL SGA, excepto la escuela de derecho, de la facultad de derecho y ciencias políticas, porque es de 6 años
                $programas=ProgramaURAA::where('idUnidad',1)->where('idDependencia',$dependencia->idDependencia)->whereNotNull('idSGA_PREG')
                ->where('idPrograma','!=',11)->pluck("idSGA_PREG");
    
                foreach ($programas as $programa) {
                    // INGRESANTES SGA
                    $ingresantesPrograma=PersonaSga::join('perfil','persona.per_id','perfil.per_id')
                    ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                    ->where('perfil.pfl_cond','AL')
                    ->where('persona.per_login','LIKE','%'.$anio_ingreso)
                    ->where('dependencia.dep_id',$programa)
                    ->count();
    
                    $contIngresantes+=$ingresantesPrograma;

                    // EGRESADOS SGA
                    $subQueryEgresados= PersonaSga::select('perfil.pfl_id',DB::raw('max(sga_matricula.mat_fecha) as fecha_egreso'))
                    ->join('perfil','persona.per_id','perfil.per_id')
                    ->join('sga_datos_alumno','perfil.pfl_id','sga_datos_alumno.pfl_id')
                    ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                    ->join('sga_matricula','perfil.pfl_id','sga_matricula.pfl_id')
                    ->where('perfil.pfl_estado',true)
                    ->where('sga_datos_alumno.con_id',6)
                    ->where('dependencia.dep_id',$programa)
                    ->groupBy('perfil.pfl_id');

                    $egresadosPrograma=PersonaSga::select('subQueryEgresados.pfl_id','subQueryEgresados.fecha_egreso')
                    ->join('perfil','persona.per_id','perfil.per_id')
                    ->joinSub($subQueryEgresados, 'subQueryEgresados', 
                    function($join){
                        $join->on('subQueryEgresados.pfl_id','perfil.pfl_id');
                    })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

                    $contEgresados+=$egresadosPrograma;

                }

                // Guardando la cantidad de ingresantes por dependencia
                array_push($ingresantesFacultad,$contIngresantes);
                $totalIngresantes+=$contIngresantes;
                // Guardando la cantidad de egresados por dependencia
                array_push($egresadosFacultad,$contEgresados);
                $totalEgresados+=$contEgresados;

            }

            $series1[$anio-$i]=[
                [
                    "name"=>"Ingresantes",
                    "type"=>"line",
                    "data"=>$ingresantesFacultad
                ]
                ,
                [
                    "name"=>"Egresados",
                    "type"=>"column",
                    "data"=>$egresadosFacultad
                ]
            ];

            // INDICADOR DE 6 AÑOS

            /* restando $i al año de egreso para que se el año actual y el anterior, además se resta 4 para que sean ingresantes de 6 años antes */
            $anio_ingreso=substr((($anio-$i)-5), -2);

            $ingresantesFacultad=array(); // array de ingresantes por programa que van en las series
            $egresadosFacultad=array(); // array de egresados por programa que van en las series

            /* Programas SUV de estomatología, farmacia y derecho para el indicador de 6 años */
            $programas=ProgramaURAA::select('nombre', 'idSUV_PREG')->where('idUnidad',1)->whereIn('idPrograma',[18,19,47])->orderBy('nombre')->get();
            
            // Recorriendo los programas del SUV
            foreach ($programas as $key => $programa) {
                if (count($labels2)<3) {
                    array_push($labels2,$programa->nombre); // Labels para el gráfico de 6 años
                }
                
                // INGRESANTES SUV
                $ingresantesPrograma=Alumno::join('patrimonio.area', 'alumno.idarea','area.idarea')
                ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
                ->where('alumno.idalumno','LIKE','%'.$anio_ingreso)
                ->where('estructura.idestructura',$programa->idSUV_PREG)
                ->count();

                // $ingresantesFacultad[$key]+=$ingresantesPrograma;
                array_push($ingresantesFacultad,$ingresantesPrograma);

                $totalIngresantes+=$ingresantesPrograma;

                // EGRESADOS SUV
                $subQueryEgresados=Alumno::select('alumno.idalumno',DB::raw('max(matricula.mat_fecha) as fecha_egreso'))
                ->join('matriculas.matricula','matricula.idalumno', 'alumno.idalumno')
                ->join('patrimonio.area', 'alumno.idarea','area.idarea')
                ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
                ->where('alumno.alu_estado',6)
                ->where('estructura.idestructura',$programa->idSUV_PREG)
                ->groupBy('alumno.idalumno')
                ;
                
                $egresadosPrograma=Alumno::select('subQueryEgresados.idalumno','subQueryEgresados.fecha_egreso')
                ->joinSub($subQueryEgresados, 'subQueryEgresados', 
                function($join){
                    $join->on('subQueryEgresados.idalumno', '=', 'alumno.idalumno');
                })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

                // $egresadosFacultad[$key]+=$egresadosPrograma;
                array_push($egresadosFacultad,$egresadosPrograma);
                $totalEgresados+=$egresadosPrograma;
                
            }
            
            /* Programas SGA de estomatología, farmacia y derecho para el indicador de 6 años */
            $programas=ProgramaURAA::where('idUnidad',1)->whereIn('idPrograma',[18,19,11])->orderBy('nombre')->pluck('idSGA_PREG');
            foreach ($programas as $key => $programa) {                
                // INGRESANTES SGA
                $ingresantesPrograma=PersonaSga::join('perfil','persona.per_id','perfil.per_id')
                ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                ->where('perfil.pfl_cond','AL')
                ->where('persona.per_login','LIKE','%'.$anio_ingreso)
                ->where('dependencia.dep_id',$programa)
                ->count();

                $ingresantesFacultad[$key]+=$ingresantesPrograma;

                $totalIngresantes+=$ingresantesPrograma;
                // EGRESADOS SGA
                $subQueryEgresados= PersonaSga::select('perfil.pfl_id',DB::raw('max(sga_matricula.mat_fecha) as fecha_egreso'))
                ->join('perfil','persona.per_id','perfil.per_id')
                ->join('sga_datos_alumno','perfil.pfl_id','sga_datos_alumno.pfl_id')
                ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                ->join('sga_matricula','perfil.pfl_id','sga_matricula.pfl_id')
                ->where('perfil.pfl_estado',true)
                ->where('sga_datos_alumno.con_id',6)
                ->where('dependencia.dep_id',$programa)
                ->groupBy('perfil.pfl_id');

                $egresadosPrograma=PersonaSga::select('subQueryEgresados.pfl_id','subQueryEgresados.fecha_egreso')
                ->join('perfil','persona.per_id','perfil.per_id')
                ->joinSub($subQueryEgresados, 'subQueryEgresados', 
                function($join){
                    $join->on('subQueryEgresados.pfl_id','perfil.pfl_id');
                })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

                $egresadosFacultad[$key]+=$egresadosPrograma;
                
                $totalEgresados+=$egresadosPrograma;
            }

            $series2[$anio-$i]=[
                [
                    "name"=>"Ingresantes",
                    "type"=>"line",
                    "data"=>$ingresantesFacultad
                ]
                ,
                [
                    "name"=>"Egresados",
                    "type"=>"column",
                    "data"=>$egresadosFacultad
                ]
            ];

            // INDICADOR 7 AÑOS

            /* restando $i al año de egreso para que se el año actual y el anterior, además se resta 4 para que sean ingresantes de 6 años antes */
            $anio_ingreso=substr((($anio-$i)-6), -2);

            $ingresantesFacultad=array(); // array de ingresantes por programa que van en las series
            $egresadosFacultad=array(); // array de egresados por programa que van en las series

            // Programa de medicina de 7 años
            $programaMedicina=ProgramaURAA::select('nombre', 'idSUV_PREG','idSGA_PREG')->where('idUnidad',1)->where('idPrograma',36)->first();
            
            // LABELS DEL PROGRAMA DE MEDICINA
            if (count($labels3)<1) {
                array_push($labels3,$programaMedicina->nombre); // Labels para el gráfico de 7 añoS
            }

            // INGRESANTES SUV
            $ingresantesPrograma=Alumno::join('patrimonio.area', 'alumno.idarea','area.idarea')
            ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
            ->where('alumno.idalumno','LIKE','%'.$anio_ingreso)
            ->where('estructura.idestructura',$programaMedicina->idSUV_PREG)
            ->count();

            array_push($ingresantesFacultad,$ingresantesPrograma);

            $totalIngresantes+=$ingresantesPrograma;

            // EGRESADOS SUV
            $subQueryEgresados=Alumno::select('alumno.idalumno',DB::raw('max(matricula.mat_fecha) as fecha_egreso'))
            ->join('matriculas.matricula','matricula.idalumno', 'alumno.idalumno')
            ->join('patrimonio.area', 'alumno.idarea','area.idarea')
            ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
            ->where('alumno.alu_estado',6)
            ->where('estructura.idestructura',$programaMedicina->idSUV_PREG)
            ->groupBy('alumno.idalumno')
            ;
                
            $egresadosPrograma=Alumno::select('subQueryEgresados.idalumno','subQueryEgresados.fecha_egreso')
            ->joinSub($subQueryEgresados, 'subQueryEgresados', 
            function($join){
                $join->on('subQueryEgresados.idalumno', '=', 'alumno.idalumno');
            })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

            array_push($egresadosFacultad,$egresadosPrograma);

            $totalEgresados+=$egresadosPrograma;

            // INGRESANTES SGA
            $ingresantesPrograma=PersonaSga::join('perfil','persona.per_id','perfil.per_id')
            ->join('dependencia','dependencia.dep_id','perfil.dep_id')
            ->where('perfil.pfl_cond','AL')
            ->where('persona.per_login','LIKE','%'.$anio_ingreso)
            ->where('dependencia.dep_id',$programaMedicina->idSGA_PREG)
            ->count();

            $ingresantesFacultad[0]+=$ingresantesPrograma;

            $totalIngresantes+=$ingresantesPrograma;

            // EGRESADOS SGA
            $subQueryEgresados= PersonaSga::select('perfil.pfl_id',DB::raw('max(sga_matricula.mat_fecha) as fecha_egreso'))
            ->join('perfil','persona.per_id','perfil.per_id')
            ->join('sga_datos_alumno','perfil.pfl_id','sga_datos_alumno.pfl_id')
            ->join('dependencia','dependencia.dep_id','perfil.dep_id')
            ->join('sga_matricula','perfil.pfl_id','sga_matricula.pfl_id')
            ->where('perfil.pfl_estado',true)
            ->where('sga_datos_alumno.con_id',6)
            ->where('dependencia.dep_id',$programaMedicina->idSGA_PREG)
            ->groupBy('perfil.pfl_id');

            $egresadosPrograma=PersonaSga::select('subQueryEgresados.pfl_id','subQueryEgresados.fecha_egreso')
            ->join('perfil','persona.per_id','perfil.per_id')
            ->joinSub($subQueryEgresados, 'subQueryEgresados', 
            function($join){
                $join->on('subQueryEgresados.pfl_id','perfil.pfl_id');
            })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

            $egresadosFacultad[0]+=$egresadosPrograma;

            $totalEgresados+=$egresadosPrograma;

            $series3[$anio-$i]=[
                [
                    "name"=>"Ingresantes",
                    "type"=>"line",
                    "data"=>$ingresantesFacultad
                ]
                ,
                [
                    "name"=>"Egresados",
                    "type"=>"column",
                    "data"=>$egresadosFacultad
                ]
            ];

            $overview[$anio-$i]=[
                "ingresantes"=>$totalIngresantes,
                "egresados"=>$totalEgresados,
                "indicador"=>round((($totalEgresados/$totalIngresantes)*100),0)
            ];

            array_push($range,$anio_egreso);
            
        }
    }
    public function indicadorCarpetas(){
        // Array de respuestas
        $response=array();
        $labels1=array();
        $series1=array();
        $labels2=array();
        $series2=array();
        $labels3=array();
        $series3=array();
        $charts=array();
        $overview=array();
        $range=array();

        $anio=2022; //$request->anio

        /* Dependencias excepto farmacia, medicina y estomatología que no tienen programas de 5 años, la de derecho si se considera porque está la escuela de 
        CIENCIA POLÍTICA Y GOBERNABILIDAD */
        $dependencias=DependenciaURAA::select('denominacion','idDependencia')->where('idUnidad',1)->where('estado',1)->whereNotIn('idDependencia',[12,15,16])->get();
        foreach ($dependencias as $key => $dependencia) {
            // añadimos los labels  
            array_push($labels1,$dependencia->denominacion);
        }        

        for ($i=0; $i <=1 ; $i++) { 
            // Datos overview 
            $totalIngresantes=0;
            $totalEgresados=0;
            
            // año de egreso
            $anio_egreso=$anio-$i;  
            
            // INDICADOR DE 5 AÑOS

            /* restando $i al año de egreso para que se el año actual y el anterior, además se resta 4 para que sean ingresantes de 5 años antes */
            $anio_ingreso=substr((($anio-$i)-4), -2); 
           
            $ingresantesFacultad=array(); // array de ingresantes por facultad que van en las series
            $egresadosFacultad=array(); // array de egresados por facultad que van en las series
            
            foreach ($dependencias as $dependencia) {

                // Total de ingresantes por facultad
                $contIngresantes=0;
                // Total de egresados por facultad
                $contEgresados=0;
                            

                // Programas DEL SUV, excepto la escuela de derecho, de la facultad de derecho y ciencias políticas, porque es de 6 años
                $programas=ProgramaURAA::where('idUnidad',1)->where('idDependencia',$dependencia->idDependencia)->whereNotNull('idSUV_PREG')
                ->where('idPrograma','!=',47)->pluck("idSUV_PREG");
                
                foreach ($programas as $programa) {
                    
                    // INGRESANTES SUV
                    $ingresantesPrograma=Alumno::join('patrimonio.area', 'alumno.idarea','area.idarea')
                    ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
                    ->where('alumno.idalumno','LIKE','%'.$anio_ingreso)
                    ->where('estructura.idestructura',21)
                    ->count();
    
                    $contIngresantes+=$ingresantesPrograma;

                    // EGRESADOS SUV
                    $subQueryEgresados=Alumno::select('alumno.idalumno',DB::raw('max(matricula.mat_fecha) as fecha_egreso'))
                    ->join('matriculas.matricula','matricula.idalumno', 'alumno.idalumno')
                    ->join('patrimonio.area', 'alumno.idarea','area.idarea')
                    ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
                    ->where('alumno.alu_estado',6)
                    ->where('estructura.idestructura',21)
                    ->where('alumno.idalumno','LIKE','%'.$anio_ingreso) // Egresados con el mismo año de ingreso que los ingresantes
                    ->groupBy('alumno.idalumno')
                    ;

                    $egresadosPrograma=Alumno::select('subQueryEgresados.idalumno','subQueryEgresados.fecha_egreso')
                    ->joinSub($subQueryEgresados, 'subQueryEgresados', 
                    function($join){
                        $join->on('subQueryEgresados.idalumno','alumno.idalumno');
                    })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

                    $contEgresados+=$egresadosPrograma;

                }

                
                // Programas DEL SGA, excepto la escuela de derecho, de la facultad de derecho y ciencias políticas, porque es de 6 años
                $programas=ProgramaURAA::where('idUnidad',1)->where('idDependencia',$dependencia->idDependencia)->whereNotNull('idSGA_PREG')
                ->where('idPrograma','!=',11)->pluck("idSGA_PREG");
    
                foreach ($programas as $programa) {
                    // INGRESANTES SGA
                    $ingresantesPrograma=PersonaSga::join('perfil','persona.per_id','perfil.per_id')
                    ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                    ->where('perfil.pfl_cond','AL')
                    ->where('persona.per_login','LIKE','%'.$anio_ingreso)
                    ->where('dependencia.dep_id',$programa)
                    ->count();
    
                    $contIngresantes+=$ingresantesPrograma;

                    // EGRESADOS SGA
                    $subQueryEgresados= PersonaSga::select('perfil.pfl_id',DB::raw('max(sga_matricula.mat_fecha) as fecha_egreso'))
                    ->join('perfil','persona.per_id','perfil.per_id')
                    ->join('sga_datos_alumno','perfil.pfl_id','sga_datos_alumno.pfl_id')
                    ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                    ->join('sga_matricula','perfil.pfl_id','sga_matricula.pfl_id')
                    ->where('perfil.pfl_estado',true)
                    ->where('sga_datos_alumno.con_id',6)
                    ->where('dependencia.dep_id',$programa)
                    ->groupBy('perfil.pfl_id');

                    $egresadosPrograma=PersonaSga::select('subQueryEgresados.pfl_id','subQueryEgresados.fecha_egreso')
                    ->join('perfil','persona.per_id','perfil.per_id')
                    ->joinSub($subQueryEgresados, 'subQueryEgresados', 
                    function($join){
                        $join->on('subQueryEgresados.pfl_id','perfil.pfl_id');
                    })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

                    $contEgresados+=$egresadosPrograma;

                }

                // Guardando la cantidad de ingresantes por dependencia
                array_push($ingresantesFacultad,$contIngresantes);
                $totalIngresantes+=$contIngresantes;
                // Guardando la cantidad de egresados por dependencia
                array_push($egresadosFacultad,$contEgresados);
                $totalEgresados+=$contEgresados;

            }

            $series1[$anio-$i]=[
                [
                    "name"=>"Ingresantes",
                    "type"=>"line",
                    "data"=>$ingresantesFacultad
                ]
                ,
                [
                    "name"=>"Egresados",
                    "type"=>"column",
                    "data"=>$egresadosFacultad
                ]
            ];

            // INDICADOR DE 6 AÑOS

            /* restando $i al año de egreso para que se el año actual y el anterior, además se resta 4 para que sean ingresantes de 6 años antes */
            $anio_ingreso=substr((($anio-$i)-5), -2);

            $ingresantesFacultad=array(); // array de ingresantes por programa que van en las series
            $egresadosFacultad=array(); // array de egresados por programa que van en las series

            /* Programas SUV de estomatología, farmacia y derecho para el indicador de 6 años */
            $programas=ProgramaURAA::select('nombre', 'idSUV_PREG')->where('idUnidad',1)->whereIn('idPrograma',[18,19,47])->orderBy('nombre')->get();
            
            // Recorriendo los programas del SUV
            foreach ($programas as $key => $programa) {
                if (count($labels2)<3) {
                    array_push($labels2,$programa->nombre); // Labels para el gráfico de 6 años
                }
                
                // INGRESANTES SUV
                $ingresantesPrograma=Alumno::join('patrimonio.area', 'alumno.idarea','area.idarea')
                ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
                ->where('alumno.idalumno','LIKE','%'.$anio_ingreso)
                ->where('estructura.idestructura',$programa->idSUV_PREG)
                ->count();

                // $ingresantesFacultad[$key]+=$ingresantesPrograma;
                array_push($ingresantesFacultad,$ingresantesPrograma);

                $totalIngresantes+=$ingresantesPrograma;

                // EGRESADOS SUV
                $subQueryEgresados=Alumno::select('alumno.idalumno',DB::raw('max(matricula.mat_fecha) as fecha_egreso'))
                ->join('matriculas.matricula','matricula.idalumno', 'alumno.idalumno')
                ->join('patrimonio.area', 'alumno.idarea','area.idarea')
                ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
                ->where('alumno.alu_estado',6)
                ->where('estructura.idestructura',$programa->idSUV_PREG)
                ->groupBy('alumno.idalumno')
                ;
                
                $egresadosPrograma=Alumno::select('subQueryEgresados.idalumno','subQueryEgresados.fecha_egreso')
                ->joinSub($subQueryEgresados, 'subQueryEgresados', 
                function($join){
                    $join->on('subQueryEgresados.idalumno', '=', 'alumno.idalumno');
                })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

                // $egresadosFacultad[$key]+=$egresadosPrograma;
                array_push($egresadosFacultad,$egresadosPrograma);
                $totalEgresados+=$egresadosPrograma;
                
            }
            
            /* Programas SGA de estomatología, farmacia y derecho para el indicador de 6 años */
            $programas=ProgramaURAA::where('idUnidad',1)->whereIn('idPrograma',[18,19,11])->orderBy('nombre')->pluck('idSGA_PREG');
            foreach ($programas as $key => $programa) {                
                // INGRESANTES SGA
                $ingresantesPrograma=PersonaSga::join('perfil','persona.per_id','perfil.per_id')
                ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                ->where('perfil.pfl_cond','AL')
                ->where('persona.per_login','LIKE','%'.$anio_ingreso)
                ->where('dependencia.dep_id',$programa)
                ->count();

                $ingresantesFacultad[$key]+=$ingresantesPrograma;

                $totalIngresantes+=$ingresantesPrograma;
                // EGRESADOS SGA
                $subQueryEgresados= PersonaSga::select('perfil.pfl_id',DB::raw('max(sga_matricula.mat_fecha) as fecha_egreso'))
                ->join('perfil','persona.per_id','perfil.per_id')
                ->join('sga_datos_alumno','perfil.pfl_id','sga_datos_alumno.pfl_id')
                ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                ->join('sga_matricula','perfil.pfl_id','sga_matricula.pfl_id')
                ->where('perfil.pfl_estado',true)
                ->where('sga_datos_alumno.con_id',6)
                ->where('dependencia.dep_id',$programa)
                ->groupBy('perfil.pfl_id');

                $egresadosPrograma=PersonaSga::select('subQueryEgresados.pfl_id','subQueryEgresados.fecha_egreso')
                ->join('perfil','persona.per_id','perfil.per_id')
                ->joinSub($subQueryEgresados, 'subQueryEgresados', 
                function($join){
                    $join->on('subQueryEgresados.pfl_id','perfil.pfl_id');
                })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

                $egresadosFacultad[$key]+=$egresadosPrograma;
                
                $totalEgresados+=$egresadosPrograma;
            }

            $series2[$anio-$i]=[
                [
                    "name"=>"Ingresantes",
                    "type"=>"line",
                    "data"=>$ingresantesFacultad
                ]
                ,
                [
                    "name"=>"Egresados",
                    "type"=>"column",
                    "data"=>$egresadosFacultad
                ]
            ];

            // INDICADOR 7 AÑOS

            /* restando $i al año de egreso para que se el año actual y el anterior, además se resta 4 para que sean ingresantes de 6 años antes */
            $anio_ingreso=substr((($anio-$i)-6), -2);

            $ingresantesFacultad=array(); // array de ingresantes por programa que van en las series
            $egresadosFacultad=array(); // array de egresados por programa que van en las series

            // Programa de medicina de 7 años
            $programaMedicina=ProgramaURAA::select('nombre', 'idSUV_PREG','idSGA_PREG')->where('idUnidad',1)->where('idPrograma',36)->first();
            
            // LABELS DEL PROGRAMA DE MEDICINA
            if (count($labels3)<1) {
                array_push($labels3,$programaMedicina->nombre); // Labels para el gráfico de 7 añoS
            }

            // INGRESANTES SUV
            $ingresantesPrograma=Alumno::join('patrimonio.area', 'alumno.idarea','area.idarea')
            ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
            ->where('alumno.idalumno','LIKE','%'.$anio_ingreso)
            ->where('estructura.idestructura',$programaMedicina->idSUV_PREG)
            ->count();

            array_push($ingresantesFacultad,$ingresantesPrograma);

            $totalIngresantes+=$ingresantesPrograma;

            // EGRESADOS SUV
            $subQueryEgresados=Alumno::select('alumno.idalumno',DB::raw('max(matricula.mat_fecha) as fecha_egreso'))
            ->join('matriculas.matricula','matricula.idalumno', 'alumno.idalumno')
            ->join('patrimonio.area', 'alumno.idarea','area.idarea')
            ->join('patrimonio.estructura' , 'estructura.idestructura' , 'area.idestructura')
            ->where('alumno.alu_estado',6)
            ->where('estructura.idestructura',$programaMedicina->idSUV_PREG)
            ->groupBy('alumno.idalumno')
            ;
                
            $egresadosPrograma=Alumno::select('subQueryEgresados.idalumno','subQueryEgresados.fecha_egreso')
            ->joinSub($subQueryEgresados, 'subQueryEgresados', 
            function($join){
                $join->on('subQueryEgresados.idalumno', '=', 'alumno.idalumno');
            })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

            array_push($egresadosFacultad,$egresadosPrograma);

            $totalEgresados+=$egresadosPrograma;

            // INGRESANTES SGA
            $ingresantesPrograma=PersonaSga::join('perfil','persona.per_id','perfil.per_id')
            ->join('dependencia','dependencia.dep_id','perfil.dep_id')
            ->where('perfil.pfl_cond','AL')
            ->where('persona.per_login','LIKE','%'.$anio_ingreso)
            ->where('dependencia.dep_id',$programaMedicina->idSGA_PREG)
            ->count();

            $ingresantesFacultad[0]+=$ingresantesPrograma;

            $totalIngresantes+=$ingresantesPrograma;

            // EGRESADOS SGA
            $subQueryEgresados= PersonaSga::select('perfil.pfl_id',DB::raw('max(sga_matricula.mat_fecha) as fecha_egreso'))
            ->join('perfil','persona.per_id','perfil.per_id')
            ->join('sga_datos_alumno','perfil.pfl_id','sga_datos_alumno.pfl_id')
            ->join('dependencia','dependencia.dep_id','perfil.dep_id')
            ->join('sga_matricula','perfil.pfl_id','sga_matricula.pfl_id')
            ->where('perfil.pfl_estado',true)
            ->where('sga_datos_alumno.con_id',6)
            ->where('dependencia.dep_id',$programaMedicina->idSGA_PREG)
            ->groupBy('perfil.pfl_id');

            $egresadosPrograma=PersonaSga::select('subQueryEgresados.pfl_id','subQueryEgresados.fecha_egreso')
            ->join('perfil','persona.per_id','perfil.per_id')
            ->joinSub($subQueryEgresados, 'subQueryEgresados', 
            function($join){
                $join->on('subQueryEgresados.pfl_id','perfil.pfl_id');
            })->whereYear('subQueryEgresados.fecha_egreso',($anio_egreso))->count();

            $egresadosFacultad[0]+=$egresadosPrograma;

            $totalEgresados+=$egresadosPrograma;

            $series3[$anio-$i]=[
                [
                    "name"=>"Ingresantes",
                    "type"=>"line",
                    "data"=>$ingresantesFacultad
                ]
                ,
                [
                    "name"=>"Egresados",
                    "type"=>"column",
                    "data"=>$egresadosFacultad
                ]
            ];

            $overview[$anio-$i]=[
                "ingresantes"=>$totalIngresantes,
                "egresados"=>$totalEgresados,
                "indicador"=>round((($totalEgresados/$totalIngresantes)*100),0)
            ];

            array_push($range,$anio_egreso);
            
        }

        $charts=[
                [
                    "labels"=>$labels1,
                    "series"=>$series1
                ],
                [
                    "labels"=>$labels2,
                    "series"=>$series2
                ],
                [
                    "labels"=>$labels3,
                    "series"=>$series3
                ]
            ];
        
        $response=[
            "charts"=>$charts,
            "overview"=>$overview,
            "range"=>$range
        ];
        return $response;
    }
}

