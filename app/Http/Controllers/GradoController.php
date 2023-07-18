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
use App\Cronograma;
// use App\Jobs\RegistroTramiteJob;
// use App\Jobs\EnvioCertificadoJob;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Spipu\Html2Pdf\Html2Pdf;

use App\PersonaSE;

use App\Mencion;
use App\Escuela;
use App\PersonaSuv;
use App\PersonaSga;
use App\Alumno;
use App\Acreditacion;
use App\Usuario_Programa;

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
        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        // TRÁMITES
        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa','tipo_tramite_unidad.descripcion as tramite',
        'tipo_tramite_unidad.costo',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher','cronograma_carpeta.fecha_cierre_alumno','cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato',
        'cronograma_carpeta.fecha_colacion','tramite.uuid')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',17)
        ->where('tramite.idTipo_tramite_unidad',15)
        ->where('tramite.estado',1)
        ->where(function($query) use ($usuario_programas)
        {
            if (count($usuario_programas) > 0) {
                $query->whereIn('tramite.idPrograma',$usuario_programas);
            }
        })
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
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->where('tramite.idEstado_tramite',17)
        ->where('tramite.idTipo_tramite_unidad',15)
        ->where('tramite.estado',1)
        ->where(function($query) use ($usuario_programas)
        {
            if (count($usuario_programas) > 0) {
                $query->whereIn('tramite.idPrograma',$usuario_programas);
            }
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
        ->where('cronograma_carpeta.visible',true)
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

    public function GetGradosAprobadosEscuela(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 
        'usuario.nro_documento', 'usuario.correo','voucher.archivo as voucher','cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','tramite.uuid')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',30)
        ->where('tramite.idTipo_tramite_unidad',15)
        ->where(function($query) use ($usuario_programas)
        {
            if (count($usuario_programas) > 0) {
                $query->whereIn('tramite.idPrograma',$usuario_programas);
            }
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
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',30)
        ->where('tramite.idTipo_tramite_unidad',15)
        ->where(function($query) use ($usuario_programas)
        {
            if (count($usuario_programas) > 0) {
                $query->whereIn('tramite.idPrograma',$usuario_programas);
            }
        })
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
        ->where('cronograma_carpeta.visible',true)
        ->count();

        
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->uuid;
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
        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
        ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite',
        'programa.nombre as programa','tramite.uuid')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',31)
        ->where('tramite.idTipo_tramite_unidad',15)
        ->where(function($query) use ($usuario_programas)
        {
            if (count($usuario_programas) > 0) {
                $query->whereIn('tramite.idPrograma',$usuario_programas);
            }
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
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->where('tramite.idEstado_tramite',31)
        ->where('tramite.idTipo_tramite_unidad',15)
        ->where(function($query) use ($usuario_programas)
        {
            if (count($usuario_programas) > 0) {
                $query->whereIn('tramite.idPrograma',$usuario_programas);
            }
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
        ->where('cronograma_carpeta.visible',true)
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

    public function GetGradosValidadosFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
        ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite',
        'programa.nombre as programa','tramite.uuid')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',20)
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
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();


        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',20)
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
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
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

    public function GetGradosAprobadosFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
        ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','programa.nombre as programa'
        ,'tramite.uuid')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',32)
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
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',32)
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
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
        ->count();

        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
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

    public function GetGradosRevalidadosFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
        ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','tramite.idEstado_tramite'
        ,'programa.nombre as programa','tramite.uuid')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',33)
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
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',33)
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
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
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
    
    // Se puede reutilizar en titulos y SE
    public function cambiarEstado(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','programa.nombre as programa',
            'tramite.uuid')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('programa','tramite.idPrograma','programa.idPrograma')
            ->Find($request->idTramite); 

            $tramite->idEstado_tramite=$request->newEstado;
            $tramite->save();


            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->uuid;

            //OBTENIENDO EL HISTORIAL AL QUE SE DESEA REGRESAR
            $historial_new=Historial_Estado::where('idTramite',$tramite->idTramite)
            ->where('idEstado_nuevo',$request->newEstado)
            ->orderBy('fecha','desc')
            ->limit(1)
            ->first();

            // CAMBIANDO EL ESTADO=0 A CADA UNO DE LOS HISTORIALES MAYORES AL HISTORIAL QUE SE DESEA REGRESAR
            $historiales=Historial_Estado::where('idTramite',$tramite->idTramite)
            ->where('idHistorial_estado','>',$historial_new->idHistorial_estado)
            ->get();
            foreach ($historiales as $key => $historial) {
                // $historial->estado=0;
                $historial->delete();
            }
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    // se podría reutilizar en títulos y se
    public function enviarFacultad(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','programa.nombre as programa'
            ,'tramite.uuid')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('programa','tramite.idPrograma','programa.idPrograma')
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
            $tramite->fut="fut/".$tramite->uuid;

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

            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','programa.nombre as programa')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('programa','tramite.idPrograma','programa.idPrograma')
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
            $tramite->fut="fut/".$tramite->uuid;

            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
    //-------------------------------------------------------

    // Se puede reutilizar en titulos y SE
    public function enviarEscuela(Request $request)
    {
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','programa.nombre as programa'
            ,'tramite.uuid')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('programa','tramite.idPrograma','programa.idPrograma')
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
            $tramite->fut="fut/".$tramite->uuid;
            
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    //------------------------------------------------------------

    public function GetGradosValidadosUra(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
        ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
        'tramite_detalle.certificado_final','programa.nombre as programa','tramite.uuid')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',7)
        ->where('tramite.idTipo_tramite_unidad',15)
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
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->where('tramite.idEstado_tramite',7)
        ->where('tramite.idTipo_tramite_unidad',15)
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
        ->where('cronograma_carpeta.visible',true)
        ->count();

        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
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

    public function GetGradosDatosDiplomaEscuela(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
        'tramite.nro_tramite','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
        'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion','tramite.created_at','tramite.idEstado_tramite',
        'programa.nombre as programa')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',36)
        ->where('tramite.idTipo_tramite_unidad',15)
        ->where(function($query) use ($usuario_programas)
        {
            if (count($usuario_programas) > 0) {
                $query->whereIn('tramite.idPrograma',$usuario_programas);
            }
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
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->where('tramite.idEstado_tramite',36)
        ->where('tramite.idTipo_tramite_unidad',15)
        ->where(function($query) use ($usuario_programas)
        {
            if (count($usuario_programas) > 0) {
                $query->whereIn('tramite.idPrograma',$usuario_programas);
            }
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
        ->where('cronograma_carpeta.visible',true)
        ->count();

        foreach ($tramites as $key => $tramite) {
            // obtener primera y última matrícula de cada usuario que realiza el trámite medienta esu número de matrícula
            if ($tramite->idUnidad==1) {
                // Verificando el SUV
                $matriculaPrimera=MatriculaSUV::select('mat_fecha')->where('idalumno',$tramite->nro_matricula)->first();
                if ($matriculaPrimera) {
                    $tramite->fecha_primera_matricula=$matriculaPrimera->mat_fecha;
                    // NUMERO DE CRÉDITOS
                    $nro_creditos=Alumno::select('alu_nrocrdsaprob')->where('idalumno',$tramite->nro_matricula)->first();
                    $tramite->nro_creditos_carpeta=$nro_creditos->alu_nrocrdsaprob;
                }else {
                    // Verificando SGA 
                    $matriculaPrimera=PersonaSga::select('mat_fecha')->join('perfil','persona.per_id','perfil.per_id')
                    ->join('sga_matricula','sga_matricula.pfl_id','perfil.pfl_id')
                    ->where('persona.per_login',$tramite->nro_matricula)->first();
                    if ($matriculaPrimera) {
                        $tramite->fecha_primera_matricula=$matriculaPrimera->mat_fecha;
                    }else {
                        $tramite->fecha_primera_matricula=null;
                    }
                    // ----------------------------------------------------------------
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
                // Verificando SE 
                $matriculaPrimera=PersonaSE::select('matricula.fecha_hora')->join('matricula','alumno.idAlumno','matricula.idAlumno')
                ->where('alumno.codigo',$tramite->nro_matricula)->first();
                if ($matriculaPrimera) {
                    $tramite->fecha_primera_matricula=$matriculaPrimera->fecha_hora;
                }else {
                    $tramite->fecha_primera_matricula=null;
                }
                // ----------------------------------------------------------------
            }
            
            // Verificación de programa acreditada
            $acreditacion=Acreditacion::where('fecha_inicio','<=',$tramite->fecha_colacion)
            ->where('fecha_fin','>=',$tramite->fecha_colacion)
            ->where('idPrograma',$tramite->idPrograma)
            ->where('estado',1)
            ->first();
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


    public function GetGradosDatosDiplomaFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
        'tramite.nro_tramite','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
        'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
        'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
        'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
        'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
        'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
        ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
        'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
        'tramite_detalle.idAcreditacion','tramite_detalle.fecha_inicio_acto_academico','programa.nombre as programa','programa.idPrograma')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',38)
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
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',38)
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
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
        ->count();

        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->uuid;
            
            // Verificación de programa acreditada
            $acreditacion=Acreditacion::where('fecha_inicio','<=',$tramite->fecha_colacion)
            ->where('fecha_fin','>=',$tramite->fecha_colacion)
            ->where('idPrograma',$tramite->idPrograma)
            ->where('estado',1)
            ->first();
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

    public function GetGradosDatosDiplomaUra(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
        'tramite.nro_tramite','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
        'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
        'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
        'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
        'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
        'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
        ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
        'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
        'tramite_detalle.idAcreditacion','tramite_detalle.fecha_inicio_acto_academico','tramite_detalle.certificado_final','programa.nombre as programa')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',39)
        ->where('tramite.idTipo_tramite_unidad',15)
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
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->where('tramite.idEstado_tramite',39)
        ->where('tramite.idTipo_tramite_unidad',15)
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
        ->where('cronograma_carpeta.visible',true)
        ->count();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->uuid;

            // Verificación de programa acreditada
            $acreditacion=Acreditacion::where('fecha_inicio','<=',$tramite->fecha_colacion)
            ->where('fecha_fin','>=',$tramite->fecha_colacion)
            ->where('idPrograma',$tramite->idPrograma)
            ->where('estado',1)
            ->first();
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

    public function GuardarDatosDiploma(Request $request){
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            // RETORNANDO EL GRADO EDITADO
            $tramite=Tramite::find($request->idTramite);
            
            $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
            $tramite_detalle->idModalidad_carpeta=$request->idModalidad_carpeta;
            if ($tramite->idTipo_tramite_unidad==15) {
                $cronograma=Cronograma::find($tramite_detalle->idCronograma_carpeta);
                $tramite_detalle->fecha_sustentacion_carpeta =$cronograma->fecha_colacion;
            }else {
                $tramite_detalle->fecha_sustentacion_carpeta =$request->fecha_sustentacion_carpeta;
            }

            $tramite_detalle->nombre_trabajo_carpeta=trim($request->nombre_trabajo_carpeta);
            $tramite_detalle->url_trabajo_carpeta=trim($request->url_trabajo_carpeta);
            $tramite_detalle->nro_creditos_carpeta=$request->nro_creditos_carpeta;
            $tramite_detalle->idPrograma_estudios_carpeta=$request->idPrograma_estudios_carpeta;
            $tramite_detalle->fecha_primera_matricula=$request->fecha_primera_matricula;
            $tramite_detalle->fecha_ultima_matricula=$request->fecha_ultima_matricula;
            $tramite_detalle->idDiploma_carpeta=$request->idDiploma_carpeta;
            $tramite_detalle->idAcreditacion=$request->idAcreditacion;
            $tramite_detalle->fecha_inicio_acto_academico=$request->fecha_inicio_acto_academico;
            $tramite_detalle->idUniversidad=1;
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
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tramite.nro_tramite','dependencia.nombre as facultad','tramite.nro_matricula','usuario.nro_documento','usuario.correo',
            'voucher.archivo as voucher', DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','tramite_detalle.idTramite_detalle',
            'tramite_detalle.idModalidad_carpeta','tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta',
            'tramite_detalle.url_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.fecha_sustentacion_carpeta','tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.url_trabajo_carpeta'
            ,'tramite_detalle.nro_creditos_carpeta','tramite_detalle.idPrograma_estudios_carpeta','tramite_detalle.fecha_primera_matricula',
            'tramite_detalle.fecha_ultima_matricula','tramite_detalle.idDiploma_carpeta','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion'
            ,'tramite_detalle.fecha_inicio_acto_academico','programa.nombre as programa')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('programa','tramite.idPrograma','programa.idPrograma')
            ->find($request->idTramite);
            
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function GetResolucion($nro_resolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];


        $resolucion=Resolucion::where('nro_resolucion','LIKE','%'.$nro_resolucion.'%')->orderBy('fecha','desc')
        ->limit(1)
        ->first();

        if ($resolucion) {
            return response()->json(['status' => '200','resolucion'=>$resolucion], 200);
        }else {
            return response()->json(['status' => '400','message'=>"Resolución no encontrada"], 400);
        }

    }

    public function GetGradosResolucion(Request $request,$idResolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $resolucion=Resolucion::find($idResolucion);

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula',
        'tramite.exonerado_archivo', 'tramite_detalle.*', 'tramite.idTipo_tramite_unidad',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher',
        'resolucion.idResolucion', 'cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
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
        ->where('tramite.idEstado_tramite',42)
        ->where('tipo_tramite_unidad.idTipo_tramite',2)
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
        ->orderBy($request->query('sort'), $request->query('order'))
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            // Verificación de programa acreditada
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
            $tramites=Tramite::join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite','!=',42)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            
            ->where('resolucion.idResolucion',$request->idResolucion)
            ->get();  

            if (count($tramites)>0) {
                DB::rollback();
                return response()->json(['status' => '400', 'message' =>"Hay ".count($tramites)." trámites en estados pendientes"], 400);
            }

            // Recorremos todos los trámites y le añadimos su numeracion a cada uno
            $tramites=Tramite::join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa','programa.idPrograma','tramite.idPrograma')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',42)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            
            ->where('resolucion.idResolucion',$request->idResolucion)
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->get();  
            
            foreach ($tramites as $key => $tramite) {
                

                // obtenemos datos del último registro del libro
                $ultimoRegistro=Libro::where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)->orderBy('nro_registro','desc')
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
                $newRegistro->idTipo_tramite_unidad=$tramite->idTipo_tramite_unidad;
                $newRegistro->save();

                //Obtenemos el detalle de cada uno de los trámites Y ACTUALIZAMOS LOS DATOS QUE VAN EN EL LIBRO
                $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                $tramite_detalle->nro_libro=$newRegistro->nro_libro;
                $tramite_detalle->folio=$newRegistro->folio;
                $tramite_detalle->nro_registro=$newRegistro->nro_registro;
                $tramite_detalle->idTipo_tramite_unidad=$newRegistro->idTipo_tramite_unidad;

                // Registramos el código de diploma
                $letra=null;
                if($tramite->idTipo_tramite_unidad==15) $letra="BO";
                elseif($tramite->idTipo_tramite_unidad==16) $letra="TO";
                elseif ($tramite->idTipo_tramite_unidad==34) $letra="SO";

                $tramite_detalle->codigo_diploma=$letra.$newRegistro->nro_libro.$newRegistro->folio.$newRegistro->nro_registro;
                $tramite_detalle->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 43, $idUsuario);
                $historial_estado->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 43, 13, $idUsuario);
                $historial_estado->save();

                $tramite->idEstado_tramite=13;
                $tramite->save();
            }

            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula',
            'tramite.exonerado_archivo', 'tramite_detalle.*', 'tramite.idTipo_tramite_unidad',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'voucher.archivo as voucher',
            'resolucion.idResolucion', 'cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
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
            ->where('tramite.idEstado_tramite',13)
            ->where('tipo_tramite_unidad.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('resolucion.idResolucion',$request->idResolucion)
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->get();  

            DB::commit();
            return response()->json($tramites, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function GetGradosFirmaDecano(Request $request,$idResolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];
        
        $resolucion=Resolucion::find($idResolucion);

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.nro_tramite','tramite.nro_matricula', 'tramite.exonerado_archivo', 'tramite_detalle.autoridad3', 
        'tramite.idTipo_tramite_unidad', 'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'resolucion.idResolucion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',15)
            ->orWhere('tramite.idTipo_tramite_unidad',16)
            ->orWhere('tramite.idTipo_tramite_unidad',34);
        })
        ->where(function($query) use ($idDependencia)
        {
            if ($idDependencia!=null) {
                $query->where('tramite.idDependencia',$idDependencia)
                ->orWhere('dependencia.idDependencia2',$idDependencia);
            }
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
        ->where(function($query) use($idUsuario)
        {
            $query->where('tramite.idEstado_tramite',13)
            ->orWhere('tramite_detalle.autoridad3',$idUsuario);
        })
        ->orderBy($request->query('sort'), $request->query('order'))
        ->orderBy('tramite.idTipo_tramite_unidad','asc')
        ->orderBy('dependencia.nombre','asc')
        ->orderBy('programa.nombre','asc')
        ->orderBy('usuario.apellidos','asc')
        ->orderBy('usuario.nombres','asc')
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',15)
            ->orWhere('tramite.idTipo_tramite_unidad',16)
            ->orWhere('tramite.idTipo_tramite_unidad',34);
        })
        ->where(function($query) use ($idDependencia)
        {
            if ($idDependencia!=null) {
                $query->where('tramite.idDependencia',$idDependencia)
                ->orWhere('dependencia.idDependencia2',$idDependencia);
            }
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
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where(function($query) use($idUsuario)
        {
            $query->where('tramite.idEstado_tramite',13)
            ->orWhere('tramite_detalle.autoridad3',$idUsuario);
        })
        ->count();
        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200','resolucion'=>$resolucion, 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }
    
    public function GetGradosFirmaRector(Request $request,$idResolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];
        
        $resolucion=Resolucion::find($idResolucion);

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.nro_tramite','tramite.nro_matricula', 'tramite.exonerado_archivo', 'tramite_detalle.autoridad1', 
        'tramite.idTipo_tramite_unidad', 'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'resolucion.idResolucion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',15)
            ->orWhere('tramite.idTipo_tramite_unidad',16)
            ->orWhere('tramite.idTipo_tramite_unidad',34);
        })
        ->where('resolucion.idResolucion',$resolucion->idResolucion)
        ->where(function($query) use($idUsuario)
        {
            $query->where('tramite.idEstado_tramite',48)
            ->orWhere('tramite_detalle.autoridad1',$idUsuario);
        })
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
        ->orderBy($request->query('sort'), $request->query('order'))
        ->orderBy('tramite.idTipo_tramite_unidad','asc')
        ->orderBy('dependencia.nombre','asc')
        ->orderBy('programa.nombre','asc')
        ->orderBy('usuario.apellidos','asc')
        ->orderBy('usuario.nombres','asc')
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',15)
            ->orWhere('tramite.idTipo_tramite_unidad',16)
            ->orWhere('tramite.idTipo_tramite_unidad',34);
        })
        ->where('resolucion.idResolucion',$resolucion->idResolucion)
        ->where(function($query) use($idUsuario)
        {
            $query->where('tramite.idEstado_tramite',48)
            ->orWhere('tramite_detalle.autoridad1',$idUsuario);
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
        ->count();
        
        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200','resolucion'=>$resolucion, 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }

    public function GetGradosFirmaSecretaria(Request $request,$idResolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];
        
        $resolucion=Resolucion::find($idResolucion);

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.nro_tramite','tramite.nro_matricula', 'tramite.exonerado_archivo', 'tramite_detalle.autoridad2', 
        'tramite.idTipo_tramite_unidad', 'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'resolucion.idResolucion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->where('resolucion.idResolucion',$resolucion->idResolucion)
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',15)
            ->orWhere('tramite.idTipo_tramite_unidad',16)
            ->orWhere('tramite.idTipo_tramite_unidad',34);
        })
        ->where(function($query) use($idUsuario)
        {
            $query->where('tramite.idEstado_tramite',46)
            ->orWhere('tramite_detalle.autoridad2',$idUsuario);
        })
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
        ->orderBy($request->query('sort'), $request->query('order'))
        ->orderBy('tramite.idTipo_tramite_unidad','asc')
        ->orderBy('dependencia.nombre','asc')
        ->orderBy('programa.nombre','asc')
        ->orderBy('usuario.apellidos','asc')
        ->orderBy('usuario.nombres','asc')
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',15)
            ->orWhere('tramite.idTipo_tramite_unidad',16)
            ->orWhere('tramite.idTipo_tramite_unidad',34);
        })
        ->where('resolucion.idResolucion',$resolucion->idResolucion)
        ->where(function($query) use($idUsuario)
        {
            $query->where('tramite.idEstado_tramite',46)
            ->orWhere('tramite_detalle.autoridad2',$idUsuario);
        })
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
        return response()->json(['status' => '200','resolucion'=>$resolucion, 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }

    public function GetGradosPendientesImpresion(Request $request,$idResolucion){
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
        ->where('tramite.idEstado_tramite',44)
        ->where('tipo_tramite_unidad.idTipo_tramite',2)
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
        ->get();
        
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
            
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
            ->join('programa','programa.idPrograma','tramite.idPrograma')
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
            //         $nombre = $tramite->nro_tramite.'.'.$file->guessExtension();
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
                    if ($tramite->nro_tramite."_firmado.pdf"==$file->getClientOriginalName()) {
                        $nombre = $tramite->nro_tramite.'.'.$file->guessExtension();
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
            $tramite->fut="fut/".$tramite->uuid;
            //Requisitos
            $tramite->requisitos=Tramite_Requisito::select('*')
            ->join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
            ->where('tramite_requisito.idTramite',$tramite->idTramite)
            ->get();

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
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
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
            ->join('programa','programa.idPrograma','tramite.idPrograma')
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
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    // Firmas   
    public function firmaDecano(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $idDependencia=$apy['idDependencia'];

            // Recorremos todos los trámites y le añadimos su numeracion a cada uno
            $tramites=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite.idEstado_tramite')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',13)
            ->where('resolucion.idResolucion',$request->idResolucion)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->get();

            foreach ($tramites as $key => $tramite) {
                // Cambiando el estado a firma de Rector
                $tramite_detalle=Tramite_detalle::where('idTramite_detalle',$tramite->idTramite_detalle)->first();
                $tramite_detalle->autoridad3=$idUsuario;
                $tramite_detalle->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE FIRMADO POR DECANO
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 14, $idUsuario);
                $historial_estado->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE PENDIENTE DE FIRMA DEL RECTOR
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 14, 48, $idUsuario);
                $historial_estado->save();

                $tramite->idEstado_tramite=48;
                $tramite->save();
            }

            DB::commit();

            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
            'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad3', 'tramite.idTipo_tramite_unidad', 
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa', 'tipo_tramite_unidad.descripcion as tramite',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'resolucion.idResolucion')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->where('tramite_detalle.autoridad3',$idUsuario)
            ->where('resolucion.idResolucion',$request->idResolucion)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->get();  

            return response()->json($tramites,200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function firmaRector(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            // Recorremos todos los trámites y le añadimos su numeracion a cada uno
            $tramites=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite.idEstado_tramite')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',48)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('resolucion.idResolucion',$request->idResolucion)
            ->get();  

            foreach ($tramites as $key => $tramite) {
                // Cambiando el estado a firma de Rector
                $tramite_detalle=Tramite_detalle::where('idTramite_detalle',$tramite->idTramite_detalle)->first();
                $tramite_detalle->autoridad1=$idUsuario;
                $tramite_detalle->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE FIRMADO POR RECTOR
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 49, $idUsuario);
                $historial_estado->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE PENDIENTE DE FIRMA DE SECRETARÍA GENERAL
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 49, 46, $idUsuario);
                $historial_estado->save();

                $tramite->idEstado_tramite=46;
                $tramite->save();
            }

            DB::commit();

            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
            'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad1', 'tramite.idTipo_tramite_unidad', 
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa', 'tipo_tramite_unidad.descripcion as tramite',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'resolucion.idResolucion')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->where('tramite_detalle.autoridad1',$idUsuario)
            ->where('resolucion.idResolucion',$request->idResolucion)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->get();  

            return response()->json($tramites,200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function firmaSecretaria(Request $request){
        set_time_limit(0);
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            

            // Recorremos todos los trámites y le añadimos su numeracion a cada uno
            $tramites=Tramite::select('tramite.idTramite', 'tramite.idUnidad','tramite.idEstado_tramite','tramite.idTramite_detalle', 
            'programa.denominacion as programa', 
            DB::raw("(case 
                        when tramite.idUnidad = 1 then dependencia.denominacion  
                        when tramite.idUnidad = 4 then  (select denominacion from dependencia d where d.idDependencia=dependencia.idDependencia2)
                    end) as facultad"),
            DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombreComp'),'usuario.tipo_documento','usuario.nro_documento',
            'tramite_detalle.codigo_diploma','tramite_detalle.nro_libro','tramite_detalle.folio','tramite_detalle.nro_registro',
            'tramite_detalle.autoridad1','tramite_detalle.autoridad2','tramite_detalle.autoridad3',
            DB::raw('CONCAT(rector.nombres," ",rector.apellidos) as nombre_rector'),
            'rector.cargo as cargo_rector', 'rector.sexo as sexo_rector','rector.grado as grado_rector',
            DB::raw('CONCAT(decano.nombres," ",decano.apellidos) as nombre_decano'),
            'decano.cargo as cargo_decano', 'decano.sexo as sexo_decano','decano.grado as grado_decano',
            'diploma_carpeta.descripcion as denominacion', 'tipo_tramite_unidad.diploma_obtenido',
            'cronograma_carpeta.fecha_colacion', 'resolucion.nro_resolucion', 'resolucion.fecha as fecha_resolucion',
            'modalidad_carpeta.acto_academico')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('usuario as rector','rector.idUsuario','tramite_detalle.autoridad1')
            ->join('usuario as decano','decano.idUsuario','tramite_detalle.autoridad3')
            ->join('diploma_carpeta','tramite_detalle.idDiploma_carpeta','diploma_carpeta.idDiploma_carpeta')
            ->join('modalidad_carpeta','tramite_detalle.idModalidad_carpeta','modalidad_carpeta.idModalidad_carpeta')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa','programa.idPrograma','tramite.idPrograma')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',46)
            ->where('resolucion.idResolucion',$request->idResolucion)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->get();  
            
            // Obteniendo los datos de las autoridades
            $secretariaGeneral=User::select(DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombres'),'usuario.cargo','usuario.sexo','usuario.grado')
            ->where('idUsuario',$idUsuario)->first();
            
            foreach ($tramites as $key => $tramite) {                
                // Obteniendo datos de rector(a)
                $rector = (object)array(
                    "nombres" =>$tramite->nombre_rector,
                    "sexo" =>$tramite->sexo_rector,
                    "grado" =>$tramite->grado_rector,
                    "cargo" =>$tramite->cargo_rector,
                );

                // Obteniendo datos de decano(a)
                $decano = (object)array(
                    "nombres" =>$tramite->nombre_decano,
                    "sexo" =>$tramite->sexo_decano,
                    "grado" =>$tramite->grado_decano,
                    "cargo" =>$tramite->cargo_decano,
                );

                $tramite_detalle=Tramite_detalle::where('idTramite_detalle',$tramite->idTramite_detalle)->first();
                $tramite_detalle->autoridad2=$idUsuario;
                $tramite_detalle->nombre_descarga_sunedu='T004_'.$tramite->nro_documento.'_'.substr($tramite->diploma_obtenido, 0,1);
                $tramite_detalle->diploma_final = "/storage/diplomas/".'T004_'.$tramite->nro_documento.'_'.substr($tramite->diploma_obtenido, 0,1).'.pdf';
                $tramite_detalle->save();
                
                // Colocando a cada uno de los trámites la firma de secretaria general
                $tramite->autoridad2=$idUsuario;

                // Obteniendo la foto para la diploma
                $requisito_foto=Tramite_Requisito::where('idTramite',$tramite->idTramite)
                ->where(function($query)
                {
                    $query->where('idRequisito',15)
                    ->orWhere('idRequisito',23)
                    ->orWhere('idRequisito',61);
                })->first();


                // Creando el diploma con los datos obtenidos
                // return $tramite;
                $html2pdf = new Html2Pdf('L', 'A4', 'es', true, 'UTF-8');
                $html2pdf->writeHTML(view('diploma.diploma', [
                    'foto_interesado'=>$requisito_foto->archivo,
                    'decano'=>$decano,'secretaria'=>$secretariaGeneral,'rector'=>$rector,
                    'emision_diploma'=>'O - ORIGINAL',
                    'tramite'=>$tramite
                ]));
                // Guardar el pdf generado en la ruta especificada
                $html2pdf->output(storage_path('app/public').'/diplomas/T004_'.$tramite->nro_documento.'_'.substr($tramite->diploma_obtenido, 0,1).'.pdf','F');
            }

            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
            'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad2', 'tramite.idTipo_tramite_unidad', 
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa', 'tipo_tramite_unidad.descripcion as tramite',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'resolucion.idResolucion')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->where('tramite_detalle.autoridad2',$idUsuario)
            ->where('resolucion.idResolucion',$request->idResolucion)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->get(); 


            DB::commit();
            set_time_limit(60);

            return response()->json($tramites,200);
        } catch (\Exception $e) {
            DB::rollback();
            set_time_limit(60);
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function Paginacion($items, $size, $page = null, $options = [])
    {
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return $response=new LengthAwarePaginator($items->forPage($page, $size), $items->count(), $size, $page, $options);
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
