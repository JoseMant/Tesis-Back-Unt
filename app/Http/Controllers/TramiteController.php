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
        $this->middleware('jwt');
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
            
            if ($request->query('search')!="") {
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.nro_tramite','tramite.created_at','tramite.idTramite','tramite.idTipo_tramite_unidad','estado_tramite.idEstado_tramite',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado')
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
                foreach ($tramites as $key => $tramite) {
                    //Obtenemos el historial de cada trámite
                    $tramite->historial=Historial_Estado::Where('idTramite',$tramite->idTramite)->get();
                    foreach ($tramite->historial as $key => $item) {
                        if($item->idEstado_actual!=null){
                            $item->estado_actual=Estado_Tramite::Where('idEstado_tramite',$item->idEstado_actual)->first();
                        }else{
                            $item->estado_actual="Ninguno";
                        }
                        $item->estado_nuevo=Estado_Tramite::Where('idEstado_tramite',$item->idEstado_nuevo)->first();
                    }
                }
            }else {
                // TRÁMITES POR USUARIO
                $tramites=Tramite::select('tramite.nro_tramite','tramite.created_at','tramite.idTramite','tramite.idTipo_tramite_unidad','estado_tramite.idEstado_tramite',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'estado_tramite.nombre as estado')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('estado_tramite','estado_tramite.idEstado_tramite','tramite.idEstado_tramite')
                ->Where('tramite.idUsuario',$idUsuario)
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();
                foreach ($tramites as $key => $tramite) {
                    $tramite->historial=Historial_Estado::Where('idTramite',$tramite->idTramite)->get();
                    foreach ($tramite->historial as $key => $item) {
                        if($item->idEstado_actual!=null){
                            $item->estado_actual=Estado_Tramite::Where('idEstado_tramite',$item->idEstado_actual)->first();
                        }else{
                            $item->estado_actual="Ninguno";
                        }
                        $item->estado_nuevo=Estado_Tramite::Where('idEstado_tramite',$item->idEstado_nuevo)->first();
                    }
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
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e], 400);
        }  
    }

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
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','unidad.idUnidad','tipo_tramite.descripcion as tipo_tramite','tipo_tramite_unidad.descripcion as tipo_tramite_unidad','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            /*,'motivo_certificado.nombre as motivo'*/,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            ,'voucher.nro_operacion','voucher.entidad','voucher.fecha_operacion','tipo_tramite_unidad.costo','tramite.exonerado_archivo'
            ,'tipo_tramite.idTipo_tramite','tramite.comentario as comentario_tramite','voucher.comentario as comentario_voucher'
            ,'tramite_detalle.idMotivo_certificado','voucher.des_estado_voucher')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            // ->join('motivo_certificado','motivo_certificado.idMotivo_certificado','tramite_detalle.idMotivo_certificado')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idusuario',$idUsuario)
            ->get();   
            foreach ($tramites as $key => $tramite) {
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
                    //     $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
                    // }else {
                        // $personaSga=PersonaSga::Where('per_dni',$usuario->nro_documento)->first();
                        // if ($personaSga) {
                            $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
                        // }
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

            // se tiene que validar también el idUsuario
            $tramiteValidate=Tramite::join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->Where('entidad',trim($request->entidad))->where('nro_operacion',trim($request->nro_operacion))
            ->where('fecha_operacion',trim($request->fecha_operacion))
            ->where('idUsuario',trim($idUsuario))
            ->first();
            if($tramiteValidate){
                return response()->json(['status' => '400', 'message' => 'El voucher ya se encuentra registrado!!'], 400);
            }else{
                $tramite=new Tramite;

                //AÑADIMOS EL NÚMERO DE TRÁMITE
                $inicio=date('Y-m-d')." 00:00:00";
                $fin=date('Y-m-d')." 23:59:59";
                $last_tramite=Tramite::whereBetween('created_at', [$inicio , $fin])->orderBy("created_at","DESC")->first();
                if ($last_tramite) {
                    $correlativo=(int)(substr($last_tramite->nro_tramite,0,3));
                    $correlativo++;
                    if ($correlativo<10) {
                        $tramite -> nro_tramite="00".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                    } elseif($correlativo<100){
                        $tramite -> nro_tramite="0".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                    }else{
                        $tramite -> nro_tramite=$correlativo.date('d').date('m').substr(date('Y'),2,3);
                    }
                }else{
                    $tramite -> nro_tramite="001".date('d').date('m').substr(date('Y'),2,3);
                }

                // REGISTRAMOS LE VOUCHER
                $voucher=new Voucher;
                $voucher->entidad=trim($request->entidad);
                $voucher->nro_operacion=trim($request->nro_operacion);
                $voucher->fecha_operacion=trim($request->fecha_operacion);
                // $voucher->des_estado_voucher=trim($request->des_estado_voucher);

                if($request->hasFile("archivo")){
                    $file=$request->file("archivo");
                    $nombre = $tramite->nro_tramite.'.'.$file->guessExtension();
                    $nombreBD = "/storage/vouchers_tramites/".$nombre;
                    if($file->guessExtension()=="pdf"){
                      $file->storeAs('public/vouchers_tramites', $nombre);
                      $voucher->archivo = $nombreBD;
                    }
                }
                $voucher->comentario=null;
                $voucher->save();

                // REGISTRAMOS EL DETALLE DEL TRÁMITE REGISTRADO
                $tipo_tramite = Tipo_Tramite::select('tipo_tramite.idTipo_tramite','tipo_tramite.descripcion')->join('tipo_tramite_unidad', 'tipo_tramite_unidad.idTipo_tramite', 'tipo_tramite.idTipo_tramite')
                ->where('tipo_tramite_unidad.idTipo_tramite_unidad', $request->idTipo_tramite_unidad)->first();
                $tramite_detalle=new Tramite_Detalle();

                switch ($tipo_tramite->idTipo_tramite) {
                    case 1:
                        $tramite_detalle->idCronograma_carpeta = null;
                        $tramite_detalle->idModalidad_titulo_carpeta=null;
                        $tramite_detalle->idMotivo_certificado=trim($request->idMotivo_certificado);
                        break;
                    case 2:
                        $tramite_detalle->idCronograma_carpeta = trim($request->idCronograma_carpeta);
                        $tramite_detalle->idModalidad_titulo_carpeta=trim($request->idModalidad_titulo_carpeta);
                        $tramite_detalle->idMotivo_certificado=null;
                        break;
                    case 3:
                        $tramite_detalle->idCronograma_carpeta = null;
                        $tramite_detalle->idModalidad_titulo_carpeta=null;
                        $tramite_detalle->idMotivo_certificado=null;
                        break;
                }
                $tramite_detalle->asignado_certificado=null;
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
                $tramite -> exonerado_archivo=null; //corregir
                $tramite -> idEstado_tramite=2;
                // REGISTRAMOS LA FIRMA DEL TRÁMITE(DEBE SER UNA SOLA VEZ EL REGISTRO PARA TODOS LOS TRÁMITES)


                // if($request->hasFile("archivo_firma")){
                //     $file=$request->file("archivo_firma");
                //     $nombre = $dni.".".$file->guessExtension();
                //     $nombreBD = "/storage/firmas_tramites/".$nombre;
                //     // Validamos que no se gaurde la misma firma para otro trámite
                //     $tramiteValidate=Tramite::where("firma_tramite",$nombreBD)->first();
                //     if (!$tramiteValidate) {
                //         if($file->guessExtension()=="jpg"){
                //             $file->storeAs('public/firmas_tramites', $nombre);
                //             $tramite->firma_tramite = $nombreBD;
                //           }
                //     }
                // }
                // $tramite -> save();

                // ---------------------------------------------------
                if($request->hasFile("archivo_firma")){
                    $file=$request->file("archivo_firma");
                    $nombre = $tramite->nro_tramite.".".$file->guessExtension();
                    $nombreBD = "/storage/firmas_tramites/".$nombre;
                    if($file->guessExtension()=="jpg"){
                      $file->storeAs('public/firmas_tramites', $nombre);
                      $tramite->firma_tramite = $nombreBD;
                    }
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
                        $nombreBD = "/storage"."/".$tipo_tramite->descripcion."/".$requisito["descripcion"]."/".$nombre;
                        if($file->guessExtension()==$requisito["extension"]){
                          $file->storeAs("/public"."/".$tipo_tramite->descripcion."/".$requisito["descripcion"], $nombre);
                          $tramite_requisito->archivo = $nombreBD;
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

                DB::commit();
                dispatch(new RegistroTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
                return response()->json(['status' => '200', 'usuario' => 'Trámite registrado correctamente!!'], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e], 400);
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function Paginacion($items, $size, $page = null, $options = [])
    {
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return $response=new LengthAwarePaginator($items->forPage($page, $size), $items->count(), $size, $page, $options);
    }
}
