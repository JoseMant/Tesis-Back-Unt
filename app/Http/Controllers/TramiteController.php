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
            ->Where('entidad',$request->input('entidad'))->where('nro_operacion',$request->input('nro_operacion'))
            ->where('fecha_operacion',$request->input('fecha_operacion'))
            ->where('nro_documento',$request->input('nro_documento'))
            ->get();
            // $voucherValidate=Voucher::Where('entidad',$request->input('entidad'))->where('nro_operacion',$request->input('nro_operacion'))
            // ->where('fecha_operacion',$request->input('fecha_operacion'))
            // ->first();
            if($tramiteValidate){
                return response()->json(['status' => '400', 'message' => 'El voucher ya se encuentra registrado!!'], 400);
            }else{
                // REGISTRAMOS LE VOUCHER
                $voucher=new Voucher;
                $voucher->entidad=trim($request->entidad);
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

                // REGISTRAMOS EL TRÁMITE
                $tramite=new Tramite;
                $tramite -> idTipo_tramite=$request->input('idTipo_unidad_tramite');
                $tramite -> nro_documento=$request->input('nro_documento');
                $tramite -> codigo=$request->input('nro_tramite');
                $tramite -> idVoucher=$voucher->idVoucher;
                $tramite -> idEstado_tramite=$request->input('idEstado_tramite');
                $tramite -> idDependencia_detalle=$request->input('idDependencia_detalle');
                $tramite -> idDependencia=$request->input('idDependencia');
                $tramite -> descripcion_estado=$request->input('des_estado_tramite');
                $tramite -> save();
                

                // REGISTRAMOS EL DETALLE DEL TRÁMITE REGISTRADO
                $tramite_detalle=new Tramite_Detalle();
                $tramite_detalle->idCronograma_carpeta=$request->idCronograma_carpeta;
                $tramite_detalle->idModalidad_carpeta=$request->idModalidad_carpeta;
                $tramite_detalle->exonerado_carpeta=$request->exonerado_carpeta;
                $tramite_detalle->motivo_certificado=$request->idMotivo_certificado;
                $tramite_detalle->objeto_solicitud_certificado=$request->objeto_solicitud_certificado;
                $tramite_detalle->idTramite=$tramite->idTramite;

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

                // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
                $token = JWTAuth::getToken();
                $apy = JWTAuth::getPayload($token);
                $idUsuario=$apy['idUsuario'];

                // REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$idUsuario;
                $historial_estados->estado_nuevo='TRÁMITE REGISTRADO';
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
