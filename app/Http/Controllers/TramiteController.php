<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Tramite;
use App\Tipo_Tramite;
use App\Historial_Estado;
use App\Tramite_Requisito;
use App\Voucher;
use App\Tramite_Detalle;
use Illuminate\Support\Str;
class TramiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login','register','PruebaFiles']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Tramite::All();
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


            // $file=$request->file("archivo_firma");
            // $nombre = $dni.".".$file->guessExtension();
            // $nombreBD = "/storage/firmas_tramites/".$nombre;   
            // // Validamos que no se gaurde la misma firma para otro trámite
            // return $tramiteValidate=Tramite::where("firma_tramite",$nombreBD)->first();



            // se tiene que validar también el idUsuario 
            $tramiteValidate=Tramite::join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->Where('idEntidad',trim($request->idEntidad))->where('nro_operacion',trim($request->nro_operacion))
            ->where('fecha_operacion',trim($request->fecha_operacion))
            ->where('idUsuario',trim($idUsuario))
            ->first();
            // $voucherValidate=Voucher::Where('entidad',$request->input('entidad'))->where('nro_operacion',$request->input('nro_operacion'))
            // ->where('fecha_operacion',$request->input('fecha_operacion'))
            // ->first();
            if($tramiteValidate){
                return response()->json(['status' => '400', 'message' => 'El voucher ya se encuentra registrado!!'], 400);
            }else{
                // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
                // $token = JWTAuth::getToken();
                // $apy = JWTAuth::getPayload($token);
                // $idUsuario=$apy['idUsuario'];

                $tramite=new Tramite;

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
                $voucher->idEntidad=trim($request->idEntidad);
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
                $voucher->save();
                // return $voucher;

                // REGISTRAMOS EL DETALLE DEL TRÁMITE REGISTRADO
                $tipo_tramite = Tipo_Tramite::join('tipo_tramite_unidad', 'tipo_tramite_unidad.idTipo_tramite', 'tipo_tramite.idTipo_tramite')
                ->where('tipo_tramite_unidad.idTipo_tramite_unidad', $request->idTipo_tramite_unidad)->first();
                // return $tipo_tramite;
                $tramite_detalle=new Tramite_Detalle();
                
                switch ($tipo_tramite->idTipo_tramite) {
                    case 1:
                        $tramite_detalle->idCronograma_carpeta = null;
                        $tramite_detalle->idModalidad_titulo_carpeta=null;
                        $tramite_detalle->exonerado_carpeta=null;
                        $tramite_detalle->idMotivo_certificado=trim($request->idMotivo_certificado);
                        $tramite_detalle->solicitud_certificado=trim($request->solicitud_certificado);
                        break;
                        
                    default:
                        # code...
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
                $tramite -> sede=trim($request->sede);
                $tramite -> idEstado_tramite=1;
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

                // $requisitos=Tipo_Tramite::select('requisitos.idRequisito','requisitos.nombre')
                // ->join('requisitos','tipo_tramite.idTipo_tramite','requisitos.idTipo_tramite')
                // ->Where('tipo_tramite.idTipo_tramite',$request->idTipo_tramite)->get();
                
                if($request->hasFile("files")){

                    foreach ($request->file("files") as $key => $file) {
                        $requisito=json_decode($request->requisitos[$key],true);
                        // return $requisito["idRequisito"]; 
                        // return $file; 
                        $tramite_requisito=new Tramite_Requisito;
                        $tramite_requisito->idTramite=$tramite->idTramite;
                        $tramite_requisito->idRequisito=$requisito["idRequisito"];
                        //Verificar archivo
                        // $file=$request->file("archivo");
                        $nombre = $dni.".".$file->guessExtension();
                        return $nombreBD = "/storage"."/".$tipo_tramite->descripcion."/".$requisito["descripcion"]."/".$nombre;  
                        if($file->guessExtension()==$requisito["extension"]){
                          $file->storeAs('public/requisitos_tramites', $nombre);
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
                DB::commit();
                return response()->json(['status' => '200', 'message' => 'Trámite registrado correctamente!!'], 200);
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


    // public function PruebaFiles(Request $request){
    //     $file = $request->file("archivo");
    //     //Obtener el nombre de la imagen completo con su extension
    //     $nombre_imagen_con_extension = $request->file('archivo')->getClientOriginalName();
    //     // Obtener solo el nombre de la imagen, sin la extension
    //     $nombre_imagen = pathinfo($nombre_imagen_con_extension,PATHINFO_FILENAME);
    //     //Obtener solo la extension de la imagen
    //     $extension_imagen = $request->file('archivo')->getClientOriginalExtension();
    //     return $file->getClientOriginalName();
    // }
}
