<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Mail\Mailable;
use Carbon\Carbon;
use App\Tramite;
use App\Tipo_Tramite;
use App\Tipo_Tramite_Unidad;
use App\Historial_Estado;
use App\Tramite_Requisito;
use App\Voucher;
use App\User;
use App\Tramite_Detalle;
use App\Estado_Tramite;
use App\MatriculaSUV;
use App\Resolucion;
use App\Libro;
// use App\Jobs\RegistroTramiteJob;
// use App\Jobs\EnvioCertificadoJob;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

use App\PersonaSE;

use App\Mencion;
use App\Escuela;
use App\PersonaSuv;
use App\PersonaSga;
use App\Alumno;
use App\Acreditacion;
class GradoController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }
    public function GetGradosValidadosEscuela(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',17)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia_detalle',$idDependencia);
                }
            })
            // ->where('tramite.idDependencia_detalle',$idDependencia)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',17)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia_detalle',$idDependencia);
                }
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
        // return response()->json(['status' => '200', 'tramites' => $tramites], 200);
    }
    public function GetGradosAprobadosEscuela(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',30)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia_detalle',$idDependencia);
                }
            })
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',30)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia_detalle',$idDependencia);
                }
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
        // return response()->json(['status' => '200', 'tramites' => $tramites], 200);
    }


    public function GetGradosRevalidadosEscuela(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',31)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia_detalle',$idDependencia);
                }
            })
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',31)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia_detalle',$idDependencia);
                }
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
        // return response()->json(['status' => '200', 'tramites' => $tramites], 200);
    }


    public function GetGradosValidadosFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',20)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',20)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
        // return response()->json(['status' => '200', 'tramites' => $tramites], 200);
    }
    public function GetGradosAprobadosFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',32)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',32)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
        // return response()->json(['status' => '200', 'tramites' => $tramites], 200);
    }


    public function GetGradosRevalidadosFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',33)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',33)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
        // return response()->json(['status' => '200', 'tramites' => $tramites], 200);
    }





    public function GetGradosFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',20)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            // ->where('tramite_detalle.asignado_certificado',$idUsuario)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',20)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
        // return response()->json(['status' => '200', 'tramites' => $tramites], 200);
    }
    
    
    public function cambiarEstado(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->Find($request->idTramite); 

            $tramite->idEstado_tramite=$request->newEstado;
            $tramite->save();


            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            // foreach ($tramite->requisitos as $key => $requisito) {
            //     if ($tramite->idEstado_tramite==33) {
            //         if ($requisito->responsable==8) {
            //             $tramite_requisito=Tramite_Requisito::where('idTramite',$tramite->idTramite)->where('idRequisito',$requisito->idRequisito)->first();
            //             $tramite_requisito->archivo=null;
            //             $tramite_requisito->save();
            //         }
            //     }else {
            //         if ($requisito->responsable==5) {
            //             $tramite_requisito=Tramite_Requisito::where('idTramite',$tramite->idTramite)->where('idRequisito',$requisito->idRequisito)->first();
            //             $tramite_requisito->archivo=null;
            //             $tramite_requisito->save();
            //         }
            //     }
            // }
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
            $tramite->escuela=$dependenciaDetalle->nombre;


            //OBTENIENDO EL HISTORIAL AL QUE SE DESEA REGRESAR
            $historial_new=Historial_Estado::where('idTramite',$tramite->idTramite)
            ->where('idEstado_nuevo',$request->newEstado)
            ->orderBy('fecha','desc')
            ->limit(1)
            ->first();

            // CAMBIANDO EL ESTADO=0 A CADA UNO DE LOS HITORIALES MAYORES AL HISTORIAL QUE SE DESEA REGRESAR
            $historiales=Historial_Estado::where('idTramite',$tramite->idTramite)
            ->where('idHistorial_estado','>',$historial_new->idHistorial_estado)
            ->get();
            foreach ($historiales as $key => $historial) {
                $historial->estado=0;
                $historial->save();
            }
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
    public function enviarFacultad(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->Find($request->idTramite);
            
            //REGISTRAMOS EL ESTADO DEL TRÁMITE
            $historial_estados=new Historial_Estado;
            $historial_estados->idTramite=$tramite->idTramite;
            $historial_estados->idUsuario=$idUsuario;
            $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
            $historial_estados->idEstado_nuevo=34;
            $historial_estados->fecha=date('Y-m-d h:i:s');
            $historial_estados->save();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estados=new Historial_Estado;
            $historial_estados->idTramite=$tramite->idTramite;
            $historial_estados->idUsuario=$idUsuario;
            $historial_estados->idEstado_actual=34;
            $historial_estados->idEstado_nuevo=20;
            $historial_estados->fecha=date('Y-m-d h:i:s');
            $historial_estados->save();

            $tramite->idEstado_tramite=20;
            $tramite->save();


            // OBTENIENDO LOS REQUISITOS Y DATOS ADICIONALES DEL TRÁMITE
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;

            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function enviarUraa(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->Find($request->idTramite);
            
            //REGISTRAMOS EL ESTADO DEL TRÁMITE
            $historial_estados=new Historial_Estado;
            $historial_estados->idTramite=$tramite->idTramite;
            $historial_estados->idUsuario=$idUsuario;
            $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
            $historial_estados->idEstado_nuevo=35;
            $historial_estados->fecha=date('Y-m-d h:i:s');
            $historial_estados->save();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estados=new Historial_Estado;
            $historial_estados->idTramite=$tramite->idTramite;
            $historial_estados->idUsuario=$idUsuario;
            $historial_estados->idEstado_actual=35;
            $historial_estados->idEstado_nuevo=7;
            $historial_estados->fecha=date('Y-m-d h:i:s');
            $historial_estados->save();

            $tramite->idEstado_tramite=7;
            $tramite->save();


            // OBTENIENDO LOS REQUISITOS Y DATOS ADICIONALES DEL TRÁMITE
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;

            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }


    public function enviarEscuela(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->Find($request->idTramite);
            
            //REGISTRAMOS EL ESTADO DEL TRÁMITE
            $historial_estados=new Historial_Estado;
            $historial_estados->idTramite=$tramite->idTramite;
            $historial_estados->idUsuario=$idUsuario;
            $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
            $historial_estados->idEstado_nuevo=40;
            $historial_estados->fecha=date('Y-m-d h:i:s');
            $historial_estados->save();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estados=new Historial_Estado;
            $historial_estados->idTramite=$tramite->idTramite;
            $historial_estados->idUsuario=$idUsuario;
            $historial_estados->idEstado_actual=40;
            $historial_estados->idEstado_nuevo=36;
            $historial_estados->fecha=date('Y-m-d h:i:s');
            $historial_estados->save();

            $tramite->idEstado_tramite=36;
            $tramite->save();


            // OBTENIENDO LOS REQUISITOS Y DATOS ADICIONALES DEL TRÁMITE
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;

            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }


    public function GetGradosValidadosUra(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
            'tramite_detalle.certificado_final')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',7)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            // ->where('tramite_detalle.asignado_certificado',$idUsuario)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
            'tramite_detalle.certificado_final')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',7)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
        // return response()->json(['status' => '200', 'tramites' => $tramites], 200);
    }


    public function GetGradosDatosDiplomaEscuela(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','tramite.created_at')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',36)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia_detalle',$idDependencia);
                }
            })
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','tramite.created_at')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',36)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia_detalle',$idDependencia);
                }
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            // obtener primera y última matrícula de cada usuario que realiza el trámite medienta esu número de matrícula
            if ($tramite->idUnidad==1) {
                // Verificando el SUV
                $matriculaPrimera=MatriculaSUV::select('mat_fecha')->where('idalumno',$tramite->nro_matricula)->first();
                if ($matriculaPrimera) {
                    $tramite->fecha_primera_matricula=$matriculaPrimera->mat_fecha;
                    $matriculaUltima=MatriculaSUV::select('mat_fecha')->where('idalumno',$tramite->nro_matricula)->orderBy('mat_fecha','desc')
                    ->limit(1)
                    ->first();
                    $tramite->fecha_ultima_matricula=$matriculaUltima->mat_fecha;
                    // NUMERO DE CRÉDITOS
                    $nro_creditos=Alumno::select('alu_nrocrdsaprob')->where('idalumno',$tramite->nro_matricula)->first();
                    $tramite->nro_creditos_carpeta=$nro_creditos->alu_nrocrdsaprob;
                }else {
                    // Verificando SGA 
                    $matriculaPrimera=PersonaSga::select('mat_fecha')->join('perfil','persona.per_id','perfil.per_id')
                    ->join('sga_matricula','sga_matricula.pfl_id','perfil.pfl_id')
                    ->where('persona.per_login',$tramite->nro_matricula)->first();
                    $tramite->fecha_primera_matricula=$matriculaPrimera->mat_fecha;
                    // ----------------------------------------------------------------
                    $matriculaUltima=PersonaSga::select('mat_fecha')->join('perfil','persona.per_id','perfil.per_id')
                    ->join('sga_matricula','sga_matricula.pfl_id','perfil.pfl_id')
                    ->where('persona.per_login',$tramite->nro_matricula)
                    ->orderBy('mat_fecha','desc')
                    ->limit(1)
                    ->first();
                    $tramite->fecha_ultima_matricula=$matriculaUltima->mat_fecha;
                    // Número de créditos SGA
                    $sql=PersonaSga::select('cur.cur_id', 'dma.dma_vez', 'cur.cur_creditos', 'n.not_pr', 'n.not_ap')
                    ->join('perfil','persona.per_id','perfil.per_id')
                    ->join('sga_matricula as mat','mat.pfl_id','perfil.pfl_id')
                    ->join('sga_det_matricula as dma','mat.mat_id','dma.mat_id')
                    ->join('sga_curso as cur' , 'cur.cur_id','dma.cur_id')
                    ->join('sga_notas as n' , 'n.dma_id' , 'dma.dma_id')
                    ->join('sga_datos_alumno as da' , 'mat.pfl_id' , 'da.pfl_id')
                    ->join('sga_historico_curricula as hc' , 'da.hcr_id' , 'hc.hcr_id')
                    ->where('dma.dma_estado', '!=','0')
                    ->where('cur.cur_estado','1')
                    ->where('n.not_pr','!=','')
                    ->where(function($query)
                    {
                        $query->where('mat.mat_estado' , '1')
                        ->orWhere('mat.mat_estado' , '3');
                    })
                    ->where('persona.per_login',$tramite->nro_matricula)
                    ->orderby('cur.cur_id', 'DESC')
                    ->orderby('dma.dma_vez', 'DESC')
                    ->get()
                    ;

                    $rows_cursos = $sql;
                    $prom_temporal = 0;
                    $vez_temporal = 0;
                    $cod_temporal = 0;
                    $cred_temporal = 0;
                    $creditos = 0;
                    $total_cred = 0;
                    $total_cur = 0;
                    $total_cred_ap = 0;
                    $total_cur_ap = 0;
                    $total_prom = 0;
                    
                    for( $i = 0, $n = count( $rows_cursos ); $i < $n; $i++ ){
                        $obj_cursos = $rows_cursos[$i];
                        
                        if( $obj_cursos->not_pr > 10 or $obj_cursos->not_ap > 10){
                            $total_cred_ap = $total_cred_ap + $obj_cursos->cur_creditos;
                        }
                    }
                    $tramite->nro_creditos_carpeta=$total_cred_ap;
                }
                
            }elseif ($tramite->idUnidad==2) {
                # code...
            }elseif ($tramite->idUnidad==3) {
                # code...
            }else {
                // Verificando SGA 
                $matriculaPrimera=PersonaSE::select('matricula.fecha_hora')->join('matricula','alumno.idAlumno','matricula.idAlumno')
                ->where('alumno.codigo',$tramite->nro_matricula)->first();
                $tramite->fecha_primera_matricula=$matriculaPrimera->fecha_hora;
                // ----------------------------------------------------------------
                $matriculaUltima=PersonaSE::select('matricula.fecha_hora')->join('matricula','alumno.idAlumno','matricula.idAlumno')
                ->where('alumno.codigo',$tramite->nro_matricula)
                ->orderBy('fecha_hora','desc')
                ->limit(1)
                ->first();
                $tramite->fecha_ultima_matricula=$matriculaUltima->fecha_hora;

            }
            // // numero de años
            // $fechaUltima = Carbon::parse($tramite->fecha_ultima_matricula);
            // $fechaPrimera = Carbon::parse($tramite->fecha_primera_matricula);
            // $tramite->nro_años=($fechaUltima->year-$fechaPrimera->year)+1;
            // --------------
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
            // Verificación de escuela acreditada
            $acreditacion=Acreditacion::where('fecha_inicio','<=',$tramite->fecha_colacion)->where('fecha_fin','>=',$tramite->fecha_colacion)->first();
            if ($acreditacion) {
                $tramite->dependencia_acreditado="SÍ";
                $tramite->fecha_inicio=$acreditacion->fecha_inicio;
                $tramite->fecha_fin=$acreditacion->fecha_fin;
                $tramite->idAcreditacion=$acreditacion->idAcreditacion;
            }else {
                $tramite->dependencia_acreditado="NO";
                $tramite->fecha_inicio=null;
                $tramite->fecha_fin=null;
                $tramite->idAcreditacion=null;
            }
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }


    public function GetGradosDatosDiplomaFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
            'tramite_detalle.idAcreditacion','tramite_detalle.fecha_inicio_acto_academico')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',38)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
            'tramite_detalle.idAcreditacion','tramite_detalle.fecha_inicio_acto_academico')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',38)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
            // Verificación de escuela acreditada
            $acreditacion=Acreditacion::where('fecha_inicio','<=',$tramite->fecha_colacion)->where('fecha_fin','>=',$tramite->fecha_colacion)->first();
            if ($acreditacion) {
                $tramite->dependencia_acreditado="SÍ";
                $tramite->fecha_inicio=$acreditacion->fecha_inicio;
                $tramite->fecha_fin=$acreditacion->fecha_fin;
                $tramite->idAcreditacion=$acreditacion->idAcreditacion;
            }else {
                $tramite->dependencia_acreditado="NO";
                $tramite->fecha_inicio=null;
                $tramite->fecha_fin=null;
                $tramite->idAcreditacion=null;
            }
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetGradosDatosDiplomaUra(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
            'tramite_detalle.idAcreditacion','tramite_detalle.fecha_inicio_acto_academico')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',39)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            // ->where('tramite_detalle.asignado_certificado',$idUsuario)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
            'tramite_detalle.idAcreditacion','tramite_detalle.fecha_inicio_acto_academico')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',39)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
            // Verificación de escuela acreditada
            $acreditacion=Acreditacion::where('fecha_inicio','<=',$tramite->fecha_colacion)->where('fecha_fin','>=',$tramite->fecha_colacion)->first();
            if ($acreditacion) {
                $tramite->dependencia_acreditado="SÍ";
                $tramite->fecha_inicio=$acreditacion->fecha_inicio;
                $tramite->fecha_fin=$acreditacion->fecha_fin;
                $tramite->idAcreditacion=$acreditacion->idAcreditacion;
            }else {
                $tramite->dependencia_acreditado="NO";
                $tramite->fecha_inicio=null;
                $tramite->fecha_fin=null;
                $tramite->idAcreditacion=null;
            }
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }


    public function GuardarDatosDiploma(Request $request){
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            // RETORNANDO EL GRADO EDITADO
            $tramite=Tramite::find($request->idTramite);
            // AÑADIENDO LOS DATOS DEL DIPLOMA SUBIDOS POR LA ESCUELA AL DETALLE DEL TRÁMITE
            $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
            $tramite_detalle->idModalidad_carpeta=$request->idModalidad_carpeta;
            $tramite_detalle->fecha_sustentacion_carpeta =$request->fecha_sustentacion_carpeta;
            $tramite_detalle->nombre_trabajo_carpeta=trim($request->nombre_trabajo_carpeta);
            $tramite_detalle->url_trabajo_carpeta=trim($request->url_trabajo_carpeta);
            $tramite_detalle->nro_creditos_carpeta=$request->nro_creditos_carpeta;
            $tramite_detalle->idPrograma_estudios_carpeta=$request->idPrograma_estudios_carpeta;
            $tramite_detalle->fecha_primera_matricula=$request->fecha_primera_matricula;
            $tramite_detalle->fecha_ultima_matricula=$request->fecha_ultima_matricula;
            $tramite_detalle->idDiploma_carpeta=$request->idDiploma_carpeta;
            $tramite_detalle->idAcreditacion=$request->idAcreditacion;
            $tramite_detalle->fecha_inicio_acto_academico=$request->fecha_inicio_acto_academico;
            $tramite_detalle->update();

            // Cambiando el estado
            if ($tramite->idEstado_tramite==36) {
    
    
                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                $historial_estados->idEstado_nuevo=37;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
    
                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=37;
                $historial_estados->idEstado_nuevo=38;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
                
                // Nuevo estado del trámite
                $tramite->idEstado_tramite=38;
            }elseif($tramite->idEstado_tramite==38) {
    
    
                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                $historial_estados->idEstado_nuevo=7;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
                
                // Nuevo estado del trámite
                $tramite->idEstado_tramite=7;
            }elseif ($tramite->idEstado_tramite==50) {
                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                $historial_estados->idEstado_nuevo=42;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();

                // Nuevo estado del trámite
                $tramite->idEstado_tramite=42;
            }else {
    
    
                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                $historial_estados->idEstado_nuevo=41;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=41;
                $historial_estados->idEstado_nuevo=42;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
                
                // Nuevo estado del trámite
                $tramite->idEstado_tramite=42;
            }
            $tramite->save();

            // RETORNANDO EL GRADO EDITADO
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion'
            ,'tramite_detalle.fecha_inicio_acto_academico')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->find($request->idTramite);
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }


    public function GetGradosValidadosSecretaria(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',42)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            // ->where('tramite_detalle.asignado_certificado',$idUsuario)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',42)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
            // Verificación de escuela acreditada
            $acreditacion=Acreditacion::where('fecha_inicio','<=',$tramite->fecha_colacion)->where('fecha_fin','>=',$tramite->fecha_colacion)->first();
            if ($acreditacion) {
                $tramite->dependencia_acreditado="SÍ";
                $tramite->fecha_inicio=$acreditacion->fecha_inicio;
                $tramite->fecha_fin=$acreditacion->fecha_fin;
                $tramite->idAcreditacion=$acreditacion->idAcreditacion;
            }else {
                $tramite->dependencia_acreditado="NO";
                $tramite->fecha_inicio=null;
                $tramite->fecha_fin=null;
                $tramite->idAcreditacion=null;
            }
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetGradosRechazadosSecretaria(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',50)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            // ->where('tramite_detalle.asignado_certificado',$idUsuario)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',50)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
            // Verificación de escuela acreditada
            $acreditacion=Acreditacion::where('fecha_inicio','<=',$tramite->fecha_colacion)->where('fecha_fin','>=',$tramite->fecha_colacion)->first();
            if ($acreditacion) {
                $tramite->dependencia_acreditado="SÍ";
                $tramite->fecha_inicio=$acreditacion->fecha_inicio;
                $tramite->fecha_fin=$acreditacion->fecha_fin;
                $tramite->idAcreditacion=$acreditacion->idAcreditacion;
            }else {
                $tramite->dependencia_acreditado="NO";
                $tramite->fecha_inicio=null;
                $tramite->fecha_fin=null;
                $tramite->idAcreditacion=null;
            }
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetGradosAprobadosSecretaria(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',44)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            // ->where('tramite_detalle.asignado_certificado',$idUsuario)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',44)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetGradosResolucion(Request $request,$nro_resolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];


        $resolucion=Resolucion::where('nro_resolucion','LIKE','%'.$nro_resolucion.'%')->orderBy('fecha','desc')
        ->limit(1)
        ->first();

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','tramite_detalle.nro_libro',
            'tramite_detalle.folio','tramite_detalle.nro_registro')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',42)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','tramite_detalle.nro_libro',
            'tramite_detalle.folio','tramite_detalle.nro_registro')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',42)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200','resolucion'=>$resolucion, 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function registrarEnLibro(Request $request){
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            // Recorremos todos los trámites y le añadimos su numeracion a cada uno
            $tramites=Tramite::select('tramite.*')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',42)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where('tipo_tramite_unidad.idTipo_tramite',2)
            ->where('resolucion.idResolucion',$request->idResolucion)
            ->orderBy('cronograma_carpeta.fecha_colacion','asc')
            ->orderBy('usuario.nombres','asc')
            ->orderBy('usuario.apellidos','asc')
            ->get();  
            
            foreach ($tramites as $key => $tramite) {
                // obtenemos datos del último registro del libro
                $ultimoRegistro=Libro::orderBy('nro_registro','desc')
                ->limit(1)
                ->first();
                
                // GUARDAMOS EL REGISTRO EN EL LIBRO
                $newRegistro=new Libro();
                if ($ultimoRegistro->folio==200 && $ultimoRegistro->contador==20) {
                    $newRegistro->nro_libro=$ultimoRegistro->nro_libro+1;
                    $newRegistro->folio=1;
                    $newRegistro->nro_registro=$ultimoRegistro->nro_registro+1;
                    $newRegistro->contador=1;
                }elseif ($ultimoRegistro->contador==20) {
                    $newRegistro->nro_libro=$ultimoRegistro->nro_libro;
                    $newRegistro->folio=$ultimoRegistro->folio+1;
                    $newRegistro->nro_registro=$ultimoRegistro->nro_registro+1;
                    $newRegistro->contador=1;
                }else {
                    $newRegistro->nro_libro=$ultimoRegistro->nro_libro;
                    $newRegistro->folio=$ultimoRegistro->folio;
                    $newRegistro->nro_registro=$ultimoRegistro->nro_registro+1;
                    $newRegistro->contador=$ultimoRegistro->contador+1;
                }
                $newRegistro->save();
                //Obtenemos el detalle de cada uno de los trámites Y ACTUALIZAMOS LOS DATOS QUE VAN EN EL LIBRO
                $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                $tramite_detalle->nro_libro=$newRegistro->nro_libro;
                $tramite_detalle->folio=$newRegistro->folio;
                $tramite_detalle->nro_registro=$newRegistro->nro_registro;
                $tramite_detalle->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                $historial_estados->idEstado_nuevo=43;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=43;
                $historial_estados->idEstado_nuevo=44;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();

                $tramite->idEstado_tramite=44;
                $tramite->save();
            }

            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite as codigo','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion'
            ,'tramite_detalle.nro_libro','tramite_detalle.folio','tramite_detalle.nro_registro')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',44)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where('resolucion.idResolucion',$request->idResolucion)
            ->orderBy('cronograma_carpeta.fecha_colacion','asc')
            ->orderBy('usuario.nombres','asc')
            ->orderBy('usuario.apellidos','asc')
            ->get();  

            DB::commit();
            return response()->json($tramites, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function GetGradosFirmaDecano(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($idDependencia) {
            if ($request->query('search')!="") {
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
                'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
                'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->where('tramite.idEstado_tramite',13)
                ->where('tipo_tramite.idTipo_tramite',2)
                ->where('tramite.idTipo_tramite_unidad',15)
                ->where(function($query) use ($idDependencia)
                {
                    if ($idDependencia!=null) {
                        $query->where('tramite.idDependencia',$idDependencia)
                        ->orWhere('dependencia.idDependencia2',$idDependencia);
                    }
                })
                // ->where('tramite.idDependencia_detalle',$idDependencia)
                ->where(function($query) use ($request)
                {
                    $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                    ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                    ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
                })
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();
            }else {
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
                'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
                'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->where('tramite.idEstado_tramite',13)
                ->where('tipo_tramite.idTipo_tramite',2)
                ->where('tramite.idTipo_tramite_unidad',15)
                ->where(function($query) use ($idDependencia)
                {
                    if ($idDependencia!=null) {
                        $query->where('tramite.idDependencia',$idDependencia)
                        ->orWhere('dependencia.idDependencia2',$idDependencia);
                    }
                })
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();   
            }
        }else {
            if ($request->query('search')!="") {
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
                'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
                'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->where('tramite.idEstado_tramite',13)
                ->where('tipo_tramite.idTipo_tramite',2)
                ->where('tramite.idTipo_tramite_unidad',15)
                
                // ->where('tramite.idDependencia_detalle',$idDependencia)
                ->where(function($query) use ($request)
                {
                    $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                    ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                    ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
                })
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();
            }else {
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
                'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
                'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->where('tramite.idEstado_tramite',13)
                ->where('tipo_tramite.idTipo_tramite',2)
                ->where('tramite.idTipo_tramite_unidad',15)
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();   
            }
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->diploma_final="diploma/".$tramite->idTramite;

            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetGradosFirmaSecretaria(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',46)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            // ->where(function($query) use ($idDependencia)
            // {
            //     if ($idDependencia) {
            //         $query->where('tramite.idDependencia_detalle',$idDependencia);
            //     }
            // })
            // ->where('tramite.idDependencia_detalle',$idDependencia)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',46)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            // ->where(function($query) use ($idDependencia)
            // {
            //     if ($idDependencia) {
            //         $query->where('tramite.idDependencia_detalle',$idDependencia);
            //     }
            // })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
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
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetGradosFirmaRector(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',48)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            // ->where(function($query) use ($idDependencia)
            // {
            //     if ($idDependencia) {
            //         $query->where('tramite.idDependencia_detalle',$idDependencia);
            //     }
            // })
            // ->where('tramite.idDependencia_detalle',$idDependencia)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                // ->orWhere('tipo_tramite.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',48)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            // ->where(function($query) use ($idDependencia)
            // {
            //     if ($idDependencia) {
            //         $query->where('tramite.idDependencia_detalle',$idDependencia);
            //     }
            // })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
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
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetGradosPendientesImpresion(Request $request,$nro_resolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        $resolucion=Resolucion::where('nro_resolucion','LIKE','%'.$nro_resolucion.'%')->orderBy('fecha','desc')
        ->limit(1)
        ->first();

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final','tramite_detalle.codigo_diploma',
            'tramite_detalle.impresion','resolucion.nro_resolucion','tramite_detalle.nro_libro','tramite_detalle.folio'
            ,'tramite_detalle.nro_registro','tramite_detalle.observacion_diploma')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',44)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where('resolucion.nro_resolucion',$nro_resolucion)
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
            ->orderBy('tramite_detalle.nro_libro', 'asc')
            ->orderBy('tramite_detalle.folio', 'asc')
            ->orderBy('tramite_detalle.nro_registro', 'asc')
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
            'cronograma_carpeta.fecha_colacion','tramite_detalle.diploma_final','tramite_detalle.codigo_diploma',
            'tramite_detalle.impresion','resolucion.nro_resolucion','tramite_detalle.nro_libro','tramite_detalle.folio'
            ,'tramite_detalle.nro_registro','tramite_detalle.observacion_diploma')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',44)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where('resolucion.nro_resolucion','like','%'.$nro_resolucion.'%')
            ->orderBy('tramite_detalle.nro_libro', 'asc')
            ->orderBy('tramite_detalle.folio', 'asc')
            ->orderBy('tramite_detalle.nro_registro', 'asc')
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
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
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200','resolucion'=>$resolucion, 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function uploadDiploma(Request $request, $id){
        DB::beginTransaction();
        try {
            // return $request->all();
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
            'tramite_detalle.diploma_final','tramite.idTramite_detalle')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->Find($id);
            // Datos de correo
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)->first();
            $tipo_tramite = Tipo_Tramite::select('tipo_tramite.idTipo_tramite','tipo_tramite.descripcion')
            ->join('tipo_tramite_unidad', 'tipo_tramite_unidad.idTipo_tramite', 'tipo_tramite.idTipo_tramite')
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad', $tramite->idTipo_tramite_unidad)->first();
            $usuario = User::findOrFail($tramite->idUsuario);

            $tramite_detalle=Tramite_detalle::find($tramite['idTramite_detalle']);
            // if ($tramite->idEstado_tramite==13) {
            //     if($request->hasFile("archivo")){
            //         $file=$request->file("archivo");
            //         $nombre = $tramite->codigo.'.'.$file->guessExtension();
            //         $nombreBD = "/storage/diplomas/".$nombre;
            //         if($file->guessExtension()=="pdf"){
            //             $file->storeAs('public/diplomas', $nombre);
            //             $tramite_detalle->diploma_final = $nombreBD;
            //         }
            //     }else {
            //         DB::rollback();
            //         return response()->json(['status' => '400', 'message' =>"Adjuntar el diploma."], 400);
            //     }
            // }else {
                if($request->hasFile("archivo")){
                    $file=$request->file("archivo");
                    if ($tramite->codigo."_firmado.pdf"==$file->getClientOriginalName()) {
                        $nombre = $tramite->codigo.'.'.$file->guessExtension();
                        $nombreBD = "/storage/diplomas/".$nombre;
                        if($file->guessExtension()=="pdf"){
                            $file->storeAs('public/diplomas', $nombre);
                            $tramite_detalle->diploma_final = $nombreBD;
                        }
                    }else {
                        return response()->json(['status' => '400', 'message' =>"El Documento no es el correcto"], 400);
                    }
                }else {
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' =>"Adjuntar el diploma firmado."], 400);
                }
            // }
            $tramite_detalle->update();
            
            if ($tramite->idEstado_tramite==13) {
                $tramite->idEstado_tramite=46;
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=13;
                $historial_estados->idEstado_nuevo=14;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=14;
                $historial_estados->idEstado_nuevo=46;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
            }elseif ($tramite->idEstado_tramite==46) {
                $tramite->idEstado_tramite=48;
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=46;
                $historial_estados->idEstado_nuevo=47;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=47;
                $historial_estados->idEstado_nuevo=48;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
            }elseif ($tramite->idEstado_tramite==48) {
                $tramite->idEstado_tramite=44;
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=48;
                $historial_estados->idEstado_nuevo=49;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=49;
                $historial_estados->idEstado_nuevo=44;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
            }
            $tramite->update();
            $tramite->diploma_final=$tramite_detalle->diploma_final;
            $tramite->fut="fut/".$tramite->idTramite;
            //Requisitos
            $tramite->requisitos=Tramite_Requisito::select('*')
            ->join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
            ->where('tramite_requisito.idTramite',$tramite->idTramite)
            ->get();
            
            // // Obtenemos el motivo certificado(en caso lo tengan) de cada trámite 
            // if ($tramite->idTipo_tramite==1) {
            //     $motivo=Motivo_Certificado::Where('idMotivo_certificado',$tramite->idMotivo_certificado)->first();
            //     $tramite->motivo=$motivo->nombre;
            // }
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;
            
            // if ($tramite->idEstado_tramite==15) {
            //     $ruta=public_path().$tramite->certificado_final;
            //     dispatch(new EnvioCertificadoJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad,$ruta));
            // }
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function createCodeDiploma(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            // return $request->all();

            if ($request->flag) {
                //Guardamos el código que se envía del primer registro
                $tramite=Tramite::find($request->grado['idTramite']);
                $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                $tramite_detalle->codigo_diploma=$request->grado['codigo_diploma'];
                $tramite_detalle->observacion_diploma=trim($request->grado['observacion_diploma']);
                $tramite_detalle->save();

                // Obtenemos todos los trámites pendientes de impresión que sean diferentes al primer trámite que ya se registró
                // Además, estos están ordenados por el orden del libro, folio, nro_registro
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
                'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
                'tramite_detalle.diploma_final','tramite.idTramite_detalle','diploma_carpeta.descripcion as denominacion','diploma_carpeta.codigo as diploma',
                'tipo_tramite_unidad.idTipo_tramite_unidad as idFicha','dependencia.idDependencia','tramite_detalle.nro_libro','tramite_detalle.folio'
                ,'tramite_detalle.nro_registro','resolucion.nro_resolucion','resolucion.fecha as fecha_resolucion','tramite.sede'
                ,'tramite_detalle.codigo_diploma','tramite_detalle.observacion_diploma')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('diploma_carpeta','tramite_detalle.idDiploma_carpeta','diploma_carpeta.idDiploma_carpeta')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
                ->where('tramite_detalle.nro_libro','!=',null)
                ->where('tramite_detalle.folio','!=',null)
                ->where('tramite_detalle.nro_registro','!=',null)
                ->where('tramite.idEstado_tramite',44)
                ->where('tramite.idTramite','!=',$request->grado['idTramite'])
                ->where('tramite_detalle.nro_registro','>',$tramite_detalle->nro_registro)
                ->orderBy('tramite_detalle.nro_libro', 'asc')
                ->orderBy('tramite_detalle.folio', 'asc')
                ->orderBy('tramite_detalle.nro_registro', 'asc')
                ->get();

                $codigoInicial=substr($request->grado['codigo_diploma'], -8);

                foreach ($tramites as $key => $value) {
                    $codigo=$codigoInicial+($key+1);
                    $tamCodigo=strlen($codigo);
                    switch ($tamCodigo) {
                        case 1:
                            $codigo="0000000".$codigo;
                            break;
                        case 2:
                            $codigo="000000".$codigo;
                            break;
                        case 3:
                            $codigo="00000".$codigo;
                            break;
                        case 4:
                            $codigo="0000".$codigo;
                            break;
                        case 5:
                            $codigo="000".$codigo;
                            break;
                        case 6:
                            $codigo="00".$codigo;
                            break;
                        case 7:
                            $codigo="0".$codigo;
                            break;
                    }
                    
                    $tramite_detalle=Tramite_Detalle::find($value->idTramite_detalle);
                    $tramite_detalle->codigo_diploma="G-".$codigo;
                    $tramite_detalle->save();
                }


                // RESPUESTA 
                // Obtenemos todos los trámites pendientes de impresión que sean diferentes al primer trámite que ya se registró
                // Además, estos están ordenados por el orden del libro, folio, nro_registro
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
                'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
                'tramite_detalle.diploma_final','tramite.idTramite_detalle','diploma_carpeta.descripcion as denominacion','diploma_carpeta.codigo as diploma',
                'tipo_tramite_unidad.idTipo_tramite_unidad as idFicha','dependencia.idDependencia','tramite_detalle.nro_libro','tramite_detalle.folio'
                ,'tramite_detalle.nro_registro','resolucion.nro_resolucion','resolucion.fecha as fecha_resolucion','tramite.sede'
                ,'tramite_detalle.codigo_diploma','tramite_detalle.observacion_diploma')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('diploma_carpeta','tramite_detalle.idDiploma_carpeta','diploma_carpeta.idDiploma_carpeta')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
                ->where('tramite_detalle.nro_libro','!=',null)
                ->where('tramite_detalle.folio','!=',null)
                ->where('tramite_detalle.nro_registro','!=',null)
                ->where('tramite.idEstado_tramite',44)
                ->orderBy('tramite_detalle.nro_libro', 'asc')
                ->orderBy('tramite_detalle.folio', 'asc')
                ->orderBy('tramite_detalle.nro_registro', 'asc')
                ->get();
                DB::commit();
                return response()->json( $tramites,200);
            }else {
                // validación que no se registre ningún trámite con código que ya existe
                $tramite_detalle_validate=Tramite_Detalle::where('codigo_diploma',$request->grado['codigo_diploma'])->first();
                if ($tramite_detalle_validate) {
                    return response()->json( ['status'=>400,'message'=>'El código ya se encuentra registrado'],400);
                }

                $tramite=Tramite::find($request->grado['idTramite']);
                $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                $tramite_detalle->codigo_diploma=$request->grado['codigo_diploma'];
                $tramite_detalle->observacion_diploma=trim($request->grado['observacion_diploma']);
                $tramite_detalle->save();
                

                // RESPUESTA
                $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
                'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
                'tramite_detalle.diploma_final','tramite.idTramite_detalle','diploma_carpeta.descripcion as denominacion','diploma_carpeta.codigo as diploma',
                'tipo_tramite_unidad.idTipo_tramite_unidad as idFicha','dependencia.idDependencia','tramite_detalle.nro_libro','tramite_detalle.folio'
                ,'tramite_detalle.nro_registro','resolucion.nro_resolucion','resolucion.fecha as fecha_resolucion','tramite.sede'
                ,'tramite_detalle.codigo_diploma','tramite_detalle.observacion_diploma')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('diploma_carpeta','tramite_detalle.idDiploma_carpeta','diploma_carpeta.idDiploma_carpeta')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
                ->where('tramite_detalle.nro_libro','!=',null)
                ->where('tramite_detalle.folio','!=',null)
                ->where('tramite_detalle.nro_registro','!=',null)
                ->find($request->grado['idTramite']);

                DB::commit();
                return response()->json( [$tramite],200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function GetGradosFinalizados(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
            'tramite_detalle.diploma_final','tramite.idTramite_detalle','diploma_carpeta.descripcion as denominacion','diploma_carpeta.codigo as diploma',
            'tipo_tramite_unidad.idTipo_tramite_unidad as idFicha','dependencia.idDependencia','tramite_detalle.nro_libro','tramite_detalle.folio'
            ,'tramite_detalle.nro_registro','resolucion.nro_resolucion','resolucion.fecha as fecha_resolucion','tramite.sede'
            ,'tramite_detalle.codigo_diploma','tramite_detalle.observacion_diploma')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('diploma_carpeta','tramite_detalle.idDiploma_carpeta','diploma_carpeta.idDiploma_carpeta')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',15)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nro_documento','LIKE', '%'.$request->query('search').'%');
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        }else {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
            'tramite_detalle.diploma_final','tramite.idTramite_detalle','diploma_carpeta.descripcion as denominacion','diploma_carpeta.codigo as diploma',
            'tipo_tramite_unidad.idTipo_tramite_unidad as idFicha','dependencia.idDependencia','tramite_detalle.nro_libro','tramite_detalle.folio'
            ,'tramite_detalle.nro_registro','resolucion.nro_resolucion','resolucion.fecha as fecha_resolucion','tramite.sede'
            ,'tramite_detalle.codigo_diploma','tramite_detalle.observacion_diploma')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('diploma_carpeta','tramite_detalle.idDiploma_carpeta','diploma_carpeta.idDiploma_carpeta')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',15)
            ->where('tipo_tramite.idTipo_tramite',2)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
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
            $tramite->escuela=$dependenciaDetalle->nombre;
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function Paginacion($items, $size, $page = null, $options = [])
    {
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return $response=new LengthAwarePaginator($items->forPage($page, $size), $items->count(), $size, $page, $options);
    }
}
