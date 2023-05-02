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

use App\Mencion;
use App\Escuela;
use App\Motivo_Certificado;
use App\PersonaSuv;
use App\PersonaSga;
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
            
            if ($request->query('search')!="") {
                if ($idTipo_usuario==1) {
                    // TRÁMITES POR USUARIO
                    $tramites=Tramite::select('tramite.nro_tramite','tramite.created_at','tramite.idTramite','tramite.idTipo_tramite_unidad','estado_tramite.idEstado_tramite',
                    DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado','tramite.idUsuario_asignado')
                    ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                    ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                    ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
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
                    DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado','tramite.idUsuario_asignado')
                    ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                    ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                    ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->where('tipo_tramite.idTipo_tramite',1)
                    ->where(function($query) use ($request)
                    {
                        $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                        ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                        ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                        ->orWhere('created_at','LIKE','%'.$request->query('search').'%')
                        ->orWhere('estado_tramite.nombre','LIKE','%'.$request->query('search').'%');
                    })
                    ->orderBy($request->query('sort'), $request->query('order'))
                    ->get();
                    $total = Tramite::join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                    ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                    ->where('tipo_tramite.idTipo_tramite',1)
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
                    DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado','tramite.idUsuario_asignado')
                    ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                    ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                    ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->Where('tramite.idUsuario',$idUsuario)
                    ->where(function($query) use ($request)
                    {
                        $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                        ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                        ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                        ->orWhere('created_at','LIKE','%'.$request->query('search').'%')
                        ->orWhere('estado_tramite.nombre','LIKE','%'.$request->query('search').'%');
                    })
                    ->orderBy($request->query('sort'), $request->query('order'))
                    ->get();

                    $total = Tramite::join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                    ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                    ->Where('tramite.idUsuario',$idUsuario)
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
            }else {
                if ($idTipo_usuario==1) {
                    // TRÁMITES POR USUARIO
                    $tramites=Tramite::select('tramite.nro_tramite','tramite.created_at','tramite.idTramite','tramite.idTipo_tramite_unidad','estado_tramite.idEstado_tramite',
                    DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado','tramite.idUsuario_asignado')
                    ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                    ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                    ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->Where('tramite.idEstado_tramite','!=',29)
                    ->Where('tramite.idEstado_tramite','!=',15)
                    ->orderBy($request->query('sort'), $request->query('order'))
                    ->take($request->query('size'))
                    ->skip($request->query('page')*$request->query('size'))
                    ->get();
                    $total = Tramite::join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->Where('tramite.idEstado_tramite','!=',29)
                    ->Where('tramite.idEstado_tramite','!=',15)
                    ->count();
                }elseif($idTipo_usuario==13){
                    // TRÁMITES POR USUARIO
                    $tramites=Tramite::select('tramite.nro_tramite','tramite.created_at','tramite.idTramite','tramite.idTipo_tramite_unidad','estado_tramite.idEstado_tramite',
                    DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado','tramite.idUsuario_asignado')
                    ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                    ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                    ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->Where('tramite.idEstado_tramite','!=',29)
                    ->Where('tramite.idEstado_tramite','!=',15)
                    ->where('tipo_tramite.idTipo_tramite',1)
                    ->orderBy($request->query('sort'), $request->query('order'))
                    ->take($request->query('size'))
                    ->skip($request->query('page')*$request->query('size'))
                    ->get();
                    $total = Tramite::join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->Where('tramite.idEstado_tramite','!=',29)
                    ->Where('tramite.idEstado_tramite','!=',15)
                    ->count();
                }elseif($idTipo_usuario==9){
                    // TRÁMITES POR USUARIO
                    $tramites=Tramite::select('tramite.nro_tramite','tramite.created_at','tramite.idTramite','tramite.idTipo_tramite_unidad','estado_tramite.idEstado_tramite',
                    DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado','tramite.idUsuario_asignado')
                    ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                    ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                    ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->Where('tramite.idEstado_tramite','!=',29)
                    ->Where('tramite.idEstado_tramite','!=',15)
                    ->where('tipo_tramite.idTipo_tramite',2)
                    ->orderBy($request->query('sort'), $request->query('order'))
                    ->take($request->query('size'))
                    ->skip($request->query('page')*$request->query('size'))
                    ->get();
                    $total = Tramite::join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->Where('tramite.idEstado_tramite','!=',29)
                    ->Where('tramite.idEstado_tramite','!=',15)
                    ->count();
                }
                else {
                    
                    // TRÁMITES POR USUARIO
                    $tramites=Tramite::select('tramite.nro_tramite','tramite.created_at','tramite.idTramite','tramite.idTipo_tramite_unidad','estado_tramite.idEstado_tramite',
                    DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado','tramite.idUsuario_asignado')
                    ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                    ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                    ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->Where('tramite.idUsuario',$idUsuario)
                    ->orderBy($request->query('sort'), $request->query('order'))
                    ->take($request->query('size'))
                    ->skip($request->query('page')*$request->query('size'))
                    ->get();
                    $total = Tramite::join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                    ->Where('tramite.idEstado_tramite','!=',29)
                    ->Where('tramite.idEstado_tramite','!=',15)
                    ->count();
                }
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
            // $pagination=$this->Paginacion($total, $tramites, $request->query('size'), $request->query('page')+1);
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

    // public function Paginacion($total, $items, $size, $page = null)
    // {
    //     $items = $items instanceof Collection ? $items : Collection::make($items);
    //     return $response=new LengthAwarePaginator($items->forPage($page, $size), $items->count(), $size, $page, $options);

    //     return response()->json([ => '400', 'message' => $e->getMessage()], 400);
    // }

    public function GetTramitesByUser(Request $request)
    {
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            
            // TRÁMITES POR USUARIO
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','unidad.idUnidad','tipo_tramite.descripcion as tipo_tramite','tipo_tramite_unidad.idTipo_tramite_unidad',
            'tipo_tramite_unidad.descripcion as tipo_tramite_unidad','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            /*,'motivo_certificado.nombre as motivo'*/,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            ,'voucher.nro_operacion','voucher.entidad','voucher.fecha_operacion','tipo_tramite_unidad.costo','tramite.exonerado_archivo'
            ,'tipo_tramite.idTipo_tramite','tramite.comentario as comentario_tramite','voucher.comentario as comentario_voucher'
            ,'tramite_detalle.idMotivo_certificado','voucher.des_estado_voucher','tramite.sede','tramite.idEstado_tramite')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            // ->join('motivo_certificado','motivo_certificado.idMotivo_certificado','tramite_detalle.idMotivo_certificado')
            // ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idusuario',$idUsuario)
            ->get();   
            foreach ($tramites as $key => $tramite) {
                //Requisitos
                $tramite->requisitos=Tramite_Requisito::select('requisito.*','tramite_requisito.idTramite','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador',
                'tramite_requisito.validado','tramite_requisito.comentario','tramite_requisito.des_estado_requisito','tramite_requisito.estado')
                ->join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
                ->where('tramite_requisito.idTramite',$tramite->idTramite)
                ->get();
                //Datos del usuario al que pertenece el trámite
                $usuario=User::findOrFail($tramite->idUsuario)->first();
                // Obtenemos el motivo certificado(en caso lo tengan) de cada trámite 
                if ($tramite->idTipo_tramite==1) {
                    $motivo=Motivo_Certificado::Where('idMotivo_certificado',$tramite->idMotivo_certificado)->first();
                    $tramite->motivo=$motivo->nombre;
                }
                // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
                $dependenciaDetalle=null;
                if ($tramite->idUnidad==1) {
                    // $personaSuv=PersonaSuv::Where('per_dni',$usuario->nro_documento)->first();
                    // if ($personaSuv) {
                        $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
                    // }else {
                    //     $personaSga=PersonaSga::Where('per_dni',$usuario->nro_documento)->first();
                    //     if ($personaSga) {
                    //         $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
                    //     }
                    // }
                }else if ($tramite->idUnidad==2) {
                    
                }else if ($tramite->idUnidad==3) {
                    
                }else{
                    $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
                }
                $tramite->escuela=$dependenciaDetalle->nombre;
            }
            return response()->json($tramites, 200);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e], 400);
        }  
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
            
            // VERIFICAR QUE LA PERSONA QUE REGISTRA UN TRÁMITE DE GRADO SEA EGRESADO
            if ($tipo_tramite_unidad->idTipo_tramite==2) {
                // VALIDACION DE REPETICIÓN DE TRÁMITES
                $tramite_validate=Tramite::where('idUsuario',$idUsuario)->where('idTipo_tramite_unidad',$request->idTipo_tramite_unidad)
                ->where('idEstado_tramite','!=','29')
                ->first();
                if ($tramite_validate) {
                    return response()->json(['status' => '400', 'message' => 'Ya tiene un trámite registrado para '.$tipo_tramite_unidad->descripcion], 400);
                }

                // Verificando que sea alumno de universidad no licenciada o amnistiados
                $alumnoSUV=PersonaSuv::join('matriculas.alumno','matriculas.alumno.idpersona','persona.idpersona')
                // ->where('idmodalidadingreso',8)
                ->where(function($query)
                {
                    $query->where('idmodalidadingreso',8)
                    ->orWhere('idmodalidadingreso',9);
                })
                ->Where('per_dni',$dni)->first();
                if (!$alumnoSUV) {
                    if ($request->idUnidad==1) {
                        $alumnoSUV=PersonaSuv::join('matriculas.alumno','matriculas.alumno.idpersona','persona.idpersona')
                        ->where('alu_estado',6)->Where('per_dni',$dni)->first();
                        if (!$alumnoSUV) {
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
                    }elseif ($request->idUnidad==2) {
                        
                    }elseif ($request->idUnidad==3) {
                        
                    }else {
                       
                    }
                }
            }
            if ($tipo_tramite_unidad->idTipo_tramite==3) {
                // VALIDACION DE REPETICIÓN DE TRÁMITES
                if ($request->idTipo_tramite_unidad==17||$request->idTipo_tramite_unidad==19||$request->idTipo_tramite_unidad==21||$request->idTipo_tramite_unidad==23) {
                    $tramite_validate=Tramite::where('idUsuario',$idUsuario)->where('idTipo_tramite_unidad',$request->idTipo_tramite_unidad)
                    ->where('idEstado_tramite','!=','29')
                    ->first();
                    if ($tramite_validate) {
                        return response()->json(['status' => '400', 'message' => 'Ya tiene un trámite registrado para '.$tipo_tramite_unidad->descripcion], 400);
                    }
                }
            }
            $tramiteValidate=Tramite::join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->Where('voucher.entidad',trim($request->entidad))->where('voucher.nro_operacion',trim($request->nro_operacion))
            ->where('voucher.fecha_operacion',Str::substr(trim($request->fecha_operacion),0, 10))
            ->where('tramite.idUsuario',$idUsuario)
            ->where('tramite.idEstado_tramite','!=',29)
            ->first();
            if($tramiteValidate){
                return response()->json(['status' => '400', 'message' => 'El voucher ya se encuentra registrado!!'], 400);
            }else{
                $tramite=new Tramite;

                //AÑADIMOS EL NÚMERO DE TRÁMITE
                $inicio=date('Y-m-d')." 00:00:00";
                $fin=date('Y-m-d')." 23:59:59";
                $last_tramite=Tramite::whereBetween('created_at', [$inicio , $fin])->where('idTipo_tramite_unidad','!=',37)
                ->orderBy("created_at","DESC")->first();
                
                if ($last_tramite) {
                    $correlativo=(int)(substr($last_tramite->nro_tramite,0,4));
                    $correlativo++;
                    if ($correlativo<10) {
                        $tramite -> nro_tramite="000".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                    } elseif($correlativo<100){
                        $tramite -> nro_tramite="00".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                    }elseif ($correlativo<1000) {
                        $tramite -> nro_tramite="0".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                    }
                    else{
                        $tramite -> nro_tramite=$correlativo.date('d').date('m').substr(date('Y'),2,3);
                    }
                }else{
                    $tramite -> nro_tramite="0001".date('d').date('m').substr(date('Y'),2,3);
                }
                

                // REGISTRAMOS LE VOUCHER
                $voucher=new Voucher;
                $voucher->entidad=trim($request->entidad);
                $voucher->nro_operacion=trim($request->nro_operacion);
                $voucher->fecha_operacion=trim($request->fecha_operacion);
                // $voucher->des_estado_voucher=trim($request->des_estado_voucher);

                
                if ($request->hasFile("archivo") && $request->hasFile("archivo_exonerado")) {
                    // GUARDAMOS EL ARCHIVO DEL VOUCHER
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
                    // GUARDAMOS EL ARCHIVO DEL EXONERADO
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
                }else {
                    if($request->hasFile("archivo")){
                        // return "ingresé al voucher";
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
                    }else {
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
                        }else {
                            return response()->json(['status' => '400', 'message' =>"Datos incompletos"], 400);
                        }
                    }
                }
                $voucher->comentario=null;
                $voucher->save();

                // REGISTRAMOS EL DETALLE DEL TRÁMITE REGISTRADO
                $tipo_tramite = Tipo_Tramite::select('tipo_tramite.idTipo_tramite','tipo_tramite.descripcion','tipo_tramite.filename')->join('tipo_tramite_unidad', 'tipo_tramite_unidad.idTipo_tramite', 'tipo_tramite.idTipo_tramite')
                ->where('tipo_tramite_unidad.idTipo_tramite_unidad', $request->idTipo_tramite_unidad)->first();
                $tramite_detalle=new Tramite_Detalle();

                switch ($tipo_tramite->idTipo_tramite) {
                    case 1:
                        $tramite_detalle->idCronograma_carpeta = null;
                        $tramite_detalle->idModalidad_carpeta=null;
                        $tramite_detalle->idMotivo_certificado=2;//trim($request->idMotivo_certificado);
                        break;
                    case 2:
                        if ($request->idCronograma_carpeta!=null) {
                            $tramite_detalle->idCronograma_carpeta = trim($request->idCronograma_carpeta);
                        }else {
                            DB::rollback();
                            return response()->json(['status' => '400', 'message' => "Seleccionar una fecha de colación"], 400);
                        }
                        // $tramite_detalle->idModalidad_titulo_carpeta=1;//trim($request->idModalidad_titulo_carpeta);//por defecto null por ahora
                        $tramite_detalle->idMotivo_certificado=1;
                        break;
                    case 3:
                        $tramite_detalle->idCronograma_carpeta = null;
                        $tramite_detalle->idModalidad_carpeta=null;
                        $tramite_detalle->idMotivo_certificado=null;
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
                $tramite -> idDependencia_detalle=trim($request->idDependencia_detalle);
                $tramite -> nro_matricula=trim($request->nro_matricula);
                $tramite -> comentario=trim($request->comentario);
                $tramite -> sede=trim($request->sede);
                $tramite->idUsuario_asignado=null;
                $tramite -> idEstado_tramite=2;
                
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
                }else{
                    return response()->json(['status' => '400', 'message' =>"¡Adjuntar firma!"], 400);
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
                        if ($tipo_tramite->idTipo_tramite==2) {
                            $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"]."/".$nombre;
                        }else {
                            $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$requisito["nombre"]."/".$nombre;
                        }
                        // $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$requisito["nombre"]."/".$nombre;
                        if ($file->getClientOriginalName()!=="vacio.kj") {
                            if($file->guessExtension()==$requisito["extension"]){
                                if ($tipo_tramite->idTipo_tramite==2) {
                                    $file->storeAs("/public"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"], $nombre);
                                }else {
                                    $file->storeAs("/public"."/".$tipo_tramite->filename."/".$requisito["nombre"], $nombre);
                                    // $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$requisito["nombre"]."/".$nombre;
                                }
                            //   $file->storeAs("/public"."/".$tipo_tramite->filename."/".$requisito["nombre"], $nombre);
                              $tramite_requisito->archivo = $nombreBD;
                            }else {
                                DB::rollback();
                                // return response()->json(['status' => '400', 'message' => "Subir archivo pdf"], 400);
                                return response()->json(['status' => '400', 'message' => "Subir ".$requisito["nombre"]." en ".$requisito["extension"]], 400);
                            }
                        }
                        $tramite_requisito -> save();
                    }
                }

                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=null;
                $historial_estados->idEstado_nuevo=1;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=1;
                $historial_estados->idEstado_nuevo=2;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();

                //Verificar que el código no esté registrado
                // $code_validate=Tramite::where('nro_tramite',$tramite->nro_tramite)->first();
                // if ($code_validate) {
                //     return response()->json(['status' => '400', 'message' => "Volver a intentar"], 400);
                // }
                //---------------------------------------------------------------------------------------------
                DB::commit();
                // dispatch(new RegistroTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
                return response()->json(['status' => '200', 'usuario' => 'Trámite registrado correctamente!!'], 200);
            }
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
    
    public function updateTramiteRequisitos(Request $request){
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
            
            if ($request->idTipo_tramite==1) {
                $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'motivo_certificado.nombre as motivo','tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad','tramite.idEstado_tramite','tramite.idTipo_tramite_unidad','tipo_tramite.idTipo_tramite')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('motivo_certificado','motivo_certificado.idMotivo_certificado','tramite_detalle.idMotivo_certificado')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->find($request->idTramite);
            }else {
                $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                /*,'motivo_certificado.nombre as motivo'*/,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad','tramite.idEstado_tramite','tramite.idTipo_tramite_unidad','tipo_tramite.idTipo_tramite'
                ,'tramite_detalle.certificado_final')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                // ->join('motivo_certificado','motivo_certificado.idMotivo_certificado','tramite_detalle.idMotivo_certificado')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->find($request->idTramite);
            }

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
                            $historial_estados->idEstado_actual=9;
                            $historial_estados->idEstado_nuevo=30;
                        }
                    }
                    $historial_estados->fecha=date('Y-m-d h:i:s');
                    $historial_estados->save();

                    $tramite->idEstado_tramite = $historial_estados->idEstado_nuevo;
                    $tramite->update();
                }
            }
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$request->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            // $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
                $tramite->escuela=$dependenciaDetalle->nombre;
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
                $tramite->mencion=$dependenciaDetalle->nombre;
            }
            // $tramite->escuela=$dependenciaDetalle->nombre;
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
            
            
            $tramite=Tramite::where('idTramite',$id)->first();
            // Editamos el voucher
            $voucher=Voucher::where('idVoucher',$tramite->idVoucher)->first();
            $voucher->des_estado_voucher='PENDIENTE';
            $voucher->idUsuario_aprobador=null;
            $voucher->comentario=null;
            // return $request->all();
            if($request->hasFile("archivo")){
                $file=$request->file("archivo");
                // return $file->getClientOriginalName();
                $nombre = $tramite->nro_tramite.'.'.$file->guessExtension();
                $nombreBD = "/storage/vouchers_tramites/".$nombre;
                if($file->guessExtension()=="pdf"){
                    // Storage::delete($nombreBD);
                    $file->storeAs('public/vouchers_tramites', $nombre);
                    $voucher->archivo = $nombreBD;
                    // return $nombre;
                 }
            }
            $voucher->update();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estados=new Historial_Estado;
            $historial_estados->idTramite=$tramite->idTramite;
            $historial_estados->idUsuario=$idUsuario;
            $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
            $historial_estados->idEstado_nuevo=2;
            $historial_estados->fecha=date('Y-m-d h:i:s');
            $historial_estados->save();


            // TRÁMITES POR USUARIO
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','unidad.idUnidad','tipo_tramite.descripcion as tipo_tramite','tipo_tramite_unidad.idTipo_tramite_unidad','tipo_tramite_unidad.descripcion as tipo_tramite_unidad','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            /*,'motivo_certificado.nombre as motivo'*/,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            ,'voucher.nro_operacion','voucher.entidad','voucher.fecha_operacion','tipo_tramite_unidad.costo','tramite.exonerado_archivo'
            ,'tipo_tramite.idTipo_tramite','tramite.comentario as comentario_tramite','voucher.comentario as comentario_voucher'
            ,'tramite_detalle.idMotivo_certificado','voucher.des_estado_voucher','tramite.sede')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            // ->join('motivo_certificado','motivo_certificado.idMotivo_certificado','tramite_detalle.idMotivo_certificado')
            // ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idusuario',$idUsuario)
            ->where('tramite.idTramite',$id)
            ->first();   

            //Requisitos
            $tramite->requisitos=Tramite_Requisito::select('*')
            ->join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
            ->where('tramite_requisito.idTramite',$tramite->idTramite)
            ->get();
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // Obtenemos el motivo certificado(en caso lo tengan) de cada trámite 
            if ($tramite->idTipo_tramite==1) {
                $motivo=Motivo_Certificado::Where('idMotivo_certificado',$tramite->idMotivo_certificado)->first();
                $tramite->motivo=$motivo->nombre;
            }
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $personaSuv=PersonaSuv::Where('per_dni',$usuario->nro_documento)->first();
                if ($personaSuv) {
                    $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
                }else {
                    $personaSga=PersonaSga::Where('per_dni',$usuario->nro_documento)->first();
                    if ($personaSga) {
                        $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
                    }
                }
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
            return response()->json(['status' => '400', 'message' => $e], 400);
        }
    }

    public function UpdateFilesRequisitos(Request $request, $id){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            // TRÁMITES A EDITAR LOS REQUISITOS
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','unidad.idUnidad','tipo_tramite.filename as tipo_tramite','tipo_tramite_unidad.idTipo_tramite_unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            ,'voucher.nro_operacion','voucher.entidad','voucher.fecha_operacion','tipo_tramite_unidad.costo','tramite.exonerado_archivo'
            ,'tipo_tramite.idTipo_tramite','tramite.comentario as comentario_tramite','voucher.comentario as comentario_voucher'
            ,'tramite_detalle.idMotivo_certificado','voucher.des_estado_voucher','tramite.sede','tramite.idEstado_tramite')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
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
                        $nombreBD = "/storage"."/".$tramite->tipo_tramite."/".$tramite->tramite."/".$requisito["nombre"]."/".$nombre;
                    }else {
                        $nombreBD = "/storage"."/".$tramite->tipo_tramite."/".$requisito["nombre"]."/".$nombre;
                    }
                    // $nombreBD = "/storage"."/".$tramite->tipo_tramite."/".$requisito["nombre"]."/".$nombre;

                    if ($file->getClientOriginalName()!=="vacio.kj") {
                        if($file->guessExtension()==$requisito["extension"]){
                            if ($tramite->idTipo_tramite==2) {
                                $file->storeAs("/public"."/".$tramite->tipo_tramite."/".$tramite->tramite."/".$requisito["nombre"], $nombre);
                            }else {
                                // $nombreBD = "/storage"."/".$tramite->tipo_tramite."/".$requisito["nombre"]."/".$nombre;
                                $file->storeAs("/public"."/".$tramite->tipo_tramite."/".$requisito["nombre"], $nombre);
                            }
                            $tramite_requisito->archivo = $nombreBD;
                        }else {
                            DB::rollback();
                            // return response()->json(['status' => '400', 'message' => "Subir archivo pdf"], 400);
                            return response()->json(['status' => '400', 'message' => "Subir ".$requisito["nombre"]." en ".$requisito["extension"]], 400);
                        }
                    }
                    $tramite_requisito -> save();
                }

                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                if ($tramite->idTipo_tramite==2) {
                    // obetener historial
                    $ultimo_historial=Historial_Estado::where('idTramite',$tramite->idTramite)->where('estado',1)->orderBy('idHistorial_estado','desc')->first();
                    if ($tramite->idEstado_tramite==30) {
                        if ($ultimo_historial->idEstado_actual==18) {
                            // flujo regular
                            $tramite-> idEstado_tramite=31;
                            $historial_estados->idEstado_nuevo=31;
                        }elseif ($ultimo_historial->idEstado_actual==22||$ultimo_historial->idEstado_actual==40){
                            // la facultad observa un documento a la escuela
                            $historial_estados->idEstado_nuevo=34;
                            $historial_estados->fecha=date('Y-m-d h:i:s');
                            $historial_estados->save();

                            $historial_estados=new Historial_Estado;
                            $historial_estados->idTramite=$tramite->idTramite;
                            $historial_estados->idUsuario=$idUsuario;
                            $historial_estados->idEstado_actual=34;
                            $historial_estados->idEstado_nuevo=20;
                            $tramite-> idEstado_tramite=20;
                        }elseif ($ultimo_historial->idEstado_actual==9) {
                            // Rechazados por ura
                            $rechazados_facultad=Tramite_Requisito::join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
                            ->where('tramite_requisito.idTramite',$tramite->idTramite)->where('tramite_requisito.des_estado_requisito','RECHAZADO')
                            ->where('requisito.responsable',8)->first();
                            // SI ES QUE RECHAZÓ A ESCUELA Y FACULTAD, UNA VEZ QUE LA ESCUELA SUBSANA, LO PASA A FACULTAD, SINO LO PASA A URA
                            if($rechazados_facultad){
                                // la facultad observa un documento a la escuela
                                $historial_estados->idEstado_nuevo=34;
                                $historial_estados->fecha=date('Y-m-d h:i:s');
                                $historial_estados->save();

                                $historial_estados=new Historial_Estado;
                                $historial_estados->idTramite=$tramite->idTramite;
                                $historial_estados->idUsuario=$idUsuario;
                                $historial_estados->idEstado_actual=34;
                                $historial_estados->idEstado_nuevo=32;
                                $tramite-> idEstado_tramite=32;
                            }else {
                                // la facultad observa un documento a la escuela
                                $historial_estados->idEstado_nuevo=35;
                                $historial_estados->fecha=date('Y-m-d h:i:s');
                                $historial_estados->save();

                                $historial_estados=new Historial_Estado;
                                $historial_estados->idTramite=$tramite->idTramite;
                                $historial_estados->idUsuario=$idUsuario;
                                $historial_estados->idEstado_actual=35;
                                $historial_estados->idEstado_nuevo=7;
                                $tramite-> idEstado_tramite=7;
                            }
                        }
                    }elseif ($tramite->idEstado_tramite==32) {
                        if ($ultimo_historial->idEstado_actual==21) {
                            // flujo regular
                            $tramite-> idEstado_tramite=33;
                            $historial_estados->idEstado_nuevo=33;
                        }elseif ($ultimo_historial->idEstado_actual==9||$ultimo_historial->idEstado_actual==34) {
                            // la facultad observa un documento a la escuela
                            $historial_estados->idEstado_nuevo=35;
                            $historial_estados->fecha=date('Y-m-d h:i:s');
                            $historial_estados->save();

                            $historial_estados=new Historial_Estado;
                            $historial_estados->idTramite=$tramite->idTramite;
                            $historial_estados->idUsuario=$idUsuario;
                            $historial_estados->idEstado_actual=35;
                            $historial_estados->idEstado_nuevo=7;
                            $tramite-> idEstado_tramite=7;
                        } 
                    }
                    else {
                        // FACULTAD O URA RECHAZA DOCUMENTOS DEL ALUMNO
                        $rechazados_escuela=Tramite_Requisito::join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
                        ->where('tramite_requisito.idTramite',$tramite->idTramite)->where('tramite_requisito.des_estado_requisito','RECHAZADO')
                        ->where('requisito.responsable',5)->first();
                        // SI ES QUE RECHAZÓ A ALUMNO Y A ESCUELA, UNA VEZ QUE EL ALUMNO SUBSANA, LO PASA A ESCUELA, SINO LO PASA A FACULTAD
                        if($rechazados_escuela){
                            // la facultad observa un documento a la escuela
                            $historial_estados->idEstado_nuevo=30;
                            $tramite-> idEstado_tramite=30;
                        }else {
                            // Rechazados por ura
                            $rechazados_facultad=Tramite_Requisito::join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
                            ->where('tramite_requisito.idTramite',$tramite->idTramite)->where('tramite_requisito.des_estado_requisito','RECHAZADO')
                            ->where('requisito.responsable',8)->first();
                            // SI ES QUE RECHAZÓ A ESCUELA Y FACULTAD, UNA VEZ QUE LA ESCUELA SUBSANA, LO PASA A FACULTAD, SINO LO PASA A URA
                            if($rechazados_facultad){
                                // la facultad observa un documento a la escuela
                                $historial_estados->idEstado_nuevo=32;
                                $tramite-> idEstado_tramite=32;
                            }else {
                                $tramite-> idEstado_tramite=$ultimo_historial->idEstado_actual;
                                $historial_estados->idEstado_nuevo=$ultimo_historial->idEstado_actual;
                            }
                        }
                        
                    }
                } else {
                    $historial_estados->idEstado_nuevo=7;
                    $tramite-> idEstado_tramite=7;
                }
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();

                // $tramite-> idEstado_tramite=7;
                $tramite-> save();   
            }

            //Requisitos
            $tramite->requisitos=Tramite_Requisito::select('*')
            ->join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
            ->where('tramite_requisito.idTramite',$tramite->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
            //Datos del usuario al que pertenece el trámite
            $usuario=User::findOrFail($tramite->idUsuario)->first();
            // Obtenemos el motivo certificado(en caso lo tengan) de cada trámite 
            if ($tramite->idTipo_tramite==1) {
                $motivo=Motivo_Certificado::Where('idMotivo_certificado',$tramite->idMotivo_certificado)->first();
                $tramite->motivo=$motivo->nombre;
            }
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
                $tramite->escuela=$dependenciaDetalle->nombre;

            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
                $tramite->mencion=$dependenciaDetalle->nombre;
            }
            // $tramite->escuela=$dependenciaDetalle->nombre;
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
            // $tramite->fut="fut/".$tramite->idTramite;
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
    
    public function anularTramite(Request $request){
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

            $tramite->idEstado_tramite=$historial_estados->idEstado_nuevo;
            $tramite->update();
            DB::commit();
            return response()->json(true,200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function aprobarRequisito(Request $request){
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
            
            if ($request->idTipo_tramite==1) {
                $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                ,'motivo_certificado.nombre as motivo','tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad','tramite.idEstado_tramite','tramite.idTipo_tramite_unidad','tipo_tramite.idTipo_tramite')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('motivo_certificado','motivo_certificado.idMotivo_certificado','tramite_detalle.idMotivo_certificado')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->find($request->idTramite);
            }else {
                $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
                ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
                /*,'motivo_certificado.nombre as motivo'*/,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
                , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
                ,'tramite.exonerado_archivo','tramite.idUnidad','tramite.idEstado_tramite','tramite.idTipo_tramite_unidad','tipo_tramite.idTipo_tramite'
                ,'tramite_detalle.certificado_final')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                // ->join('motivo_certificado','motivo_certificado.idMotivo_certificado','tramite_detalle.idMotivo_certificado')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->join('voucher','tramite.idVoucher','voucher.idVoucher')
                ->find($request->idTramite);
            }

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
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$request->idTramite)
            ->get();
            $tramite->fut="fut/".$tramite->idTramite;
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            // $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
                $tramite->escuela=$dependenciaDetalle->nombre;
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
                $tramite->mencion=$dependenciaDetalle->nombre;
            }
            // $tramite->escuela=$dependenciaDetalle->nombre;
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

    public function Paginacion($items, $size, $page = null, $options = [])
    {
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return $response=new LengthAwarePaginator($items->forPage($page, $size), $items->count(), $size, $page, $options);
    }
}
