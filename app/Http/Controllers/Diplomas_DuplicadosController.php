<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Tramite;
use App\Tramite_Requisito;
use App\Tramite_Detalle;
use App\Usuario_Programa;
use App\Graduado;
use App\diploma_carpeta;
use App\Acreditacion;
use App\Cronograma;
use App\Historial_Estado;
use App\Resolucion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class Diplomas_DuplicadosController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function GetDiplomasDuplicadosValidados(Request $request)
    {
        
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
        'voucher.archivo as voucher','tramite.uuid')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',53)
        ->where(function($query)
        {
            $query->where('tipo_tramite_unidad.idTipo_tramite',6)
            ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
        })
        ->where('tramite.estado',1)
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
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->where('tramite.idEstado_tramite',42)
        ->where(function($query)
        {
            $query->where('tipo_tramite_unidad.idTipo_tramite',6)
            ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
        })
        ->where('tramite.estado',1)
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

    public function GetDiplomasDuplicadosAprobados(Request $request)
    {
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
        'voucher.archivo as voucher','tramite.uuid')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',55)
        ->where(function($query)
        {
            $query->where('tipo_tramite_unidad.idTipo_tramite',6)
            ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
        })
        ->where('tramite.estado',1)
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
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->where('tramite.idEstado_tramite',55)
        ->where(function($query)
        {
            $query->where('tipo_tramite_unidad.idTipo_tramite',6)
            ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
        })
        ->where('tramite.estado',1)
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

        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite','requisito.guardado')
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

    public function GetDiplomasDuplicadosValidacionUra(Request $request)
    {
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
        'voucher.archivo as voucher','tramite.uuid','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.descripcion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',7)
        ->where(function($query) use ($request)
        {
            if ($request->idTipo_tramite_unidad!=0) {
                if($request->idTipo_tramite_unidad==42){
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[42,47]);
                }elseif ($request->idTipo_tramite_unidad==43) {
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[43,48]);
                }elseif ($request->idTipo_tramite_unidad==44) {
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[44,49]);
                }
            }
        })
        ->where(function($query)
        {
            $query->where('tipo_tramite_unidad.idTipo_tramite',6)
            ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
        })
        ->where('tramite.estado',1)
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
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->where('tramite.idEstado_tramite',7)
        ->where(function($query) use ($request)
        {
            if ($request->idTipo_tramite_unidad!=0) {
                if($request->idTipo_tramite_unidad==42){
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[42,47]);
                }elseif ($request->idTipo_tramite_unidad==43) {
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[43,48]);
                }elseif ($request->idTipo_tramite_unidad==44) {
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[44,49]);
                }
            }
        })
        ->where(function($query)
        {
            $query->where('tipo_tramite_unidad.idTipo_tramite',6)
            ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
        })
        ->where('tramite.estado',1)
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

    public function GetDiplomasDuplicadosDatosDiplomaUra(Request $request)
    {
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
        'tramite.uuid','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.descripcion','tramite.sede')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->where(function($query) use ($request)
        {
            if ($request->idTipo_tramite_unidad!=0) {
                if($request->idTipo_tramite_unidad==42){
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[42,47]);
                }elseif ($request->idTipo_tramite_unidad==43) {
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[43,48]);
                }elseif ($request->idTipo_tramite_unidad==44) {
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[44,49]);
                }
            }
        })
        ->where('tramite.idEstado_tramite',58)
        ->where(function($query)
        {
            $query->where('tipo_tramite_unidad.idTipo_tramite',6)
            ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
        })
        ->where('tramite.estado',1)
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
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->where('tramite.idEstado_tramite',58)
        ->where(function($query) use ($request)
        {
            if ($request->idTipo_tramite_unidad!=0) {
                if($request->idTipo_tramite_unidad==42){
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[42,47]);
                }elseif ($request->idTipo_tramite_unidad==43) {
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[43,48]);
                }elseif ($request->idTipo_tramite_unidad==44) {
                    $query ->whereIn('tramite.idTipo_tramite_unidad',[44,49]);
                }
            }
        })
        ->where(function($query)
        {
            $query->where('tipo_tramite_unidad.idTipo_tramite',6)
            ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
        })
        ->where('tramite.estado',1)
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

        foreach ($tramites as $key => $tramite) {
            // VERIFICANDO SI TIENE TRÁMITE EN EL SISTEMA DE URA PARA OBTENER LOS DATOS DE DIPLOMA
            $tramiteUra=Tramite::join('usuario','tramite.idUsuario','usuario.idUsuario')
            ->where(function($query) use ($tramite)
            {
                $query->where('tramite.idEstado_tramite',44) //Pendiente de impresión
                ->orWhere('tramite.idEstado_tramite',15); // Finalizado
            })
            ->where(function($query) use ($tramite)
            {
                if ($tramite->idTipo_tramite_unidad==42||$tramite->idTipo_tramite_unidad==47) { // Duplicado de bachiller
                    $query->where('idTipo_tramite_unidad',15);
                }elseif ($tramite->idTipo_tramite_unidad==43||$tramite->idTipo_tramite_unidad==48) { //Duplicado de título pregrado
                    $query->where('idTipo_tramite_unidad',16);
                }elseif ($tramite->idTipo_tramite_unidad==44||$tramite->idTipo_tramite_unidad==49) { //Duplicado de título SE
                    $query->where('idTipo_tramite_unidad',34);
                }
            })
            ->where('tramite.nro_matricula',$tramite->nro_matricula)
            ->first();
            
            if ($tramiteUra) { // Encontrando datos en Ura
                $tramite_detalle=Tramite_Detalle::join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->find($tramiteUra->idTramite_detalle);
                $tramite->idModalidad_carpeta=$tramite_detalle->idModalidad_carpeta;
                if ($tramite_detalle->idUniversidad==null) {
                    $tramite->idUniversidad=1;
                }else {
                    $tramite->idUniversidad=$tramite_detalle->idUniversidad;
                }
                $tramite->idDiploma_carpeta=$tramite_detalle->idDiploma_carpeta;
                $tramite->fecha_colacion=$tramite_detalle->fecha_colacion;
                // $tramite->codigo_diploma=$tramite_detalle->codigo_diploma;
            }else { // Buscando datos en diplomas
                // Verificando en la bd de diplomas
                // $graduado=Graduado::select('matricula_fecha','fecha_egresado')->where('tipo_ficha',1)->where('grad_estado',2)
                // ->where('cod_alumno',$tramite->nro_matricula)->first();

                $graduado=Graduado::select('tipoficha.Tip_ficha','actoacad.Cod_acto','graduado.fec_expe_d as fecha_colacion','graduado.univ_procedencia')
                ->join('tipoficha','tipoficha.Tip_ficha','graduado.tipo_ficha') 
                ->join('actoacad','actoacad.Cod_acto','graduado.cod_acto')
                ->where(function($query) use ($tramite)
                {
                    if ($tramite->idTipo_tramite_unidad==42||$tramite->idTipo_tramite_unidad==47) { // Duplicado de bachiller
                        $query->where('tipoficha.Tip_ficha',1);
                    }elseif ($tramite->idTipo_tramite_unidad==43||$tramite->idTipo_tramite_unidad==48) { //Duplicado de título pregrado
                        $query->where('tipoficha.Tip_ficha',2);
                    }elseif ($tramite->idTipo_tramite_unidad==44||$tramite->idTipo_tramite_unidad==49) { //Duplicado de título SE
                        $query->where('tipoficha.Tip_ficha',6);
                    }
                })
                ->where('graduado.cod_alumno',$tramite->nro_matricula)
                ->first();
                if ($graduado) {
                    
                    if ($graduado->Tip_ficha==1) { // bachiller
                        switch ($graduado->Cod_acto) {
                            case 5: // automático
                                $tramite->idModalidad_carpeta=1;
                                break;
                            case 1: // tesis
                                $tramite->idModalidad_carpeta=3;
                                break;
                        }
                    }elseif ($graduado->Tip_ficha==2) {
                        switch ($graduado->Cod_acto) {
                            case 1: // tesis
                                $tramite->idModalidad_carpeta=4;
                                break;
                            case 6: // trab. de investigación
                                $tramite->idModalidad_carpeta=10;
                                break;
                            case 7: // trab. de suficiencia profesional
                                $tramite->idModalidad_carpeta=22;
                                break;
                            case 8: // trab. académico
                                $tramite->idModalidad_carpeta=24;
                                break;
                        }
                    }elseif ($graduado->Tip_ficha==6) {
                        switch ($graduado->Cod_acto) {
                            case 1: // tesis
                                $tramite->idModalidad_carpeta=6;
                                break;
                            case 6: // trab. de investigación
                                $tramite->idModalidad_carpeta=12;
                                break;
                            case 8: // trab. académico
                                $tramite->idModalidad_carpeta=24;
                                break;
                            case 98: // proyecto de investigación
                                $tramite->idModalidad_carpeta=29;
                                break;
                        }
                    }

                    $tramite->fecha_colacion=$graduado->fecha_colacion;

                    // switch ($graduado->idProg_Estu) {
                    //     case 1: //ciclo regular
                    //         $tramite->idPrograma_estudios_carpeta=2;
                    //         break;
                    //     case 2: // convalidación
                    //         $tramite->idPrograma_estudios_carpeta=3;
                    //         break;
                    //     case 3: // complementación académica
                    //         $tramite->idPrograma_estudios_carpeta=4;
                    //         break;
                    //     case 4: // complementación pedagógica
                    //         $tramite->idPrograma_estudios_carpeta=5;
                    //         break;
                    //     case 5: // programa para adultos
                    //         $tramite->idPrograma_estudios_carpeta=6;
                    //         break; 
                    //     case 6: // otros 
                    //         $tramite->idPrograma_estudios_carpeta=7;
                    //         break;
                    // }
                    
                    if ($graduado->univ_procedencia==null) {
                        $tramite->idUniversidad=1;

                    }else {
                        switch ($graduado->univ_procedencia) {
                            case 1: // UNIVERSIDAD ALAS PERUANAS
                                $tramite->idUniversidad=98;
                                break;
                            case 2: // UNIVERSIDAD NACIONAL TORIBIO RODRIGUEZ DE MENDOZA DE AMAZONAS
                                $tramite->idUniversidad=61;
                                break;
                            case 3: // UNIVERSIDAD INCA GARCILASO DE LA VEGA ASOCIACIÓN CIVIL
                                $tramite->idUniversidad=null; //no tenemos en nuestra bd
                                break;
                            case 4: // UNIVERSIDAD SAN PEDRO - CHIMBOTE
                                $tramite->idUniversidad=99;
                                break;
                        }
                    }
                }

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
        // return $request->all();
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            // Tramite de duplicado
            $tramite=Tramite::find($request->idTramite);

            // Asignando el tipo de trámite (bachiller, título preg o título se)
            $idTipo_tramite_unidad=null;
            if ($request->idTipo_tramite_unidad==42||$request->idTipo_tramite_unidad==47) {
                $idTipo_tramite_unidad=15;
            }elseif ($request->idTipo_tramite_unidad==43||$request->idTipo_tramite_unidad==48) {
                $idTipo_tramite_unidad=16;
            }elseif ($request->idTipo_tramite_unidad==44||$request->idTipo_tramite_unidad==49) {
                $idTipo_tramite_unidad=34;
            }

            // Creando el cronograma, en caso no exista
            $cronograma=Cronograma::where('idDependencia',$tramite->idDependencia)->where('idTipo_tramite_unidad',$idTipo_tramite_unidad)
            ->where('fecha_colacion',$request->fecha_colacion)->first();
            if (!$cronograma) {
                $cronograma=new Cronograma();
                $cronograma->idDependencia=$tramite->idDependencia;
                $cronograma->idUnidad=$tramite->idUnidad;
                $cronograma->idTipo_tramite_unidad=$idTipo_tramite_unidad;
                $cronograma->fecha_cierre_alumno=$request->fecha_colacion;
                $cronograma->fecha_cierre_secretaria=$request->fecha_colacion;
                $cronograma->fecha_cierre_decanato=$request->fecha_colacion;
                $cronograma->tipo_colacion='';
                $cronograma->fecha_colacion=$request->fecha_colacion;
                $cronograma->visible=false;
                $cronograma->estado=false;
                $cronograma->save();
            }
            // return $cronograma;
            $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
            $tramite_detalle->idModalidad_carpeta=$request->idModalidad_carpeta;
            $tramite_detalle->idDiploma_carpeta=$request->idDiploma_carpeta;
            $tramite_detalle->idUniversidad=$request->idUniversidad;
            $tramite_detalle->idCronograma_carpeta=$cronograma->idCronograma_carpeta;
            $tramite_detalle->update();

            // Cambiando el estado    

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

            $tramite->save();

            // RETORNANDO EL GRADO EDITADO
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa','tipo_tramite_unidad.descripcion as tramite',
            'tipo_tramite_unidad.costo',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'tramite.uuid','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.descripcion','tramite.sede','tramite_detalle.idModalidad_carpeta',
            'tramite_detalle.idDiploma_carpeta','tramite_detalle.idUniversidad','cronograma_carpeta.fecha_colacion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
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

    public function Paginacion($items, $size, $page = null, $options = [])
    {
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return $response=new LengthAwarePaginator($items->forPage($page, $size), $items->count(), $size, $page, $options);
    }
}
