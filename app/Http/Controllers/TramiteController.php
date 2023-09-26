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
use App\Jobs\AnularTramiteJob;
use App\Jobs\ActualizacionTramiteJob;
use App\Jobs\ObservacionTramiteJob;
use App\Jobs\FinalizacionCarnetJob;
use App\Jobs\NotificacionCertificadoJob;
use App\Jobs\NotificacionCarpetaJob;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use App\Imports\TramitesImport;
use App\Exports\TramitesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\PersonaSE;
use App\Amnistia;

use App\Mencion;
use App\Escuela;
use App\Motivo_Certificado;
use App\PersonaSuv;
use App\PersonaSga;
use App\Cronograma;
use App\Resolucion;
use App\MatriculaSUV;
use App\MatriculaSGA;
use App\SemestreAcademico;
class TramiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt',['except' => ['chancarExonerado']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // TRÁMITES POR USUARIO
        $tramites=Tramite::all();
        foreach ($tramites as $key => $tramite) {
            // Obtenemos el tipo de cada trámite
            $tramite->tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)->first();
            $tramite->tipo_tramite=Tipo_Tramite::Where('idTipo_tramite',$tramite->tipo_tramite_unidad->idTipo_tramite)->first();
            $tramite->historial=Historial_Estado::Where('idTramite',$tramite->idTramite)->get();
            foreach ($tramite->historial as $key => $item) {
                if($item->idEstado_actual!=null){
                    $item->estado_actual=Estado_Tramite::Where('idEstado_tramite',$item->idEstado_actual)->get();
                }else{
                    $item->estado_actual="Ninguno";
                }
                $item->estado_nuevo=Estado_Tramite::Where('idEstado_tramite',$item->idEstado_nuevo)->get();
            }
        }
        return response()->json(['status' => '200', 'tramites' => $tramites], 200);
        // return $tramites;
    }

    public function GetByUser(Request $request)
    {
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            $idTipo_usuario=$apy['idTipo_usuario'];
            
            if ($idTipo_usuario==1 || $idTipo_usuario == 20) {
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.nro_tramite','tramite.created_at','tramite.idTramite','tramite.idTipo_tramite_unidad','estado_tramite.idEstado_tramite',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado',
                'tramite.idUsuario_asignado','usuario.correo','tramite.uuid')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->Where('tramite.idEstado_tramite','!=',29)
                ->Where('tramite.idEstado_tramite','!=',15)
                ->where(function($query) use ($request)
                {
                    $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('created_at','LIKE','%'.$request->query('search').'%')
                    ->orWhere('estado_tramite.nombre','LIKE','%'.$request->query('search').'%');
                })
                ->orderBy($request->query('sort'), $request->query('order'))
                ->take($request->query('size'))
                ->skip($request->query('page')*$request->query('size'))
                ->get();
                $total = Tramite::join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->Where('tramite.idEstado_tramite','!=',29)
                ->Where('tramite.idEstado_tramite','!=',15)
                ->where(function($query) use ($request)
                {
                    $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('created_at','LIKE','%'.$request->query('search').'%')
                    ->orWhere('estado_tramite.nombre','LIKE','%'.$request->query('search').'%');
                })
                ->count();
            }elseif($idTipo_usuario==13){
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.nro_tramite','tramite.created_at','tramite.idTramite','tramite.idTipo_tramite_unidad','estado_tramite.idEstado_tramite',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado','tramite.idUsuario_asignado',
                'tramite.uuid')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                ->where('tipo_tramite.idTipo_tramite',1)
                ->Where('tramite.idEstado_tramite','!=',29)
                ->Where('tramite.idEstado_tramite','!=',15)
                ->where(function($query) use ($request)
                {
                    $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('created_at','LIKE','%'.$request->query('search').'%')
                    ->orWhere('estado_tramite.nombre','LIKE','%'.$request->query('search').'%');
                })
                ->orderBy($request->query('sort'), $request->query('order'))
                ->take($request->query('size'))
                ->skip($request->query('page')*$request->query('size'))
                ->get();
                $total = Tramite::join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->where('tipo_tramite.idTipo_tramite',1)
                ->Where('tramite.idEstado_tramite','!=',29)
                ->Where('tramite.idEstado_tramite','!=',15)
                ->where(function($query) use ($request)
                {
                    $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('created_at','LIKE','%'.$request->query('search').'%')
                    ->orWhere('estado_tramite.nombre','LIKE','%'.$request->query('search').'%');
                })
                ->count();
            }elseif($idTipo_usuario==9){
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.nro_tramite','tramite.created_at','tramite.idTramite','tramite.idTipo_tramite_unidad','estado_tramite.idEstado_tramite',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado','tramite.idUsuario_asignado',
                'tramite.uuid')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                ->Where('tramite.idEstado_tramite','!=',29)
                ->Where('tramite.idEstado_tramite','!=',15)
                ->where('tipo_tramite.idTipo_tramite',2)
                ->where(function($query) use ($request)
                {
                    $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('created_at','LIKE','%'.$request->query('search').'%')
                    ->orWhere('estado_tramite.nombre','LIKE','%'.$request->query('search').'%');
                })
                ->orderBy($request->query('sort'), $request->query('order'))
                ->take($request->query('size'))
                ->skip($request->query('page')*$request->query('size'))
                ->get();
                $total = Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                ->Where('tramite.idEstado_tramite','!=',29)
                ->Where('tramite.idEstado_tramite','!=',15)
                ->where('tipo_tramite.idTipo_tramite',2)
                ->where(function($query) use ($request)
                {
                    $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('created_at','LIKE','%'.$request->query('search').'%')
                    ->orWhere('estado_tramite.nombre','LIKE','%'.$request->query('search').'%');
                })
                ->count();
            }else {
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.nro_tramite','tramite.created_at','tramite.idTramite','tramite.idTipo_tramite_unidad','estado_tramite.idEstado_tramite',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado','tramite.idUsuario_asignado',
                'tramite.uuid')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                ->Where('tramite.idUsuario',$idUsuario)
                ->Where('tramite.idEstado_tramite','!=',29)
                ->where(function($query) use ($request)
                {
                    $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('created_at','LIKE','%'.$request->query('search').'%')
                    ->orWhere('estado_tramite.nombre','LIKE','%'.$request->query('search').'%');
                })
                ->orderBy($request->query('sort'), $request->query('order'))
                ->take($request->query('size'))
                ->skip($request->query('page')*$request->query('size'))
                ->get();

                $total = Tramite::join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->Where('tramite.idUsuario',$idUsuario)
                ->Where('tramite.idEstado_tramite','!=',29)
                ->where(function($query) use ($request)
                {
                    $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('created_at','LIKE','%'.$request->query('search').'%')
                    ->orWhere('estado_tramite.nombre','LIKE','%'.$request->query('search').'%');
                })
                ->count();
            }
            foreach ($tramites as $key => $tramite) {
                if ($tramite->idUsuario_asignado) {
                    $tramite['usuario_asignado']=User::select(DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as usuario'))->findOrFail($tramite->idUsuario_asignado);
                }
                $tramite->historial=Historial_Estado::Where('idTramite',$tramite->idTramite)->where('estado',true)->get();
                foreach ($tramite->historial as $key => $item) {
                    if($item->idEstado_actual!=null){
                        $item->estado_actual=Estado_Tramite::Where('idEstado_tramite',$item->idEstado_actual)->first();
                    }else{
                        $item->estado_actual="Ninguno";
                    }
                    $item->estado_nuevo=Estado_Tramite::Where('idEstado_tramite',$item->idEstado_nuevo)->first();
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
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }  
    }

    public function GetTramiteById($id)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $dni=$apy['nro_documento'];
        $idTipo_usuario=$apy['idTipo_usuario'];
     
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.created_at as fecha', 'tramite.exonerado_archivo', 'tramite.nro_tramite', 'tramite.nro_matricula',
            'tramite.comentario as comentario_tramite','tramite.sede','tramite.idEstado_tramite','tramite_detalle.idMotivo_certificado',
            'unidad.descripcion as unidad', 'dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.idTipo_tramite_unidad', 'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo','tipo_tramite_unidad.costo_exonerado',
            'tipo_tramite.descripcion as tipo_tramite', 'tipo_tramite.idTipo_tramite',
            'usuario.nro_documento','usuario.correo', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),
            'voucher.archivo as voucher', 'voucher.nro_operacion', 'voucher.entidad', 'voucher.fecha_operacion', 'voucher.comentario as comentario_voucher',
            'voucher.des_estado_voucher','tramite.uuid')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->Where('tramite.idTramite',$id)
            ->first();   
         
                $tramite->fut="fut/".$tramite->uuid;
                //Requisitos
                $tramite->requisitos=Tramite_Requisito::select('requisito.*','tramite_requisito.idTramite','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador',
                'tramite_requisito.validado','tramite_requisito.comentario','tramite_requisito.des_estado_requisito','tramite_requisito.estado')
                ->join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
                ->where('tramite_requisito.idTramite',$tramite->idTramite)
                ->get();

        return response()->json($tramite, 200);
    }

    public function GetTramitesByUser(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $dni=$apy['nro_documento'];
        $idTipo_usuario=$apy['idTipo_usuario'];
        
        // TRÁMITES POR USUARIO
        if ($idTipo_usuario == 1 || $idTipo_usuario == 20) {
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.created_at as fecha', 'tramite.exonerado_archivo', 'tramite.nro_tramite', 'tramite.nro_matricula',
            'tramite.comentario as comentario_tramite','tramite.sede','tramite.idEstado_tramite','tramite_detalle.idMotivo_certificado',
            'unidad.descripcion as unidad', 'dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.idTipo_tramite_unidad', 'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo','tipo_tramite_unidad.costo_exonerado',
            'tipo_tramite.descripcion as tipo_tramite', 'tipo_tramite.idTipo_tramite',
            'usuario.nro_documento','usuario.correo', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),
            'voucher.archivo as voucher', 'voucher.nro_operacion', 'voucher.entidad', 'voucher.fecha_operacion', 'voucher.comentario as comentario_voucher',
            'voucher.des_estado_voucher','tramite.uuid')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->Where('tramite.idEstado_tramite','!=',29)
            ->Where('tramite.idEstado_tramite','!=',15)
            ->get();   
            foreach ($tramites as $key => $tramite) {
                $tramite->fut="fut/".$tramite->uuid;
                //Requisitos
                $tramite->requisitos=Tramite_Requisito::select('requisito.*','tramite_requisito.idTramite','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador',
                'tramite_requisito.validado','tramite_requisito.comentario','tramite_requisito.des_estado_requisito','tramite_requisito.estado')
                ->join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
                ->where('tramite_requisito.idTramite',$tramite->idTramite)
                ->get();
                // Obtenemos el motivo certificado(en caso lo tengan) de cada trámite 
                // if ($tramite->idTipo_tramite==1) {
                //     $motivo=Motivo_Certificado::Where('idMotivo_certificado',$tramite->idMotivo_certificado)->first();
                //     $tramite->motivo=$motivo->nombre;
                // }
            }
        } else {
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.created_at as fecha', 'tramite.exonerado_archivo', 'tramite.nro_tramite', 'tramite.nro_matricula',
            'tramite.comentario as comentario_tramite','tramite.sede','tramite.idEstado_tramite','tramite_detalle.idMotivo_certificado',
            'unidad.descripcion as unidad', 'dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.idTipo_tramite_unidad', 'tipo_tramite_unidad.descripcion as tipo_tramite_unidad','tipo_tramite_unidad.costo','tipo_tramite_unidad.costo_exonerado',
            'tipo_tramite.descripcion as tipo_tramite', 'tipo_tramite.idTipo_tramite',
            'usuario.nro_documento','usuario.correo', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),
            'voucher.archivo as voucher', 'voucher.nro_operacion', 'voucher.entidad', 'voucher.fecha_operacion', 'voucher.comentario as comentario_voucher',
            'voucher.des_estado_voucher','tramite.uuid')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idusuario',$idUsuario)
            ->get();   
            foreach ($tramites as $key => $tramite) {
                $tramite->fut="fut/".$tramite->uuid;
                //Requisitos
                $tramite->requisitos=Tramite_Requisito::select('requisito.*','tramite_requisito.idTramite','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador',
                'tramite_requisito.validado','tramite_requisito.comentario','tramite_requisito.des_estado_requisito','tramite_requisito.estado')
                ->join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
                ->where('tramite_requisito.idTramite',$tramite->idTramite)
                ->get();
            }
        }
        return response()->json($tramites, 200);
    }


    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            $usuario = User::findOrFail($idUsuario);
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_tramite_unidad',$request->idTipo_tramite_unidad)->first();
            
            //VALIDACIONES CUANDO ES ELABORACIÓN DE CARPETA O SOLICITUD DE CARNÉ
            if ($tipo_tramite_unidad->idTipo_tramite==2) {
                // VALIDACION DE REPETICIÓN DE TRÁMITES
                $tramite_validate=Tramite::where('idUsuario',$idUsuario)
                ->where('idTipo_tramite_unidad',$request->idTipo_tramite_unidad)
                ->where('idEstado_tramite','!=','29')
                ->first();
                if ($tramite_validate) {
                    return response()->json(['status' => '400', 'message' => 'Ya tiene un trámite registrado para '.$tipo_tramite_unidad->descripcion], 400);
                }

                // Verificando que sea alumno de universidad no licenciada(10) o amnistiados(9)
                $alumnoSUV=PersonaSuv::join('matriculas.alumno','matriculas.alumno.idpersona','persona.idpersona')
                ->where(function($query) {
                    $query->where('idmodalidadingreso',9)
                    ->orWhere('idmodalidadingreso',10);
                })
                ->Where('per_dni',$dni)
                ->first();
                
                if (!$alumnoSUV) {
                    // VERIFICAR QUE LA PERSONA QUE REGISTRA SEA EGRESADO
                    // DEBE VALIDARSE SI ES EGRESADO POR NÚMERO DE MATRÍCULA PORQUE EXISTEN ALUMNOS CON 2 CARRERAS
                    if ($request->idUnidad==1) {
                        $alumnoSUV=PersonaSuv::join('matriculas.alumno','matriculas.alumno.idpersona','persona.idpersona')
                        ->where('alu_estado',6)
                        ->Where('per_dni',$dni)
                        ->first();
                        if (!$alumnoSUV) {

                            $amnistiado=Amnistia::where('nro_documento',$dni)->first();
                            if (!$amnistiado) {
                                # code...
                                $alumnoSGA=PersonaSga::join('perfil','persona.per_id','perfil.per_id')
                                ->join('sga_datos_alumno','sga_datos_alumno.pfl_id','perfil.pfl_id')
                                ->Where('sga_datos_alumno.con_id',6)
                                ->Where('perfil.pfl_estado',true)
                                ->Where('per_dni',$dni)
                                ->first();
                                if (!$alumnoSGA) {
                                    return response()->json(['status' => '400', 'message' => 'Usted no se encuentra registrado como egresado para realizar este trámite. Coordinar con tu secretaria de escuela para actualizar tu condición.'], 400);
                                }
                            }
                        }
                    }elseif ($request->idUnidad==2) {
                        
                    }elseif ($request->idUnidad==3) {
                        
                    }else {
                       
                    }
                }
            }
            if ($tipo_tramite_unidad->idTipo_tramite==3) {
                // OBTENIENDO EL SEMESTRE ACADÉMICO ACTUAL
                $semestreAcademico=SemestreAcademico::where('estado',true)->first();
                // VALIDACIÓN DE MATRÍCULA PAGADA EN EL SUV
                $matriculaSuv=MatriculaSUV::select('orden_pago.ord_estado')
                ->join('matriculas.alumno','matricula.idalumno','alumno.idalumno')
                ->join('sistema.persona','alumno.idpersona','persona.idpersona')
                ->join('matriculas.orden_pago','matricula.idmatricula' ,'orden_pago.idmatricula')
                ->where('matricula.mat_estado',true)
                ->where(function($query) use($semestreAcademico)
                {
                    $query->where('matricula.mat_periodo',$semestreAcademico->anio.'-'.$semestreAcademico->periodo)
                    ->orWhere('matricula.mat_periodo',$semestreAcademico->anio.'-ANUAL');
                })
                // ->where('matricula.mat_periodo',$semestreAcademico->anio.'-'.$semestreAcademico->periodo)
                ->where('alumno.alu_estado',true)
                ->where('persona.per_dni',$dni)
                ->first();
                if ($matriculaSuv) {
                    if (trim($matriculaSuv->ord_estado)=="PENDIENTE") {
                        return response()->json(['status' => '400', 'message' => 'Falta subir sus datos de pago de su matrícula del semestre académico actual.'], 400);
                    }elseif (trim($matriculaSuv->ord_estado)=="PENDIENTE DE VALIDACION") {
                        return response()->json(['status' => '400', 'message' => 'Pendiente de validación de sus datos de pago de matrícula del semestre académico actual. Coordinar con su secretaria de escuela la validación.'], 400);
                    }
                }else {
                    // VALIDACIÓN DE MATRÍCULA PAGADA EN EL SGA
                    $matriculaSga=MatriculaSGA::select('sga_orden_pago.ord_pagado')
                    ->join('sga_anio', 'sga_anio.ani_id' ,'sga_matricula.ani_id')
                    ->join('sga_tanio' , 'sga_tanio.tan_id' ,'sga_anio.tan_id')
                    ->join('perfil', 'perfil.pfl_id' , 'sga_matricula.pfl_id')
                    ->join('persona' , 'persona.per_id' , 'perfil.per_id')
                    ->join('sga_orden_pago' , 'sga_matricula.mat_id', 'sga_orden_pago.mat_id')
                    ->where('sga_matricula.mat_estado', 1)
                    ->where('sga_orden_pago.ord_pagado', 1)
                    ->where('sga_anio.ani_anio',$semestreAcademico->anio)
                    ->where(function($query) use($semestreAcademico)
                    {
                        $query->where('sga_tanio.tan_semestre',$semestreAcademico->periodo)
                        ->orWhere('sga_tanio.tan_semestre',"Anual");
                    })
                    // ->where('sga_tanio.tan_semestre',$semestreAcademico->periodo)
                    ->where('persona.per_dni',$dni)
                    ->first();
                    if ($matriculaSga) {
                        if ($matriculaSga->ord_pagado!=1) {
                            return response()->json(['status' => '400', 'message' => 'Usted No cuenta con una matrícula PAGADA para el semestre académico actual.'], 400);
                        }
                    }else {
                        // cuando no haya nada en el suv y sga, no tiene matrícula en el semestre actual
                        return response()->json(['status' => '400', 'message' => 'Usted no cuenta con una matrícula para el semestre académico actual.'], 400);
                    }
                }
                // VALIDACION DE REPETICIÓN DE TRÁMITES REGULARES
                if ($request->idTipo_tramite_unidad==17||$request->idTipo_tramite_unidad==19||$request->idTipo_tramite_unidad==21||$request->idTipo_tramite_unidad==23) {
                    $tramite_validate=Tramite::where('idUsuario',$idUsuario)
                    ->where('idTipo_tramite_unidad',$request->idTipo_tramite_unidad)
                    ->where('idEstado_tramite','!=','29')
                    ->first();
                    if ($tramite_validate) {
                        return response()->json(['status' => '400', 'message' => 'Ya tiene un trámite registrado para '.$tipo_tramite_unidad->descripcion], 400);
                    }
                }
            }
            
            $voucher_validate=$this->validarVoucher(trim($request->entidad),trim($request->nro_operacion),trim($request->fecha_operacion), $idUsuario);
            if($voucher_validate) return response()->json(['status' => '400', 'message' => 'El voucher ya se encuentra registrado'], 400);
            
            $tramite=new Tramite;
            //AÑADIMOS EL NÚMERO DE TRÁMITE
            $inicio=date('Y-m-d')." 00:00:00";
            $fin=date('Y-m-d')." 23:59:59";
            $last_tramite=Tramite::whereBetween('created_at', [$inicio , $fin])->where('idTipo_tramite_unidad','!=',37)
            ->orderBy("created_at","DESC")->first();
            
            if ($last_tramite) {
                $correlativo=(int)(substr($last_tramite->nro_tramite,0,4));
                $correlativo++;
                if ($correlativo<10) $tramite->nro_tramite = "000".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                else if ($correlativo<100) $tramite->nro_tramite = "00".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                else if ($correlativo<1000) $tramite->nro_tramite = "0".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                else $tramite->nro_tramite = $correlativo.date('d').date('m').substr(date('Y'),2,3);
            }else{
                $tramite -> nro_tramite="0001".date('d').date('m').substr(date('Y'),2,3);
            }
            
            // REGISTRAMOS LE VOUCHER
            if(!$request->hasFile("archivo") && !$request->hasFile("archivo_exonerado")) {
                return response()->json(['status' => '400', 'message' =>"Datos incompletos"], 400);
            }
            $voucher=new Voucher;
            $voucher->entidad=trim($request->entidad);
            $voucher->nro_operacion=trim($request->nro_operacion);
            $voucher->fecha_operacion=trim($request->fecha_operacion);

            // GUARDAMOS EL ARCHIVO DEL VOUCHER
            if($request->hasFile("archivo")){
                $file=$request->file("archivo");
                $nombre = $tramite->nro_tramite.'.'.$file->guessExtension();
                $nombreBD = "/storage/vouchers_tramites/".$nombre;
                if($file->guessExtension()=="pdf"){
                  $file->storeAs('public/vouchers_tramites', $nombre);
                  $voucher->archivo = $nombreBD;
                }else {
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' => "Subir archivo del comprobante de pago en pdf"], 400);
                }
            }
            if($request->hasFile("archivo_exonerado")){
                $file=$request->file("archivo_exonerado");
                $nombre = $tramite->nro_tramite.'.'.$file->guessExtension();
                $nombreBD = "/storage/exonerados/".$nombre;
                if($file->guessExtension()=="pdf"){
                  $file->storeAs('public/exonerados', $nombre);
                  $tramite->exonerado_archivo = $nombreBD;
                }else {
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' => "Subir archivo de exonerado en pdf"], 400);
                }
            }
            $voucher->comentario=null;
            $voucher->save();

            // REGISTRAMOS EL DETALLE DEL TRÁMITE REGISTRADO
            $tipo_tramite = Tipo_Tramite::select('tipo_tramite.idTipo_tramite','tipo_tramite.descripcion','tipo_tramite.filename')->join('tipo_tramite_unidad', 'tipo_tramite_unidad.idTipo_tramite', 'tipo_tramite.idTipo_tramite')
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad', $request->idTipo_tramite_unidad)->first();
            $tramite_detalle = new Tramite_Detalle();
            switch ($tipo_tramite->idTipo_tramite) {
                case 1:
                    $tramite_detalle->idCronograma_carpeta = null;
                    $tramite_detalle->idMotivo_certificado=2;
                    break;
                case 2:
                    if ($request->idCronograma_carpeta) {
                        $tramite_detalle->idCronograma_carpeta = trim($request->idCronograma_carpeta);
                        $tramite_detalle->idMotivo_certificado=null;
                    }else {
                        DB::rollback();
                        return response()->json(['status' => '400', 'message' => "Seleccionar una fecha de colación"], 400);
                    }
                    break;
                case 3:
                    $tramite_detalle->idCronograma_carpeta = null;
                    $tramite_detalle->idMotivo_certificado = null;
                    break;
            }
            $tramite_detalle->save();
            
            // REGISTRAMOS EL TRÁMITE
            $tramite -> idTramite_detalle=$tramite_detalle->idTramite_detalle;
            $tramite -> idTipo_tramite_unidad=trim($request->idTipo_tramite_unidad);
            $tramite -> idVoucher=$voucher->idVoucher;
            $tramite -> idUsuario=$idUsuario;
            $tramite -> idUnidad=trim($request->idUnidad);
            $tramite -> idDependencia=trim($request->idDependencia);
            $tramite -> idPrograma=trim($request->idPrograma);
            $tramite -> nro_matricula=trim($request->nro_matricula);
            $tramite -> comentario=trim($request->comentario);
            $tramite -> sede=trim($request->sede);
            $tramite -> idUsuario_asignado=null;
            $tramite -> idEstado_tramite=2;

            // Creando un uudi para realizar el llamado a los trámites por ruta

                // Verificando que no haya un uuid ya guardado en bd
                $tramiteUUID=true;
                while ($tramiteUUID) {
                    $uuid=Str::orderedUuid();
                    $tramiteUUID=Tramite::where('uuid',$uuid)->first();
                }
                $tramite -> uuid=$uuid;
            
            // ---------------------------------------------------
            if($request->hasFile("archivo_firma")){
                $file=$request->file("archivo_firma");
                $nombre = $tramite->nro_tramite.".".$file->guessExtension();
                $nombreBD = "/storage/firmas_tramites/".$nombre;
                if($file->guessExtension()=="jpg"){
                  $file->storeAs('public/firmas_tramites', $nombre);
                  $tramite->firma_tramite = $nombreBD;
                }else{
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' => "Subir archivo de la firma en jpg o revisar que no esté dañado"], 400);
                }
            } else {
                DB::rollback();
                return response()->json(['status' => '400', 'message' =>"Adjuntar firma"], 400);
            }
            $tramite -> save();

            // REGISTRAMOS LOS REQUISITOS DEL TRÁMITE REGISTRADO
            if($request->hasFile("files")){
                foreach ($request->file("files") as $key => $file) {
                    $requisito=json_decode($request->requisitos[$key],true);
                    $tramite_requisito=new Tramite_Requisito;
                    $tramite_requisito->idTramite=$tramite->idTramite;
                    $tramite_requisito->idRequisito=$requisito["idRequisito"];
                    $nombre = $dni.".".$file->guessExtension();
                    if ($tipo_tramite_unidad->idTipo_tramite==2) {
                        $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"]."/".$nombre;
                    }elseif ($tipo_tramite_unidad->idTipo_tramite==5) {
                        if ($requisito["idRequisito"]==74||$requisito["idRequisito"]==76||$requisito["idRequisito"]==78) {
                            $nombre=$tramite->nro_tramite.".".$file->guessExtension();
                        }
                        $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"]."/".$nombre;
                    }
                    else {
                        $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$requisito["nombre"]."/".$nombre;
                    }
                    if ($file->getClientOriginalName()!=="vacio.kj") {
                        if($file->guessExtension()==$requisito["extension"]){
                            if ($tipo_tramite->idTipo_tramite==2) {
                                $file->storeAs("/public"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"], $nombre);
                            }elseif ($tipo_tramite->idTipo_tramite==5) {
                                if ($requisito["idRequisito"]==74||$requisito["idRequisito"]==76||$requisito["idRequisito"]==78) {
                                    $nombre=$tramite->nro_tramite.".".$file->guessExtension();
                                }
                                $file->storeAs("/public"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"], $nombre);
                            }
                            else {
                                $file->storeAs("/public"."/".$tipo_tramite->filename."/".$requisito["nombre"], $nombre);
                            }
                            $tramite_requisito->archivo = $nombreBD;
                        }else {
                            DB::rollback();
                            return response()->json(['status' => '400', 'message' => "Subir ".$requisito["nombre"]." en ".$requisito["extension"]], 400);
                        }
                    }
                    $tramite_requisito -> save();
                }
            }

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estado=$this->setHistorialEstado($tramite->idTramite, null, 1, $idUsuario);
            $historial_estado->save();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estado=$this->setHistorialEstado($tramite->idTramite, 1, 2, $idUsuario);
            $historial_estado->save();
            dispatch(new RegistroTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
            DB::commit();
            return response()->json(['status' => '200', 'usuario' => 'Trámite registrado correctamente'], 200);
        
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function AsignacionTramites(Request $request){
        // return $request->tramites;
        DB::beginTransaction();
        try {
            foreach ($request->tramites as $key => $idTramite) {
                $tramite = Tramite::findOrFail($idTramite);
                
                //if ($tramite->idUsuario_asignado==null) {
                    // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
                    $token = JWTAuth::getToken();
                    $apy = JWTAuth::getPayload($token);
                    $idUsuarioToken=$apy['idUsuario'];

                    if ($tramite->idEstado_tramite==5) {
                        //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                        $historial_estados=new Historial_Estado;
                        $historial_estados->idTramite=$tramite->idTramite;
                        $historial_estados->idUsuario=$idUsuarioToken;
                        $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                        $historial_estados->idEstado_nuevo=6;
                        $historial_estados->fecha=date('Y-m-d h:i:s');
                        $historial_estados->save();
        
                        //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                        $historial_estados=new Historial_Estado;
                        $historial_estados->idTramite=$tramite->idTramite;
                        $historial_estados->idUsuario=$idUsuarioToken;
                        $historial_estados->idEstado_actual=6;
                        $historial_estados->idEstado_nuevo=7;
                        $historial_estados->fecha=date('Y-m-d h:i:s');
                        $historial_estados->save();
                        $tramite->idEstado_tramite = $historial_estados->idEstado_nuevo;
                        $tramite->update();
                    }
                //}

                $tramite->idUsuario_asignado=$request->idUsuario;
                $tramite->update();

            }
            DB::commit();
            return response()->json($request->tramites, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e], 400);
        }
    }
    
    public function updateTramiteRequisitos(Request $request)
    {
        // return $request->all();
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            //flag de requisitos rechazados o aprobados
            $flag=true;
            $flag2=true;
            $flagAlumno=false;
            $flagEscuela=false;
            $flagFacultad=false;
            
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite',
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo', 'tipo_tramite_unidad.idTipo_tramite',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'voucher.archivo as voucher','tramite.idTipo_tramite_unidad','tramite.uuid','tramite_detalle.certificado_final')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->find($request->idTramite);

            // DATOS PARA EL CORREO 
            $usuario=User::find($tramite->idUsuario);
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::find($tramite->idTipo_tramite_unidad);
            $tipo_tramite=Tipo_Tramite::find($tramite->idTipo_tramite);
            
            //Editamos los cada uno de los requisitos que llegan junto al trámite en el request
            foreach ($request->requisitos as $key => $requisito) {
                $tramite_requisito=Tramite_Requisito::Where('idTramite',$request->idTramite)
                ->where('idRequisito',$requisito['idRequisito'])->first();
                $tramite_requisito->idUsuario_aprobador=$idUsuario;
                $tramite_requisito->validado=$requisito['validado'];
                $tramite_requisito->des_estado_requisito=$requisito['des_estado_requisito'];
                $tramite_requisito->comentario=$requisito['comentario'];
                $tramite_requisito->save();
                // VERIFICANDO SI SE APRUEBA O RECHAZA POR PARTE DE LA ESCUELA O FACULTAD
                if ($tramite->idEstado_tramite==17) {
                    // Verificando que el estado del responsable sea el alumno(4)
                    if ($requisito['des_estado_requisito']=="RECHAZADO" && $requisito['responsable']==4 ) {
                        $flag=false;
                    }
                    if ($requisito['des_estado_requisito']=="PENDIENTE" && $requisito['responsable']==4) {
                        $flag2=false;
                    }
                }elseif($tramite->idEstado_tramite==20){
                    // Verificando que el estado del responsable sea el alumno(4) o escuela(5)
                    if ($requisito['des_estado_requisito']=="RECHAZADO" && ($requisito['responsable']==4 || $requisito['responsable']==5||$requisito['responsable']==17)) {
                        $flag=false;
                        if ($requisito['responsable']==4) {
                            $flagAlumno=true;
                        } else if ($requisito['responsable']==5||$requisito['responsable']==17) {
                            $flagEscuela = true;
                        }
                    }
                    if ($requisito['des_estado_requisito']=="PENDIENTE" && ($requisito['responsable']==4 || $requisito['responsable']==5||$requisito['responsable']==17)) {
                        $flag2=false;
                    }
                }else {
                    if ($requisito['des_estado_requisito']=="RECHAZADO" && ($requisito['responsable']==4 || $requisito['responsable']==5|| $requisito['responsable']==8||$requisito['responsable']==17)) {
                        $flag=false;
                        if ($requisito['responsable']==4) {
                            $flagAlumno=true;
                        }elseif($requisito['responsable']==5||$requisito['responsable']==17){
                            $flagEscuela=true;
                        }elseif($requisito['responsable']==8) {
                            $flagFacultad=true;
                        }
                    }
                    if ($requisito['des_estado_requisito']=="PENDIENTE" && ($requisito['responsable']==4 || $requisito['responsable']==5|| $requisito['responsable']==8||$requisito['responsable']==17)) {
                        $flag2=false;
                    }
                }
            }
            // var_dump($flag); //false = hay rechazados
            // var_dump($flag2); //false = hay pendientes
            // var_dump($flagAlumno); // true = sí hay rechazado de alumno
            // var_dump($flagEscuela); // true = sí hay rechazado de escuela
            // var_dump($flagFacultad); // true = sí hay rechazado de facultad
            // return "hola";
            // SI NO HAY PENDIENTES 
            if ($flag2) {
                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                //Verificamos si todos los requisitos fueron aprobados($flag=true) o no($flag=false) 
                if ($flag) {
                    if ($tramite->idTipo_tramite==1) {
                        $historial_estados->idEstado_nuevo=8;
                    }elseif ($tramite->idTipo_tramite==2) {
                        // VERIFICANDO SI LA APROBACIÓN SE ESTÁ HACIENDO DESDE ESCUELA O FACULTAD MEDIANTE EL ESTADO DEL TRÁMITE
                        if ($tramite->idEstado_tramite==7) {
                            $historial_estados->idEstado_nuevo=8;
                            $historial_estados->fecha=date('Y-m-d h:i:s');
                            $historial_estados->save();
                            
                            //REGISTRAMOS EL ESTADO DEL TRÁMITE 
                            $historial_estados=new Historial_Estado;
                            $historial_estados->idTramite=$tramite->idTramite;
                            $historial_estados->idUsuario=$idUsuario;
                            $historial_estados->idEstado_actual=8;
                            $historial_estados->idEstado_nuevo=39;
                        }
                        elseif ($tramite->idEstado_tramite==17) {
                            $historial_estados->idEstado_nuevo=18;
                            $historial_estados->fecha=date('Y-m-d h:i:s');
                            $historial_estados->save();
                            
                            //REGISTRAMOS EL ESTADO DEL TRÁMITE 
                            $historial_estados=new Historial_Estado;
                            $historial_estados->idTramite=$tramite->idTramite;
                            $historial_estados->idUsuario=$idUsuario;
                            $historial_estados->idEstado_actual=18;
                            $historial_estados->idEstado_nuevo=30;
                        }else {
                            $historial_estados->idEstado_nuevo=21;
                            $historial_estados->fecha=date('Y-m-d h:i:s');
                            $historial_estados->save();
                            
                            //REGISTRAMOS EL ESTADO DEL TRÁMITE 
                            $historial_estados=new Historial_Estado;
                            $historial_estados->idTramite=$tramite->idTramite;
                            $historial_estados->idUsuario=$idUsuario;
                            $historial_estados->idEstado_actual=21;
                            $historial_estados->idEstado_nuevo=32;
                        }
                        
                    }elseif ($tramite->idTipo_tramite==3) {
                        $historial_estados->idEstado_nuevo=8;
                        $historial_estados->fecha=date('Y-m-d h:i:s');
                        $historial_estados->save();
                        
                        //REGISTRAMOS EL ESTADO DEL TRÁMITE 
                        $historial_estados=new Historial_Estado;
                        $historial_estados->idTramite=$tramite->idTramite;
                        $historial_estados->idUsuario=$idUsuario;
                        $historial_estados->idEstado_actual=8;
                        $historial_estados->idEstado_nuevo=25;
                    }
                    // $historial_estados->idEstado_nuevo=8;
                }else{
                    if ($tramite->idTipo_tramite==1 || $tramite->idTipo_tramite==3) {
                        $historial_estados->idEstado_nuevo=9;
                    }elseif ($tramite->idTipo_tramite==2) {
                        // VERIFICANDO SI EL RECHAZO SE ESTÁ HACIENDO DESDE ESCUELA O FACULTAD MEDIANTE EL ESTADO DEL TRÁMITE
                        if ($tramite->idEstado_tramite==7) {
                            $historial_estados->idEstado_nuevo=9;
                            $historial_estados->fecha=date('Y-m-d h:i:s');
                            $historial_estados->save();
                            // SI HAY REQUISITO DE ALUMNO 
                            if ($flagAlumno) {
                                // ENVIAR CORREO AL ALUMNO
                                dispatch(new ObservacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
                            }elseif ($flagEscuela) {
                                // SI HAY REQUISITO DE LA ESCUELA
                                // PASAMOS A ESTADO DE ADJUNTAR DE LA ESCUELA
                                $historial_estados=new Historial_Estado;
                                $historial_estados->idTramite=$tramite->idTramite;
                                $historial_estados->idUsuario=$idUsuario;
                                $historial_estados->idEstado_actual=9;
                                $historial_estados->idEstado_nuevo=30;
                            }elseif ($flagFacultad) {
                                // SI HAY REQUISITO DE LA FACULTAD
                                // PASAMOS A ESTADO DE ADJUNTAR DE LA FACULTAD
                                $historial_estados=new Historial_Estado;
                                $historial_estados->idTramite=$tramite->idTramite;
                                $historial_estados->idUsuario=$idUsuario;
                                $historial_estados->idEstado_actual=9;
                                $historial_estados->idEstado_nuevo=32;
                            }
                        }elseif ($tramite->idEstado_tramite==17) {
                            $historial_estados->idEstado_nuevo=19;
                            // CORREO DE TRÁMITE OBSERVADO
                            dispatch(new ObservacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
                        }else{
                            $historial_estados->idEstado_nuevo=22;
                            $historial_estados->fecha=date('Y-m-d h:i:s');
                            $historial_estados->save();
                            if ($flagAlumno) {
                                // ENVIAR CORREO AL ALUMNO
                                dispatch(new ObservacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad)); 
                            }elseif ($flagEscuela) {
                                // SI HAY REQUISITO DE LA ESCUELA
                                // PASAMOS A ESTADO DE ADJUNTAR DE LA ESCUELA
                                $historial_estados=new Historial_Estado;
                                $historial_estados->idTramite=$tramite->idTramite;
                                $historial_estados->idUsuario=$idUsuario;
                                $historial_estados->idEstado_actual=22;
                                $historial_estados->idEstado_nuevo=30;
                            }
                        }
                    }
                }
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
                $tramite->idEstado_tramite = $historial_estados->idEstado_nuevo;
                $tramite->update();

            }else {
                // SI HAY PENDIENTES
                if ($flag==false) {
                    // SI HAY RECHAZADOS
                    //REGISTRAMOS EL ESTADO DEL TRÁMITE
                    $historial_estados=new Historial_Estado;
                    $historial_estados->idTramite=$tramite->idTramite;
                    $historial_estados->idUsuario=$idUsuario;
                    $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                    // VERIFICANDO SI EL RECHAZO SE ESTÁ HACIENDO DESDE ESCUELA, FACULTAD O URA MEDIANTE EL ESTADO DEL TRÁMITE
                    // SI URA OBSERVA
                    if ($tramite->idEstado_tramite==7) {
                        $historial_estados->idEstado_nuevo=9;
                        $historial_estados->fecha=date('Y-m-d h:i:s');
                        $historial_estados->save();
                        // SI HAY REQUISITO DE ALUMNO 
                        if ($flagAlumno) {
                            // ENVIAR CORREO AL ALUMNO
                            dispatch(new ObservacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
                        }elseif ($flagEscuela) {
                            // SI HAY REQUISITO DE LA ESCUELA
                            // PASAMOS A ESTADO DE ADJUNTAR DE LA ESCUELA
                            $historial_estados=new Historial_Estado;
                            $historial_estados->idTramite=$tramite->idTramite;
                            $historial_estados->idUsuario=$idUsuario;
                            $historial_estados->idEstado_actual=9;
                            $historial_estados->idEstado_nuevo=30;
                        }elseif ($flagFacultad) {
                            // SI HAY REQUISITO DE LA FACULTAD
                            // PASAMOS A ESTADO DE ADJUNTAR DE LA FACULTAD
                            $historial_estados=new Historial_Estado;
                            $historial_estados->idTramite=$tramite->idTramite;
                            $historial_estados->idUsuario=$idUsuario;
                            $historial_estados->idEstado_actual=9;
                            $historial_estados->idEstado_nuevo=32;
                        }
                    }elseif ($tramite->idEstado_tramite==17) {
                        $historial_estados->idEstado_nuevo=19;
                        // CORREO DE TRÁMITE OBSERVADO
                        dispatch(new ObservacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
                    }else{
                        $historial_estados->idEstado_nuevo=22;
                        $historial_estados->fecha=date('Y-m-d h:i:s');
                        $historial_estados->save();
                        if ($flagAlumno) {
                            // ENVIAR CORREO AL ALUMNO
                            dispatch(new ObservacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
                        }elseif ($flagEscuela) {
                            // SI HAY REQUISITO DE LA ESCUELA
                            // PASAMOS A ESTADO DE ADJUNTAR DE LA ESCUELA
                            $historial_estados=new Historial_Estado;
                            $historial_estados->idTramite=$tramite->idTramite;
                            $historial_estados->idUsuario=$idUsuario;
                            $historial_estados->idEstado_actual=22;
                            $historial_estados->idEstado_nuevo=30;
                        }
                    }
                    $historial_estados->fecha=date('Y-m-d h:i:s');
                    $historial_estados->save();

                    $tramite->idEstado_tramite = $historial_estados->idEstado_nuevo;
                    $tramite->update();
                }else {
                    return response()->json(['status' => '400', 'message' => "Requisitos pendientes de validación"], 400);
                }
            }
            
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$request->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->uuid;
            // mensaje de validación de voucher
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)->first();
            $tipo_tramite = Tipo_Tramite::select('tipo_tramite.idTipo_tramite','tipo_tramite.descripcion')
            ->join('tipo_tramite_unidad', 'tipo_tramite_unidad.idTipo_tramite', 'tipo_tramite.idTipo_tramite')
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad', $tramite->idTipo_tramite_unidad)->first();
            $usuario = User::findOrFail($tramite->idUsuario);
            // dispatch(new ActualizacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function updateVoucher(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            
            
            // Editamos el voucher
            $tramite=Tramite::where('idTramite',$id)->first();
            $voucher=Voucher::where('idVoucher',$tramite->idVoucher)->first();
            $voucher->entidad=trim($request->entidad);
            $voucher->nro_operacion=trim($request->nro_operacion);
            $voucher->fecha_operacion=trim($request->fecha_operacion);
            $voucher->des_estado_voucher='PENDIENTE';
            $voucher->idUsuario_aprobador=null;
            $voucher->comentario=null;

            $tramite->idEstado_tramite=2;
            
            if($request->hasFile("archivo_voucher")){
                $file=$request->file("archivo_voucher");
                $nombre = $tramite->nro_tramite.'.'.$file->guessExtension();
                $nombreBD = "/storage/vouchers_tramites/".$nombre;
                if($file->guessExtension()=="pdf"){
                    $file->storeAs('public/vouchers_tramites', $nombre);
                    $voucher->archivo = $nombreBD;
                } else {
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' => "Subir archivo del comprobante de pago en pdf"], 400);
                }
            }
            if($request->hasFile("archivo_exonerado")){
                $file=$request->file("archivo_exonerado");
                $nombre = $tramite->nro_tramite.'.'.$file->guessExtension();
                $nombreBD = "/storage/exonerados/".$nombre;
                if($file->guessExtension()=="pdf"){
                  $file->storeAs('public/exonerados', $nombre);
                  $tramite->exonerado_archivo = $nombreBD;
                } else {
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' => "Subir archivo de exonerado en pdf"], 400);
                }
            }
            
            //Validar si voucher ya existe en otro trámite
            $voucher_validate = Tramite::join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('idEstado_tramite', '!=', 29)
            ->where('idUsuario', $tramite->idUsuario)
            ->where('voucher.idVoucher','!=',$tramite->idVoucher)
            ->where('entidad', $request->entidad)
            ->where('nro_operacion', $request->nro_operacion)
            ->where('fecha_operacion', $request->fecha_operacion)
            ->first();
            if ($voucher_validate) {
                DB::rollback();
                return response()->json(['status' => '400', 'message' => "El voucher ya fue registrado en otro trámite"], 400);
            }

            $tramite->update();
            $voucher->update();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estados=$this->setHistorialEstado($tramite->idTramite,4,2,$idUsuario);
            $historial_estados->save();

            // TRÁMITES POR USUARIO
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.created_at as fecha', 'tramite.exonerado_archivo', 'tramite.nro_tramite', 'tramite.nro_matricula',
            'tramite.comentario as comentario_tramite','tramite.sede','tramite.idEstado_tramite','tramite_detalle.idMotivo_certificado',
            'unidad.descripcion as unidad', 'dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.idTipo_tramite_unidad', 'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo','tipo_tramite_unidad.costo_exonerado',
            'tipo_tramite.descripcion as tipo_tramite', 'tipo_tramite.idTipo_tramite',
            'usuario.nro_documento','usuario.correo', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),
            'voucher.archivo as voucher', 'voucher.nro_operacion', 'voucher.entidad', 'voucher.fecha_operacion', 'voucher.comentario as comentario_voucher','voucher.des_estado_voucher')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idTramite',$id)
            ->first();

            $tramite->fut="fut/".$tramite->uuid;

            //Requisitos
            $tramite->requisitos=Tramite_Requisito::join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
            ->where('tramite_requisito.idTramite',$tramite->idTramite)
            ->get();

            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            
            // Obtenemos el motivo certificado(en caso lo tengan) de cada trámite 
            if ($tramite->idTipo_tramite==1) {
                $motivo=Motivo_Certificado::Where('idMotivo_certificado',$tramite->idMotivo_certificado)->first();
                $tramite->motivo=$motivo->nombre;
            }
            
            DB::commit();
            return response()->json($tramite, 200);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e], 400);
        }
    }

    public function UpdateFilesRequisitos(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];

            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.created_at as fecha', 'tramite.exonerado_archivo', 'tramite.nro_tramite', 'tramite.nro_matricula',
            'tramite.comentario as comentario_tramite','tramite.sede','tramite.idEstado_tramite','tramite_detalle.idMotivo_certificado',
            'unidad.descripcion as unidad', 'dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.idTipo_tramite_unidad','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo','tipo_tramite_unidad.costo_exonerado',
            'tipo_tramite.descripcion as tipo_tramite', 'tipo_tramite.idTipo_tramite',
            'usuario.nro_documento','usuario.correo', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),
            'voucher.archivo as voucher', 'voucher.nro_operacion', 'voucher.entidad', 'voucher.fecha_operacion', 'voucher.comentario as comentario_voucher',
            'voucher.des_estado_voucher','tramite.uuid','tipo_tramite.filename')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idTramite',$id)
            ->first();  
            
            // REGISTRAMOS LOS REQUISITOS DEL TRÁMITE REGISTRADO
            if($request->hasFile("files")){
                foreach ($request->file("files") as $key => $file) {
                    $requisito=json_decode($request->requisitos[$key],true);
                    $tramite_requisito=Tramite_Requisito::Where('idTramite',$requisito["idTramite"])
                    ->where('idRequisito',$requisito['idRequisito'])->first();
                    $tramite_requisito->idUsuario_aprobador=null;
                    if ($requisito['des_estado_requisito']=="RECHAZADO" && $file->getClientOriginalName()!=="vacio.kj") {
                        $tramite_requisito->comentario=null;
                        $tramite_requisito->des_estado_requisito="PENDIENTE";
                    }else {
                        $tramite_requisito->des_estado_requisito=$requisito['des_estado_requisito'];
                    }
                    $nombre = $tramite->nro_documento.".".$file->guessExtension();
                    if ($tramite->idTipo_tramite==2) {
                        $nombreBD = "/storage"."/".$tramite->filename."/".$tramite->tramite."/".$requisito["nombre"]."/".$nombre;
                    }else {
                        $nombreBD = "/storage"."/".$tramite->filename."/".$requisito["nombre"]."/".$nombre;
                    }

                    if ($file->getClientOriginalName()!=="vacio.kj") {
                        if($file->guessExtension()==$requisito["extension"]){
                            if ($tramite->idTipo_tramite==2) {
                                $file->storeAs("/public"."/".$tramite->filename."/".$tramite->tramite."/".$requisito["nombre"], $nombre);
                            }else {
                                $file->storeAs("/public"."/".$tramite->filename."/".$requisito["nombre"], $nombre);
                            }
                            $tramite_requisito->archivo = $nombreBD;
                        }else {
                            DB::rollback();
                            return response()->json(['status' => '400', 'message' => "Subir ".$requisito["nombre"]." en ".$requisito["extension"]], 400);
                        }
                    }
                    $tramite_requisito -> save();
                }

                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                if ($tramite->idTipo_tramite==2) {
                    // obetener historial
                    $ultimo_historial=Historial_Estado::where('idTramite',$tramite->idTramite)->where('estado',1)->orderBy('idHistorial_estado','desc')->first();
                    if ($tramite->idEstado_tramite==30) {
                        if ($ultimo_historial->idEstado_actual==18) {
                            // flujo regular
                            $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 31, $idUsuario);
                            $historial_estado->save();

                            $tramite-> idEstado_tramite=31;
                        }elseif ($ultimo_historial->idEstado_actual==22||$ultimo_historial->idEstado_actual==40){
                            // la facultad observa un documento a la escuela
                            $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 34, $idUsuario);
                            $historial_estado->save();

                            $historial_estado = $this->setHistorialEstado($tramite->idTramite, 34, 20, $idUsuario);
                            $historial_estado->save();

                            $tramite-> idEstado_tramite=20;
                        }elseif ($ultimo_historial->idEstado_actual==9) {
                            // Rechazados por ura
                            $rechazados_facultad=Tramite_Requisito::join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
                            ->where('tramite_requisito.idTramite',$tramite->idTramite)->where('tramite_requisito.des_estado_requisito','RECHAZADO')
                            ->where('requisito.responsable',8)->first();
                            // SI ES QUE RECHAZÓ A ESCUELA Y FACULTAD, UNA VEZ QUE LA ESCUELA SUBSANA, LO PASA A FACULTAD, SINO LO PASA A URA
                            if($rechazados_facultad){
                                // la facultad observa un documento a la escuela
                                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 34, $idUsuario);
                                $historial_estado->save();
                                
                                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 34, 32, $idUsuario);
                                $historial_estado->save();
                                $tramite-> idEstado_tramite=32;
                            }else {
                                // la facultad observa un documento a la escuela
                                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 35, $idUsuario);
                                $historial_estado->save();

                                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 35, 7, $idUsuario);
                                $historial_estado->save();
                                $tramite-> idEstado_tramite=7;
                            }
                        }
                    }elseif ($tramite->idEstado_tramite==32) {
                        if ($ultimo_historial->idEstado_actual==21) {
                            // flujo regular
                            $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 33, $idUsuario);
                            $historial_estado->save();
                            $tramite-> idEstado_tramite=33;
                        }elseif ($ultimo_historial->idEstado_actual==9||$ultimo_historial->idEstado_actual==34) {
                            // la facultad observa un documento a la escuela
                            $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 35, $idUsuario);
                            $historial_estado->save();

                            $historial_estado = $this->setHistorialEstado($tramite->idTramite, 35, 7, $idUsuario);
                            $historial_estado->save();
                            $tramite-> idEstado_tramite=7;
                        } 
                    } else {
                        // FACULTAD O URA RECHAZA DOCUMENTOS DEL ALUMNO
                        $rechazados_escuela=Tramite_Requisito::join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
                        ->where('tramite_requisito.idTramite',$tramite->idTramite)->where('tramite_requisito.des_estado_requisito','RECHAZADO')
                        ->where('requisito.responsable',5)->first();
                        // SI ES QUE RECHAZÓ A ALUMNO Y A ESCUELA, UNA VEZ QUE EL ALUMNO SUBSANA, LO PASA A ESCUELA, SINO LO PASA A FACULTAD
                        if($rechazados_escuela){
                            // la facultad observa un documento a la escuela
                            $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 30, $idUsuario);
                            $historial_estado->save();
                            $tramite-> idEstado_tramite=30;
                        }else {
                            // Rechazados por ura
                            $rechazados_facultad=Tramite_Requisito::join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
                            ->where('tramite_requisito.idTramite',$tramite->idTramite)->where('tramite_requisito.des_estado_requisito','RECHAZADO')
                            ->where('requisito.responsable',8)->first();
                            // SI ES QUE RECHAZÓ A ESCUELA Y FACULTAD, UNA VEZ QUE LA ESCUELA SUBSANA, LO PASA A FACULTAD, SINO LO PASA A URA
                            if($rechazados_facultad){
                                // la facultad observa un documento a la escuela
                                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 32, $idUsuario);
                                $historial_estado->save();
                                $tramite-> idEstado_tramite=32;
                            } else {
                                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, $ultimo_historial->idEstado_actual, $idUsuario);
                                $historial_estado->save();
                                
                                $tramite-> idEstado_tramite=$ultimo_historial->idEstado_actual;
                            }
                        }
                        
                    }
                } else {
                    $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 7, $idUsuario);
                    $historial_estado->save();
                    $tramite-> idEstado_tramite=7;
                }
                
                $tramite-> save();   
            }

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
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function notificacionUpdate(Request $request)
    {
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $uraa=User::find($idUsuario);
            $tramite=Tramite::find($request->idTramite);
            // $tramite->fut="fut/".$tramite->uuid;
            // $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            // 'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            // ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            // ->where('idTramite',$tramite->idTramite)
            // ->get();
            $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
            $tramite_detalle->mensaje_observacion=trim($request->body);
            $tramite_detalle->update();
            //Envío de correo
            $tipo_tramite_unidad=Tipo_tramite_Unidad::Find($tramite->idTipo_tramite_unidad);
            $tipo_tramite=Tipo_Tramite::Find($tipo_tramite_unidad->idTipo_tramite);
            $usuario=User::find($tramite->idUsuario);
            if ($tipo_tramite->idTipo_tramite==1) {
                $decano=User::where('idTipo_usuario',6)->where('idDependencia',$tramite->idDependencia)->where('estado',true)->first();
                $secretariaFacultad=User::where('idTipo_usuario',8)->where('idDependencia',$tramite->idDependencia)->where('estado',true)->first();
                $secretariaEscuela=User::where('idTipo_usuario',5)->where('idDependencia',$tramite->idDependencia_detalle)->where('estado',true)->first();
                if ($request->cc) {
                    $copias=[$secretariaEscuela->correo,$usuario->correo,$secretariaFacultad->correo,$uraa->correo,trim($request->cc)];
                }else {
                    $copias=[$secretariaEscuela->correo,$usuario->correo,$secretariaFacultad->correo,$uraa->correo];
                }
                // dispatch(new NotificacionCertificadoJob($decano->correo,$copias,$usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad,trim($request->body)));
            }elseif ($tipo_tramite->idTipo_tramite==2) {
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                $historial_estados->idEstado_nuevo=50;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
                $tramite->idEstado_tramite=50;
                $tramite->save();
                //Correo
                $secretariaEscuela=User::where('idTipo_usuario',5)->where('idDependencia',$tramite->idDependencia_detalle)->where('estado',true)->first();
                // dispatch(new NotificacionCarpetaJob($usuario,$secretariaEscuela,$tramite,$tipo_tramite,$tipo_tramite_unidad,trim($request->body)));
            }
            DB::commit();
            return response()->json(true, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
    
    public function anularTramite(Request $request)
    {   
        

        // return $request->all();
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            // TRÁMITE A ANULAR
            $tramite=Tramite::find($request->idTramite);
            //REGISTRANDO EL ESTADO ANULADO
            $historial_estados=new Historial_Estado;
            $historial_estados->idTramite=$tramite->idTramite;
            $historial_estados->idUsuario=$idUsuario;
            $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
            $historial_estados->idEstado_nuevo=29;
            $historial_estados->fecha=date('Y-m-d h:i:s');
            $historial_estados->save();

            //Anulando tramite desde el estado mismo
            $tramite->idEstado_tramite=$historial_estados->idEstado_nuevo;
            $tramite->update();


            //EQUAL - Anular tramite _paralelo
            $tramite_2=Tramite::where('nro_tramite',$tramite->nro_tramite)->where('idTramite','<>',$tramite->idTramite)->where('idUsuario',$tramite->idUsuario)->first();
            if($tramite_2)
            {
                $tramite_2->idEstado_tramite = 29;
                $tramite_2->update();

                //EQUAL - Registrar el estado anulado _paralelo
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite = $tramite_2->idTramite;
                $historial_estados->idUsuario = $idUsuario;
                $historial_estados->idEstado_actual = $tramite_2->idEstado_tramite;
                $historial_estados->idEstado_nuevo = 29;
                $historial_estados->fecha = date('Y-m-d h:i:s');
                $historial_estados->save();
            }
           
            //DATOS para envío del correo
            $idusuario=Tramite::select('tramite.idUsuario')
            ->where('tramite.idTramite',$request->idTramite)
            ->first();
            $usuario=User::find($idusuario->idUsuario);

            $tramite=Tramite::find($request->idTramite);

            $tipo_tramite_unidad=Tipo_Tramite_Unidad::find($tramite->idTipo_tramite_unidad);

            $tipo_tramite=Tipo_tramite::find($tipo_tramite_unidad->idTipo_tramite);
            
           if (!$request->body) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => 'Registre motivo'], 400);
           }
            dispatch(new AnularTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad,$request->body, $request->cc));
            DB::commit();
            return response()->json(true,200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function aprobarRequisito(Request $request)
    {
        // return $request->all();
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            //flag de requisitos rechazados o aprobados
            $flag=true;
            $flag2=true;
            $flagAlumno=false;
            $flagEscuela=false;
            $flagFacultad=false;
            // $flagEscuela=false;
            
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite',
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo', 'tramite.idTipo_tramite_unidad',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo', 'tipo_tramite_unidad.idTipo_tramite',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'voucher.archivo as voucher','tramite_detalle.certificado_final')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('tramite_detalle','tramite.idTramite_detalle','tramite_detalle.idTramite_detalle')
            ->find($request->idTramite);
            
            // DATOS PARA EL CORREO 
            $usuario=User::find($tramite->idUsuario);
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::find($tramite->idTipo_tramite_unidad);
            $tipo_tramite=Tipo_Tramite::find($tramite->idTipo_tramite);
            //Editamos los cada uno de los requisitos que llegan junto al trámite en el request
            foreach ($request->requisitos as $key => $requisito) {
                $tramite_requisito=Tramite_Requisito::Where('idTramite',$request->idTramite)
                ->where('idRequisito',$requisito['idRequisito'])->first();
                $tramite_requisito->idUsuario_aprobador=$idUsuario;
                $tramite_requisito->validado=$requisito['validado'];
                $tramite_requisito->des_estado_requisito=$requisito['des_estado_requisito'];
                $tramite_requisito->comentario=$requisito['comentario'];
                $tramite_requisito->save();
                // VERIFICANDO SI SE APRUEBA O RECHAZA POR PARTE DE LA ESCUELA O FACULTAD
                if ($tramite->idEstado_tramite==17) {
                    // Verificando que el estado del responsable sea el alumno(4)
                    if ($requisito['des_estado_requisito']=="RECHAZADO" && $requisito['responsable']==4 ) {
                        $flag=false;
                    }
                    if ($requisito['des_estado_requisito']=="PENDIENTE" && $requisito['responsable']==4) {
                        $flag2=false;
                    }
                }elseif($tramite->idEstado_tramite==20){
                    // Verificando que el estado del responsable sea el alumno(4) o escuela(5)
                    if ($requisito['des_estado_requisito']=="RECHAZADO" && ($requisito['responsable']==4 || $requisito['responsable']==5)) {
                        $flag=false;
                        if ($requisito['responsable']==4) {
                            $flagAlumno=true;
                        } else if ($requisito['responsable']==5) {
                            $flagEscuela = true;
                        }
                    }
                    if ($requisito['des_estado_requisito']=="PENDIENTE" && ($requisito['responsable']==4 || $requisito['responsable']==5)) {
                        
                        $flag2=false;
                    }
                    
                }else {
                    if ($requisito['des_estado_requisito']=="RECHAZADO" && ($requisito['responsable']==4 || $requisito['responsable']==5|| $requisito['responsable']==8)) {
                        $flag=false;
                        if ($requisito['responsable']==4) {
                            $flagAlumno=true;
                        }elseif($requisito['responsable']==5){
                            $flagEscuela=true;
                        }elseif($requisito['responsable']==8) {
                            $flagFacultad=true;
                        }
                    }
                    if ($requisito['des_estado_requisito']=="PENDIENTE" && ($requisito['responsable']==4 || $requisito['responsable']==5|| $requisito['responsable']==8)) {
                        $flag2=false;
                    }
                }
            }
            // var_dump($flag); //false = hay rechazados
            // var_dump($flag2); //false = hay pendientes
            // var_dump($flagAlumno); // true = sí hay rechazado de alumno
            // var_dump($flagEscuela); // true = sí hay rechazado de escuela
            // var_dump($flagFacultad); // true = sí hay rechazado de facultad
            // return "hola";
            // SI NO HAY PENDIENTES 
            if ($flag2) {
                //Verificamos si todos los requisitos fueron aprobados($flag=true) o no($flag=false) 
                if ($flag==FALSE) {
                    if ($tramite->idTipo_tramite==2) {
                        // VERIFICANDO SI EL RECHAZO SE ESTÁ HACIENDO DESDE ESCUELA O FACULTAD MEDIANTE EL ESTADO DEL TRÁMITE
                        if ($tramite->idEstado_tramite!=9) {
                            //REGISTRAMOS EL ESTADO DEL TRÁMITE
                            $historial_estados=new Historial_Estado;
                            $historial_estados->idTramite=$tramite->idTramite;
                            $historial_estados->idUsuario=$idUsuario;
                            $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                            $historial_estados->idEstado_nuevo=9;
                            $historial_estados->fecha=date('Y-m-d h:i:s');
                            
                            // SI HAY REQUISITO DE ALUMNO 
                            if ($flagAlumno) {
                                // ENVIAR CORREO AL ALUMNO
                                dispatch(new ObservacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
                            }elseif ($flagEscuela) {
                                // SI HAY REQUISITO DE LA ESCUELA
                                // PASAMOS A ESTADO DE ADJUNTAR DE LA ESCUELA
                                $historial_estados=new Historial_Estado;
                                $historial_estados->idTramite=$tramite->idTramite;
                                $historial_estados->idUsuario=$idUsuario;
                                $historial_estados->idEstado_actual=9;
                                $historial_estados->idEstado_nuevo=30;
                            }elseif ($flagFacultad) {
                                // SI HAY REQUISITO DE LA FACULTAD
                                // PASAMOS A ESTADO DE ADJUNTAR DE LA FACULTAD
                                $historial_estados=new Historial_Estado;
                                $historial_estados->idTramite=$tramite->idTramite;
                                $historial_estados->idUsuario=$idUsuario;
                                $historial_estados->idEstado_actual=9;
                                $historial_estados->idEstado_nuevo=32;
                            }
                            $historial_estados->save();
                            $tramite->idEstado_tramite = $historial_estados->idEstado_nuevo;
                            $tramite->update();
                        }
                    }
                }
                
                

            }else {
                // SI HAY PENDIENTES
                if ($flag==FALSE) {
                    if ($tramite->idTipo_tramite==2) {
                        // VERIFICANDO SI EL RECHAZO SE ESTÁ HACIENDO DESDE ESCUELA O FACULTAD MEDIANTE EL ESTADO DEL TRÁMITE
                        if ($tramite->idEstado_tramite!=9) {
                            //REGISTRAMOS EL ESTADO DEL TRÁMITE
                            $historial_estados=new Historial_Estado;
                            $historial_estados->idTramite=$tramite->idTramite;
                            $historial_estados->idUsuario=$idUsuario;
                            $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                            $historial_estados->idEstado_nuevo=9;
                            $historial_estados->fecha=date('Y-m-d h:i:s');
                            
                            // SI HAY REQUISITO DE ALUMNO 
                            if ($flagAlumno) {
                                // ENVIAR CORREO AL ALUMNO
                                dispatch(new ObservacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
                            }elseif ($flagEscuela) {
                                // SI HAY REQUISITO DE LA ESCUELA
                                // PASAMOS A ESTADO DE ADJUNTAR DE LA ESCUELA
                                $historial_estados=new Historial_Estado;
                                $historial_estados->idTramite=$tramite->idTramite;
                                $historial_estados->idUsuario=$idUsuario;
                                $historial_estados->idEstado_actual=9;
                                $historial_estados->idEstado_nuevo=30;
                            }elseif ($flagFacultad) {
                                // SI HAY REQUISITO DE LA FACULTAD
                                // PASAMOS A ESTADO DE ADJUNTAR DE LA FACULTAD
                                $historial_estados=new Historial_Estado;
                                $historial_estados->idTramite=$tramite->idTramite;
                                $historial_estados->idUsuario=$idUsuario;
                                $historial_estados->idEstado_actual=9;
                                $historial_estados->idEstado_nuevo=32;
                            }
                            $historial_estados->save();
                            $tramite->idEstado_tramite = $historial_estados->idEstado_nuevo;
                            $tramite->update();
                        }
                    }
                }
            }

            $tramite->fut="fut/".$tramite->uuid;
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$request->idTramite)
            ->get();

            // dispatch(new ActualizacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
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

    public function validarVoucher($entidad, $nro_operacion, $fecha_operacion, $idUsuario)
    {
        return Tramite::join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('voucher.entidad',$entidad)
        ->where('voucher.nro_operacion',$nro_operacion)
        ->where('voucher.fecha_operacion',$fecha_operacion)
        ->where('tramite.idUsuario',$idUsuario)
        ->where('tramite.idTipo_tramite_unidad','!=',37)
        ->where('tramite.idEstado_tramite','!=',29)
        ->first();
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
