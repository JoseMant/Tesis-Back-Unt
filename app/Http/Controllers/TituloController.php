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
use App\Graduado;
use App\Usuario_Programa;
class TituloController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function GetTitulosValidadosEscuela(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher',
        'cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
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
        ->where('tramite.idTipo_tramite_unidad',16)
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
        ->where('tramite.estado',1)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }

        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
        $begin = ($pagination->currentPage()-1)*$pagination->perPage();
        $end = min(($pagination->perPage() * $pagination->currentPage()), $pagination->total());
        return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
            'length'    => $pagination->total(),
            'size'      => $pagination->perPage(),
            'page'      => $pagination->currentPage()-1,
            'lastPage'  => $pagination->lastPage()-1,
            'startIndex'=> $begin,
            'endIndex'  => $end - 1
        ]], 200);
    }

    public function GetTitulosAprobadosEscuela(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher',
        'cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
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
        ->where('tramite.idTipo_tramite_unidad',16)
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
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetTitulosRevalidadosEscuela(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher',
        'cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',31)
        ->where('tramite.idTipo_tramite_unidad',16)
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
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;

            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
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

    public function GetTitulosValidadosFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idDependencia=$apy['idDependencia'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher',
        'cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',20)
        ->where('tramite.idTipo_tramite_unidad',16)
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
            ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
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

    public function GetTitulosAprobadosFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idDependencia=$apy['idDependencia'];

        // TRÁMITES POR USUARIO
        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher',
        'cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',32)
        ->where('tramite.idTipo_tramite_unidad',16)
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
            ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
        $begin = ($pagination->currentPage()-1)*$pagination->perPage();
        $end = min(($pagination->perPage() * $pagination->currentPage()), $pagination->total());
        return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
            'length'    => $pagination->total(),
            'size'      => $pagination->perPage(),
            'page'      => $pagination->currentPage()-1,
            'lastPage'  => $pagination->lastPage()-1,
            'startIndex'=> $begin,
            'endIndex'  => $end - 1
        ]], 200);
    }

    public function GetTitulosRevalidadosFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idDependencia=$apy['idDependencia'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher',
        'cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',33)
        ->where('tramite.idTipo_tramite_unidad',16)
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
            ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();          
        }

        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
        $begin = ($pagination->currentPage()-1)*$pagination->perPage();
        $end = min(($pagination->perPage() * $pagination->currentPage()), $pagination->total());
        return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
            'length'    => $pagination->total(),
            'size'      => $pagination->perPage(),
            'page'      => $pagination->currentPage()-1,
            'lastPage'  => $pagination->lastPage()-1,
            'startIndex'=> $begin,
            'endIndex'  => $end - 1
        ]], 200);
    }
    
    public function cambiarEstado(Request $request){ 
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
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

            $tramite->idEstado_tramite=$request->newEstado;
            $tramite->save();


            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->idTramite;

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

    //Sin cambios
    public function enviarFacultad(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'voucher.archivo as voucher',
            'cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->Find($request->idTramite);
            
            //REGISTRAMOS EL ESTADO DEL TRÁMITE
            $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 34, $idUsuario);
            $historial_estado->save();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estado = $this->setHistorialEstado($tramite->idTramite, 34, 20, $idUsuario);
            $historial_estado->save();

            $tramite->idEstado_tramite=20;
            $tramite->save();


            $tramite->fut="fut/".$tramite->idTramite;
            // OBTENIENDO LOS REQUISITOS Y DATOS ADICIONALES DEL TRÁMITE
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    //Sin cambios
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
            $tramite->fut="fut/".$tramite->idTramite;
            
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    //Sin cambios
    public function enviarEscuela(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'voucher.archivo as voucher',
            'cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->Find($request->idTramite);
            
            //REGISTRAMOS EL ESTADO DEL TRÁMITE
            $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 40, $idUsuario);
            $historial_estado->save();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estado = $this->setHistorialEstado($tramite->idTramite, 40, 36, $idUsuario);
            $historial_estado->save();

            $tramite->idEstado_tramite=36;
            $tramite->save();

            // OBTENIENDO LOS REQUISITOS Y DATOS ADICIONALES DEL TRÁMITE
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function GetTitulosValidadosUra(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo', 
        'tramite_detalle.certificado_final',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher',
        'cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',7)
        ->where('tramite.idTipo_tramite_unidad',16)
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
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
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

    public function GetTitulosDatosDiplomaEscuela(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.idTipo_tramite_unidad', 'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher',
        'cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',36)
        ->where('tramite.idTipo_tramite_unidad',16)
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
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            // obtener primera y última matrícula de cada usuario que realiza el trámite mediante su número de matrícula
            // Consultamos en bdTrámites si tiene información de primera matrícula y egreso
            $bachiller=Tramite::select('tramite_detalle.fecha_primera_matricula','fecha_ultima_matricula', 'tramite_detalle.nro_creditos_carpeta')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where('tramite.idEstado_tramite',44)
            ->where('tramite.nro_matricula',$tramite->nro_matricula)
            ->first();
            if ($bachiller) {
                $tramite->fecha_primera_matricula=$bachiller->fecha_primera_matricula;
                $tramite->fecha_ultima_matricula=$bachiller->fecha_ultima_matricula;
                $tramite->idUniversidad = 1;
            } else {
                // Accedemos a la bd de diplomas para obtener la fecha de primera matrícula y egreso
                $graduado=Graduado::select('matricula_fecha','fecha_egresado')->where('tipo_ficha',1)->where('grad_estado',2)
                ->where('cod_alumno',$tramite->nro_matricula)->first();
                if ($graduado) {
                    $tramite->fecha_primera_matricula=$graduado->matricula_fecha;
                    $tramite->fecha_ultima_matricula=$graduado->fecha_egresado;
                } else {
                    // Verificando el SUV
                    $matriculaPrimera=MatriculaSUV::select('mat_fecha')->where('idalumno',$tramite->nro_matricula)->first();
                    if ($matriculaPrimera) {
                        $tramite->idUniversidad = 1;
                        $tramite->fecha_primera_matricula=$matriculaPrimera->mat_fecha;
                    } else {
                        // Verificando SGA
                        $matriculaPrimera=PersonaSga::select('mat_fecha')->join('perfil','persona.per_id','perfil.per_id')
                        ->join('sga_matricula','sga_matricula.pfl_id','perfil.pfl_id')
                        ->where('persona.per_login',$tramite->nro_matricula)->first();
                        if ($matriculaPrimera) {
                            $tramite->idUniversidad = 1;
                            $tramite->fecha_primera_matricula=$matriculaPrimera->mat_fecha;
                        } else {
                            $tramite->idUniversidad = null;
                            $tramite->fecha_primera_matricula = null;
                        }
                    }       
                }
            }

            // NUMERO DE CRÉDITOS SUV
            $nro_creditos=Alumno::select('alu_nrocrdsaprob')->where('idalumno',$tramite->nro_matricula)->first();
            if ($nro_creditos) {
                $tramite->nro_creditos_carpeta=$nro_creditos->alu_nrocrdsaprob;
            } else {
                // Número de créditos SGA
                $cursos_sga=PersonaSga::select('cur.cur_id', 'dma.dma_vez', 'cur.cur_creditos', 'n.not_pr', 'n.not_ap')
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
                ->get();
                if ($cursos_sga) {
                    $rows_cursos = $cursos_sga;
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
                } else {
                    $tramite->nro_creditos_carpeta = 0;
                }                   
            }       
            
            // --------------
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();

            // Verificación de escuela acreditada
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

    public function GetTitulosDatosDiplomaFacultad(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idDependencia=$apy['idDependencia'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula',
        'tramite.exonerado_archivo', 'tramite_detalle.*', 'tramite.idTipo_tramite_unidad',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher',
        'cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',38)
        ->where('tramite.idTipo_tramite_unidad',16)
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
            ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where('cronograma_carpeta.visible',true)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            // Verificación de escuela acreditada
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

    public function GetTitulosDatosDiplomaUra(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula',
        'tramite.exonerado_archivo', 'tramite_detalle.*', 'tramite.idTipo_tramite_unidad',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher',
        'cronograma_carpeta.fecha_cierre_alumno',
        'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',39)
        ->where('tramite.idTipo_tramite_unidad',16)
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
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            // Verificación de escuela acreditada
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

    //Sin cambios
    public function GuardarDatosDiploma(Request $request){
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            // RETORNANDO EL GRADO EDITADO
            $tramite=Tramite::find($request->idTramite);
            // AÑADIENDO LOS DATOS DEL DIPLOMA SUBIDOS POR LA ESCUELA AL DETALLE DEL TRÁMITE
            if ($request->fecha_inicio_acto_academico>$request->fecha_sustentacion_carpeta) { //Validación de fecha de inicio de acto académico y el acto académico
                return response()->json(['status' => '400', 'message' => "La fecha de inicio de acto académico no puede ser mayor a la fecha del acto académico."], 400);
            }
            $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
            $tramite_detalle->idModalidad_carpeta=$request->idModalidad_carpeta;
            $tramite_detalle->fecha_sustentacion_carpeta =$request->fecha_sustentacion_carpeta;
            $tramite_detalle->nombre_trabajo_carpeta=trim($request->nombre_trabajo_carpeta);
            $tramite_detalle->url_trabajo_carpeta=trim($request->url_trabajo_carpeta);
            $tramite_detalle->nro_creditos_carpeta=$request->nro_creditos_carpeta;
            $tramite_detalle->originalidad=$request->originalidad;
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
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 37, $idUsuario);
                $historial_estado->save();
    
                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 37, 38, $idUsuario);
                $historial_estado->save();
                
                // Nuevo estado del trámite
                $tramite->idEstado_tramite=38;
            }elseif($tramite->idEstado_tramite==38) {
                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 7, $idUsuario);
                $historial_estado->save();
                
                // Nuevo estado del trámite
                $tramite->idEstado_tramite=7;
            }elseif ($tramite->idEstado_tramite==50) {
                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 42, $idUsuario);
                $historial_estado->save();

                // Nuevo estado del trámite
                $tramite->idEstado_tramite=42;
            }else {
    
                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 41, $idUsuario);
                $historial_estado->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 41, 42, $idUsuario);
                $historial_estado->save();
                
                // Nuevo estado del trámite
                $tramite->idEstado_tramite=42;
            }
            $tramite->save();

            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula',
            'tramite.exonerado_archivo', 'tramite_detalle.*', 'tramite.idTipo_tramite_unidad',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'voucher.archivo as voucher',
            'cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->find($request->idTramite);

            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
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
