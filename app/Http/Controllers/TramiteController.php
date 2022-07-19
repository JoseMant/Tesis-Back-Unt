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
            // se tiene que validar tmb el nro de documento 
            $tramiteValidate=Tramite::join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->Where('entidad',trim($request->entidad))->where('nro_operacion',trim($request->nro_operacion))
            ->where('fecha_operacion',trim($request->fecha_operacion))
            ->where('nro_documento',trim($request->nro_documento))
            ->get();
            // $voucherValidate=Voucher::Where('entidad',$request->input('entidad'))->where('nro_operacion',$request->input('nro_operacion'))
            // ->where('fecha_operacion',$request->input('fecha_operacion'))
            // ->first();
            if($tramiteValidate){
                return response()->json(['status' => '400', 'message' => 'El voucher ya se encuentra registrado!!'], 400);
            }else{
                // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
                $token = JWTAuth::getToken();
                $apy = JWTAuth::getPayload($token);
                $idUsuario=$apy['idUsuario'];

                // REGISTRAMOS LE VOUCHER
                $voucher=new Voucher;
                $voucher->idEntidad=trim($request->idEntidad);
                $voucher->nro_operacion=trim($request->nro_operacion);
                $voucher->fecha_operacion=trim($request->fecha_operacion);
                $voucher->des_estado_voucher=trim($request->des_estado_voucher);
                
                if($request->hasFile("archivo")){
                    $file=$request->file("archivo");
                    $nombre = $file->getClientOriginalName();
                    $nombreBD = "/storage/vouchers_tramites/".$nombre;            
                    if($file->guessExtension()=="pdf"){
                      $file->storeAs('public/vouchers_tramites', $nombre);
                      $voucher->archivo = $nombreBD;
                    }
                }
                $voucher->save();


                // REGISTRAMOS EL DETALLE DEL TRÁMITE REGISTRADO
                $tramite_detalle=new Tramite_Detalle();
                $tramite_detalle->idCronograma_carpeta=trim($request->idCronograma_carpeta);
                $tramite_detalle->idModalidad_carpeta=trim($request->idModalidad_carpeta);
                $tramite_detalle->exonerado_carpeta=trim($request->exonerado_carpeta);
                $tramite_detalle->motivo_certificado=trim($request->idMotivo_certificado);
                $tramite_detalle->objeto_solicitud_certificado=trim($request->objeto_solicitud_certificado);
                
                // REGISTRAMOS EL TRÁMITE
                $tramite=new Tramite;
                $tramite -> idTramite_detallle=$tramite_detalle->idTramite_detallle;
                $tramite -> idTipo_tramite_unidad=trim($request->idTipo_unidad_tramite);
                $tramite -> idVoucher=$voucher->idVoucher;
                $tramite -> idUsuario=$idUsuario;
                $tramite -> nro_tramite=Str::random(8);
                $tramite -> idUnidad=trim($request->idUnidad);
                $tramite -> idDependencia=trim($request->idDependencia);
                $tramite -> idDependencia_detalle=trim($request->idDependencia_detalle);
                $tramite -> nro_matricula=trim($request->nro_matricula);
                $tramite -> sede=trim($request->sede);
                $tramite -> idEstado_tramite=trim($request->idEstado_tramite);
                // REGISTRAMOS LA FIRMA DEL TRÁMITE
                if($request->hasFile("archivo_firma")){
                    $file=$request->file("archivo_firma");
                    $nombre = $file->getClientOriginalName();
                    $nombreBD = "/storage/firmas_tramites/".$nombre;            
                    if($file->guessExtension()=="pdf"){
                      $file->storeAs('public/firmas_tramites', $nombre);
                      $tramite->firma_tramite = $nombreBD;
                    }
                }
                $tramite -> save();

                // REGISTRAMOS LOS REQUISITOS DEL TRÁMITE REGISTRADO

                // $requisitos=Tipo_Tramite::select('requisitos.idRequisito','requisitos.nombre')
                // ->join('requisitos','tipo_tramite.idTipo_tramite','requisitos.idTipo_tramite')
                // ->Where('tipo_tramite.idTipo_tramite',$request->idTipo_tramite)->get();

                foreach ($request->requisitos as $requisito) {
                    $tramite_requisito=new Tramite_Requisito;
                    $tramite_requisito->idTramite=$tramite->idTramite;
                    $tramite_requisito->idRequisito=$requisito->idRequisito;
                    //Verificar archivo
                    if($requisito->hasFile("archivo")){
                        $file=$request->file("archivo");
                        $nombre = $file->getClientOriginalName();
                        $nombreBD = "/storage/requisitos_tramites/".$nombre;            
                        if($file->guessExtension()=="pdf"){
                          $file->storeAs('public/requisitos_tramites', $nombre);
                          $tramite_requisito->archivo = $nombreBD;
                        }
                    }
                    $tramite_requisito -> save();
                }

                // REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                // $historial_estados=new Historial_Estado;
                // $historial_estados->idTramite=$tramite->idTramite;
                // $historial_estados->idUsuario=$idUsuario;
                // $historial_estados->estado_nuevo='TRÁMITE REGISTRADO';
                // $historial_estados->fecha=date('Y-m-d h:i:s');
                // $historial_estados->save();
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
