<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Mail\Mailable;
use App\Tramite;
use App\Tipo_Tramite;
use App\Tipo_Tramite_Unidad;
use App\Historial_Estado;
use App\Tramite_Requisito;
use App\Voucher;
use App\User;
use App\Tramite_Detalle;
use App\Estado_Tramite;
use App\Jobs\RegistroTramiteJob;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TramitesImport;
use App\Imports\AprobadosImport;

use App\PersonaSE;

use App\Mencion;
use App\Escuela;
use App\PersonaSuv;
use App\PersonaSga;
use App\ProgramaURAA;
class CarnetController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function GetCarnets(Request $request)
    {
        // TRÁMITES POR USUARIO
        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad',DB::raw('CONCAT(tipo_tramite.descripcion," - ",tipo_tramite_unidad.descripcion) as tramite'),'tramite.nro_tramite as codigo','dependencia.nombre as facultad'
        /*,'motivo_certificado.nombre as motivo'*/,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        // ->join('motivo_certificado','motivo_certificado.idMotivo_certificado','tramite_detalle.idMotivo_certificado')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tipo_tramite.idTipo_tramite',3)
        ->get();
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario')
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
        return response()->json($tramites, 200);
    }


    public function GetCarnetsRegulares(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
        ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','tramite.idEstado_tramite','programa.nombre as escuela')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where(function($query) use ($request)
        {
            $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.nro_documento','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where(function($query)
        {
            $query->where('tramite.idEstado_tramite',7)
            ->orWhere('tramite.idEstado_tramite',16);
        })
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',17)
            ->orWhere('tramite.idTipo_tramite_unidad',19)
            ->orWhere('tramite.idTipo_tramite_unidad',21)
            ->orWhere('tramite.idTipo_tramite_unidad',23);
        })
        ->where('tramite.estado',1)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where(function($query) use ($request)
        {
            $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.nro_documento','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->where(function($query)
        {
            $query->where('tramite.idEstado_tramite',7)
            ->orWhere('tramite.idEstado_tramite',16);
        })
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',17)
            ->orWhere('tramite.idTipo_tramite_unidad',19)
            ->orWhere('tramite.idTipo_tramite_unidad',21)
            ->orWhere('tramite.idTipo_tramite_unidad',23);
        })
        ->where('tramite.estado',1)
        ->count();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.idRequisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->idTramite;
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
    public function GetCarnetsDuplicados(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
        ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','tramite.idEstado_tramite','programa.nombre as escuela')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
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
        ->where(function($query)
        {
            $query->where('tramite.idEstado_tramite',7)
            ->orWhere('tramite.idEstado_tramite',16);
        })
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',18)
            ->orWhere('tramite.idTipo_tramite_unidad',20)
            ->orWhere('tramite.idTipo_tramite_unidad',22)
            ->orWhere('tramite.idTipo_tramite_unidad',24);
        })
        ->where('tramite.estado',1)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();


        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
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
        ->where(function($query)
        {
            $query->where('tramite.idEstado_tramite',7)
            ->orWhere('tramite.idEstado_tramite',16);
        })
        ->where(function($query)
        {
            $query->where('tramite.idTipo_tramite_unidad',18)
            ->orWhere('tramite.idTipo_tramite_unidad',20)
            ->orWhere('tramite.idTipo_tramite_unidad',22)
            ->orWhere('tramite.idTipo_tramite_unidad',24);
        })
        ->where('tramite.estado',1)
        ->count();

        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','tramite_requisito.idRequisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="fut/".$tramite->idTramite;
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


    //Parece que no se utiliza
    public function GetCarnetsAsignados(Request $request)  
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        if ($request->query('search')!="") {
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idEstado_tramite',3)
            ->where('tipo_tramite.idTipo_tramite',3)
            // ->where('tramite_detalle.asignado_certificado',$idUsuario)     LOS CARNETS NO SE ASIGNAN
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
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idEstado_tramite',3)
            ->where('tipo_tramite.idTipo_tramite',3)
            // ->where('tramite_detalle.asignado_certificado',$idUsuario)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();   
        }
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            
            $tramite->fut="/api/fut/".$tramite->idTramite;
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
    //--------------------------------------------------------- 
    public function GetCarnetsAprobados(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        // TRÁMITES POR USUARIO
        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
        ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','programa.nombre as escuela')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',25)
        ->where('tipo_tramite.idTipo_tramite',3)
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
        ->where('tramite.estado',1)
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();
        
        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',25)
        ->where('tipo_tramite.idTipo_tramite',3)
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
        ->where('tramite.estado',1)
        ->count();


        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','tramite_requisito.idRequisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
        }

        // Respuesta paginada
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


    // creo que no se usa
    public function GetCarnetsAprobadosRefresh(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idEstado_tramite',25)
            ->where('tipo_tramite.idTipo_tramite',3)
            // ->where('tramite_detalle.asignado_certificado',$idUsuario)
            ->where('tramite.estado',1)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get(); 
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','tramite_requisito.idRequisito','requisito.responsable')
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
        
        return response()->json($tramites, 200); 
    }
    //------------------------------------------------------------------


    public function GetCarnetsSolicitados(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        
        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
        ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','programa.nombre as escuela')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',26)
        ->where('tipo_tramite.idTipo_tramite',3)
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
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();
        
        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',26)
        ->where('tipo_tramite.idTipo_tramite',3)
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
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','tramite_requisito.idRequisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
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


    public function setRecibidos(){
        DB::beginTransaction();
        try {
            $tramites=Tramite::where('idEstado_tramite',26)->get();
            foreach ($tramites as $key => $tramite) {
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=2;
                $historial_estados->idEstado_actual=26;
                $historial_estados->idEstado_nuevo=27;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
                $tramite->idEstado_tramite=$historial_estados->idEstado_nuevo;
                $tramite->save();
            }
            DB::commit();
            return response()->json(['status' => '200', 'message' => "Carnets entregados en la universidad."], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }


    public function GetCarnetsRecibidos(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
        ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','programa.nombre as escuela')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',27)
        ->where('tipo_tramite.idTipo_tramite',3)
        ->where(function($query) use ($idDependencia)
        {
            if ($idDependencia) {
                if ($idDependencia==15) {
                    $query->where('tramite.idPrograma',41)
                    ->orWhere('tramite.idPrograma',42)
                    ->orWhere('tramite.idPrograma',43)
                    ->orWhere('tramite.idPrograma',44)
                    ->orWhere('tramite.idPrograma',45)
                    ->orWhere('tramite.idPrograma',46)
                    ->orWhere('tramite.idPrograma',48)
                    ->orWhere('tramite.idPrograma',49);
                }elseif($idDependencia==11){
                    $query->where('tramite.idPrograma',11)
                    ->orWhere('tramite.idPrograma',47);
                }
                else {
                    $query->where('tramite.idPrograma',$idDependencia);
                }
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
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();
        

        $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa','tramite.idPrograma','programa.idPrograma')
        ->where('tramite.idEstado_tramite',27)
        ->where('tipo_tramite.idTipo_tramite',3)
        ->where(function($query) use ($idDependencia)
        {
            if ($idDependencia) {
                if ($idDependencia==15) {
                    $query->where('tramite.idPrograma',41)
                    ->orWhere('tramite.idPrograma',42)
                    ->orWhere('tramite.idPrograma',43)
                    ->orWhere('tramite.idPrograma',44)
                    ->orWhere('tramite.idPrograma',45)
                    ->orWhere('tramite.idPrograma',46)
                    ->orWhere('tramite.idPrograma',48)
                    ->orWhere('tramite.idPrograma',49);
                }elseif($idDependencia==11){
                    $query->where('tramite.idPrograma',11)
                    ->orWhere('tramite.idPrograma',47);
                }
                else {
                    $query->where('tramite.idPrograma',$idDependencia);
                }
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
        ->count();
        


        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','tramite_requisito.idRequisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
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


    public function setEntregado(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        DB::beginTransaction();
        try {
            // Datos de usuario
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')->find($request->idTramite);
            $historial_estados=new Historial_Estado;
            $historial_estados->idTramite=$tramite->idTramite;
            $historial_estados->idUsuario=$idUsuario;
            $historial_estados->idEstado_actual=27;
            $historial_estados->idEstado_nuevo=15;
            $historial_estados->fecha=date('Y-m-d h:i:s');
            $historial_estados->save();
            $tramite->idEstado_tramite=$historial_estados->idEstado_nuevo;
            $tramite->save();
            DB::commit();
            return response()->json($tramite,200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    // creo que no se utiliza
    public function GetCarnetsEntregados(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idTipo_usuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        if ($idTipo_usuario==5) {
            // $secretaria=User::find($idUsuario);
            if ($request->query('search')!="") {
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->where('tramite.idEstado_tramite',15)
                ->where('tipo_tramite.idTipo_tramite',3)
                ->where('tramite.idDependencia_detalle',$idDependencia)
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
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->where('tramite.idEstado_tramite',15)
                ->where('tipo_tramite.idTipo_tramite',3)
                ->where('tramite.idDependencia_detalle',$idDependencia)
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();   
            }
        }else {
            if ($request->query('search')!="") {
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->where('tramite.idEstado_tramite',15)
                ->where('tipo_tramite.idTipo_tramite',3)
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
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->where('tramite.idEstado_tramite',15)
                ->where('tipo_tramite.idTipo_tramite',3)
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();   
            }
        }        
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','tramite_requisito.idRequisito','requisito.responsable')
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
    }
    //---------------------------------------------------

    public function EnvioValidacionSunedu()
    {
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            //obtener carnets validados
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tramite.idEstado_tramite')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tipo_tramite.idTipo_tramite',3)
            ->where('tramite.idEstado_tramite',7)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',17)
                ->orWhere('tramite.idTipo_tramite_unidad',19)
                ->orWhere('tramite.idTipo_tramite_unidad',21)
                ->orWhere('tramite.idTipo_tramite_unidad',23);
            })
            ->get(); 
            foreach ($tramites as $key => $tramite) {
                //CAMBIAMOS EL ESTADO DE CADA TRÁMITE A PENDIENTE DE VALIDACIÓN DE SUNEDU
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                $historial_estados->idEstado_nuevo=16;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
                $tramite->idEstado_tramite=$historial_estados->idEstado_nuevo;
                $tramite->save();
            }
            DB::commit();
            return response()->json(['status' => '200'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e], 400);
        }
        

    }
    public function import(Request $request){
        DB::beginTransaction();
        try {

            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];

            $importacion = new TramitesImport;
            Excel::import( $importacion, $request->file);

            //CAMBIAR DE ESTADO TODOS LOS CARNETS QUE SIGAN CON ESTADO 16
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where(function($query)
            {
                $query->where('tramite.idEstado_tramite',7)
                ->orWhere('tramite.idEstado_tramite',16);
            })
            ->where('tipo_tramite.idTipo_tramite',3)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',17)
                ->orWhere('tramite.idTipo_tramite_unidad',19)
                ->orWhere('tramite.idTipo_tramite_unidad',21)
                ->orWhere('tramite.idTipo_tramite_unidad',23);
            })
            ->get();  
            DB::commit();
            return response()->json($tramites, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }



    public function aprobadosImport(Request $request){
        DB::beginTransaction();
        try {

            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];

            $importacion = new AprobadosImport;
            Excel::import( $importacion, $request->file);

            //CAMBIAR DE ESTADO TODOS LOS CARNETS QUE SIGAN CON ESTADO 16
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where(function($query)
            {
                $query->where('tramite.idEstado_tramite',7)
                ->orWhere('tramite.idEstado_tramite',16);
            })
            ->where('tipo_tramite.idTipo_tramite',3)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',17)
                ->orWhere('tramite.idTipo_tramite_unidad',19)
                ->orWhere('tramite.idTipo_tramite_unidad',21)
                ->orWhere('tramite.idTipo_tramite_unidad',23);
            })
            ->get();  
            DB::commit();
            return response()->json($tramites, 200);
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