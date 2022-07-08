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
            
            $voucherValidate=Voucher::Where('entidad',$request->input('entidad'))->where('nro_operacion',$request->input('nro_operacion'))
            ->where('fecha_operacion',$request->input('fecha_operacion'))
            ->first();
            if($voucherValidate){
                return response()->json(['status' => '400', 'message' => 'El voucher ya se encuentra registrado!!'], 400);
            }else{
                // REGISTRAMOS LE VOUCHER
                $voucher=new Voucher;
                $voucher->entidad=$request->input('entidad');
                $voucher->nro_operacion=$request->input('nro_operacion');
                $voucher->fecha_operacion=$request->input('fecha_operacion');

                $voucher->descipcion_estado=$request->input('descipcion_estado');

                if($request->hasFile("archivo")){
                    $file=$request->file("archivo");
            
                    // EL NOMBRE DEL VOUCHER SERÁ EL NÚMERO DE DNI
                    $nro_doc=$request->input('nro_documento');

                    // Vemos el número de oficio que es.
                    // $aux = str_replace("RESOLUCIÓN DE CONSEJO UNIVERSITARIO N° ", "", $request->input('nombre_resolucion'));
                    // $num_oficio = str_replace("/UNT", "", $aux);
            
                    // nomenclatura de los PDFs: RCU-N-029-2021-UNT.pdf
                    // $nombre = "RCU-N-".$num_oficio."-UNT.".$file->guessExtension();
                    $nombre = $file->getClientOriginalName();
                    // $nombreBD = "/storage/resoluciones_PDFs/RCU-N-".$num_oficio."-UNT.".$file->guessExtension();
                    $nombreBD = "/storage/vouchers_tramites/".$nombre;
                    // $nombreBD = "/storage/resoluciones_PDFs/".$nombre_imagen_con_extension";
            
                    if($file->guessExtension()=="jpg"){
                      $file->storeAs('public/vouchers_tramites', $nombre);
                      $voucher->archivo = $nombreBD;
                    }
                }
                $voucher->save();

                // REGISTRAMOS EL TRÁMITE
                $tramite=new Tramite;
                $tramite -> idTipo_tramite=$request->input('idTipo_tramite');
                $tramite -> nro_documento=$request->input('nro_documento');
                $tramite -> idColacion=$request->input('idColacion');
                $tramite -> idVoucher=$voucher->idVoucher;
                $tramite -> idEstado_tramite=$request->input('idEstado_tramite');
                $tramite -> idModalidad_grado=$request->input('idModalidad_grado');
                $tramite -> descripcion_estado=$request->input('descripcion_estado');
                $tramite -> codigo=$request->input('codigo');
                $tramite -> save();

                // GUARDAMOS LOS REQUISITOS DEL TRÁMITE REGISTRADO
                $requisitos=Tipo_Tramite::select('requisitos.idRequisito','requisitos.nombre')
                ->join('requisitos','tipo_tramite.idTipo_tramite','requisitos.idTipo_tramite')
                ->Where('tipo_tramite.idTipo_tramite',$request->idTipo_tramite)->get();
                foreach ($requisitos as $requisito) {
                    $tramite_requisito=new Tramite_Requisito;
                    $tramite_requisito->idTramite=$tramite->idTramite;
                    $tramite_requisito->idRequisito=$requisito->idRequisito;
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
            return response()->json(['status' => '400', 'message' => 'Error al registrar trámite'], 400);
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
