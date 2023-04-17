<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Arr;
use App\Voucher;
use App\Tramite;
use App\Tipo_Tramite_Unidad;
use App\Escuela;
use App\Mencion;
use App\User;
use App\Historial_Estado;
use App\Tramite_Requisito;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Jobs\ActualizacionTramiteJob;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteTesoreriaExport;

class VoucherController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt',['except' => ['reporteTesoreria']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Voucher::All();
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
        //
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
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            // Obtenemos el voucher a validar y actualizamos los datos
            $voucher = Voucher::findOrFail($id);
            // modificamos el estado del trámite
            $tramite=Tramite::Where('idVoucher',$voucher->idVoucher)->first();
            $usuario=User::where('idUsuario',$tramite->idUsuario)->first();
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)->first();
            $tipo_tramite=Tipo_Tramite::where('idTipo_tramite',$tipo_tramite_unidad->idTipo_tramite)->first();

            $voucher->des_estado_voucher=$request->des_estado_voucher;
            if (strtoupper($request->des_estado_voucher)=="APROBADO") {
                $voucher->validado=1;
            }
            $voucher->idUsuario_aprobador=$idUsuario;
            $voucher->comentario=trim($request->comentario);
            $voucher -> update();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estados=new Historial_Estado;
            $historial_estados->idTramite=$tramite->idTramite;
            $historial_estados->idUsuario=$idUsuario;
            $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
            $historial_estados->fecha=date('Y-m-d h:i:s');
            if (strtoupper($request->des_estado_voucher)=="APROBADO") {
                $historial_estados->idEstado_nuevo=3;
                $historial_estados->save();
                
                if ($tipo_tramite->idTipo_tramite==1 || $tipo_tramite->idTipo_tramite==4) {
                    if ($tipo_tramite->idTipo_tramite==1) {
                        $tramite->idUsuario_asignado=88;
                    }
                    //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                    $historial_estados=new Historial_Estado;
                    $historial_estados->idTramite=$tramite->idTramite;
                    $historial_estados->idUsuario=$idUsuario;
                    $historial_estados->idEstado_actual=$historial_estados->idEstado_nuevo;
                    $historial_estados->idEstado_nuevo=5;
                    $historial_estados->fecha=date('Y-m-d h:i:s');
                    $historial_estados->save();
                }
                elseif ($tipo_tramite->idTipo_tramite==3) {
                    // SI EL TRÁMITE ES DE CARNET, SE ASIGNA AUTOMÁTICAMENTE UN USUARIO
                    $tramite->idUsuario_asignado=2;
                    // $tramite->update();
                    //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                    $historial_estados=new Historial_Estado;
                    $historial_estados->idTramite=$tramite->idTramite;
                    $historial_estados->idUsuario=$idUsuario;
                    $historial_estados->idEstado_actual=3;
                    if ($tramite->idTipo_tramite_unidad==30||$tramite->idTipo_tramite_unidad==31||$tramite->idTipo_tramite_unidad==32||$tramite->idTipo_tramite_unidad==33) {
                        $historial_estados->idEstado_nuevo=25;
                    }
                    else {
                        $historial_estados->idEstado_nuevo=7;
                    }
                    $historial_estados->fecha=date('Y-m-d h:i:s');
                    $historial_estados->save();
                    $tramite->idEstado_tramite = $historial_estados->idEstado_nuevo;
                    $tramite->update();
                }elseif ($tipo_tramite->idTipo_tramite==2) {
                    // SI EL TRÁMITE ES DE GRADO o TITULO, SE ASIGNA AUTOMÁTICAMENTE UN USUARIO
                    if ($tramite->idTipo_tramite_unidad==15 || $tramite->idTipo_tramite_unidad==35 || $tramite->idTipo_tramite_unidad==36) {
                        $tramite->idUsuario_asignado=67;
                    }else {
                        //$tramite->idTipo_tramite_unidad==16 || $tramite->idTipo_tramite_unidad==34
                        $tramite->idUsuario_asignado=68;
                    }
                    //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                    $historial_estados=new Historial_Estado;
                    $historial_estados->idTramite=$tramite->idTramite;
                    $historial_estados->idUsuario=$idUsuario;
                    $historial_estados->idEstado_actual=3;
                    $historial_estados->idEstado_nuevo=17;
                    $historial_estados->fecha=date('Y-m-d h:i:s');
                    $historial_estados->save();
                    $tramite->idEstado_tramite = $historial_estados->idEstado_nuevo;
                    $tramite->update();

                    // REGISTRAMOS EL CERTIFICADO EN PARALELO
                    if ($tramite->idTipo_tramite_unidad==15 || $tramite->idTipo_tramite_unidad==34 
                    || ($tramite->idTipo_tramite_unidad==16 && $tramite->idDependencia_detalle==11)) {
                        $tramiteCertificado=new Tramite;
                        $tramiteCertificado->nro_tramite=$tramite->nro_tramite;
                        // REGISTRAMOS EL TRÁMITE
                        $tramiteCertificado -> idTramite_detalle=$tramite->idTramite_detalle;
                        $tramiteCertificado -> idTipo_tramite_unidad=37;
                        $tramiteCertificado -> idVoucher=$tramite->idVoucher;
                        $tramiteCertificado -> idUsuario=$tramite->idUsuario;
                        $tramiteCertificado -> idUnidad=$tramite->idUnidad;
                        $tramiteCertificado -> idDependencia=$tramite->idDependencia;
                        $tramiteCertificado -> idDependencia_detalle=$tramite->idDependencia_detalle;
                        $tramiteCertificado -> nro_matricula=$tramite->nro_matricula;
                        $tramiteCertificado -> comentario="CERTIFICADO PARA SOLICITUD DE ".$tipo_tramite_unidad->descripcion;
                        $tramiteCertificado -> sede=$tramite->sede;
                        $tramiteCertificado->idUsuario_asignado=null;
                        $tramiteCertificado -> idEstado_tramite=5;
                        $tramiteCertificado->firma_tramite = $tramite->firma_tramite;
                        $tramiteCertificado -> save();
    

                        // obtenemos el requisito de la foto pasaporte para el certificado paralelo
                        $requisito_foto=Tramite_Requisito::where('idTramite',$tramite->idTramite)
                        ->where(function($query) use ($request)
                        {
                            $query->where('idRequisito',15)
                            ->orWhere('idRequisito',23)
                            ->orWhere('idRequisito',61);
                        })->first();

                        //agregamos ese mismo requisito como parte del certificado paralelo
                        $tramite_requisito=new Tramite_Requisito;
                        $tramite_requisito->idTramite=$tramiteCertificado->idTramite;
                        $tramite_requisito->idRequisito=$requisito_foto->idRequisito;
                        $tramite_requisito->archivo = $requisito_foto->archivo;
                        $tramite_requisito -> save();

                        //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                        $historial_estados_certificado=new Historial_Estado;
                        $historial_estados_certificado->idTramite=$tramiteCertificado->idTramite;
                        $historial_estados_certificado->idUsuario=$idUsuario;
                        $historial_estados_certificado->idEstado_actual=null;
                        $historial_estados_certificado->idEstado_nuevo=1;
                        $historial_estados_certificado->fecha=date('Y-m-d h:i:s');
                        $historial_estados_certificado->save();
    
                        //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                        $historial_estados_certificado=new Historial_Estado;
                        $historial_estados_certificado->idTramite=$tramiteCertificado->idTramite;
                        $historial_estados_certificado->idUsuario=$idUsuario;
                        $historial_estados_certificado->idEstado_actual=1;
                        $historial_estados_certificado->idEstado_nuevo=2;
                        $historial_estados_certificado->fecha=date('Y-m-d h:i:s');
                        $historial_estados_certificado->save();

                        //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                        $historial_estados_certificado=new Historial_Estado;
                        $historial_estados_certificado->idTramite=$tramiteCertificado->idTramite;
                        $historial_estados_certificado->idUsuario=$idUsuario;
                        $historial_estados_certificado->idEstado_actual=2;
                        $historial_estados_certificado->idEstado_nuevo=5;
                        $historial_estados_certificado->fecha=date('Y-m-d h:i:s');
                        $historial_estados_certificado->save();
                    }
                }
            }elseif (strtoupper($request->des_estado_voucher)=="RECHAZADO") {
                $historial_estados->idEstado_nuevo=4;
                $historial_estados->save();
            }
            $tramite->idEstado_tramite=$historial_estados->idEstado_nuevo;
            $tramite->update();
            DB::commit();
            // mensaje de validación de voucher
            dispatch(new ActualizacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));

            return response()->json(['status' => '200', 'message' => "Voucher validado con éxito"], 200);
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
    public function destroy($id)
    {
        //
    }
    public function Pendientes(Request $request){
        DB::beginTransaction();
        try {
            if ($request->query('search')!="") {
                $vouchers=Voucher::select('voucher.idVoucher','tramite.idTramite','tramite.nro_tramite', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno')
                ,'voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.archivo','usuario.nro_documento','tramite.nro_matricula',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'voucher.comentario','tramite.exonerado_archivo',
                'tipo_tramite_unidad.costo','tramite.idUnidad','tramite.idDependencia_detalle')
                ->join('tramite','tramite.idVoucher','voucher.idVoucher')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite','tipo_tramite.descripcion')
                ->where('des_estado_voucher','PENDIENTE')
                ->where('tramite.idEstado_tramite','!=',29)
                ->where('tramite.estado',1)
                ->where(function($query) use ($request)
                {
                    $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.costo','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('entidad','LIKE','%'.$request->query('search').'%')
                    ->orWhere('nro_operacion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('fecha_operacion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('usuario.nombres','LIKE','%'.$request->query('search').'%')
                    ->orWhere('usuario.apellidos','LIKE','%'.$request->query('search').'%');
                })
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();
            }else {
                $vouchers=Voucher::select('voucher.idVoucher','tramite.idTramite','tramite.nro_tramite', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno')
                ,'voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.archivo','usuario.nro_documento','tramite.nro_matricula',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'voucher.comentario','tramite.exonerado_archivo',
                'tipo_tramite_unidad.costo','tramite.idUnidad','tramite.idDependencia_detalle')
                ->join('tramite','tramite.idVoucher','voucher.idVoucher')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->where('des_estado_voucher','PENDIENTE')
                ->where('tramite.idEstado_tramite','!=',29)
                ->where('tramite.estado',1)
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();
            }

            foreach ($vouchers as $key => $tramite) {
                // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
                $dependenciaDetalle=null;
                if ($tramite->idUnidad==1) {
                    $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
                }else if ($tramite->idUnidad==2) {
                    
                }else if ($tramite->idUnidad==3) {
                    
                }else{
                    $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
                }
                $tramite->programa=$dependenciaDetalle->nombre;
            }

            $pagination=$this->Paginacion($vouchers, $request->query('size'), $request->query('page')+1);
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

    public function Aprobados(Request $request){
        DB::beginTransaction();
        try {
            if ($request->query('search')!="") {
                $vouchers=Voucher::select('voucher.idVoucher','tramite.idTramite','tramite.nro_tramite', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno')
                ,'exonerado_archivo as exonerado','voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.archivo',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'))
                ->join('tramite','tramite.idVoucher','voucher.idVoucher')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite','tipo_tramite.descripcion')
                ->where('des_estado_voucher','APROBADO')
                ->where(function($query) use ($request)
                {
                    $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('entidad','LIKE','%'.$request->query('search').'%')
                    ->orWhere('nro_operacion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('fecha_operacion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('usuario.nombres','LIKE','%'.$request->query('search').'%')
                    ->orWhere('usuario.apellidos','LIKE','%'.$request->query('search').'%');
                })
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();
            }else {
                $vouchers=Voucher::select('voucher.idVoucher','tramite.idTramite','tramite.nro_tramite', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno')
                ,'exonerado_archivo as exonerado','voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.archivo',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'))
                ->join('tramite','tramite.idVoucher','voucher.idVoucher')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->where('des_estado_voucher','APROBADO')
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();
            }
            foreach ($vouchers as $key => $voucher) {
                $voucher->archivo=$voucher->archivo;
                if ($voucher->exonerado==null) {
                    $voucher->exonerado="NO";
                }else {
                    $voucher->exonerado="SI";
                }
            }
            $pagination=$this->Paginacion($vouchers, $request->query('size'), $request->query('page')+1);
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
    public function Rechazados(Request $request){
        DB::beginTransaction();
        try {
            if ($request->query('search')!="") {
                $vouchers=Voucher::select('voucher.idVoucher','tramite.idTramite','tramite.nro_tramite', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno')
                ,'exonerado_archivo as exonerado','voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.archivo',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'voucher.comentario')
                ->join('tramite','tramite.idVoucher','voucher.idVoucher')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite','tipo_tramite.descripcion')
                ->where('des_estado_voucher','RECHAZADO')
                ->where(function($query) use ($request)
                {
                    $query->where('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('entidad','LIKE','%'.$request->query('search').'%')
                    ->orWhere('nro_operacion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('fecha_operacion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('usuario.nombres','LIKE','%'.$request->query('search').'%')
                    ->orWhere('usuario.apellidos','LIKE','%'.$request->query('search').'%');
                })
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();
            }else {
                $vouchers=Voucher::select('voucher.idVoucher','tramite.idTramite','tramite.nro_tramite', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno')
                ,'exonerado_archivo as exonerado','voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.archivo',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'voucher.comentario')
                ->join('tramite','tramite.idVoucher','voucher.idVoucher')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->where('des_estado_voucher','RECHAZADO')
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();
            }
            foreach ($vouchers as $key => $voucher) {
                $voucher->archivo=$voucher->archivo;
                if ($voucher->exonerado==null) {
                    $voucher->exonerado="NO";
                }else {
                    $voucher->exonerado="SI";
                }
            }
            $pagination=$this->Paginacion($vouchers, $request->query('size'), $request->query('page')+1);
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

    public function vouchersAprobados(Request $request){
        if ($request->query('search')!="") {
            $vouchers=Tramite::select(DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'usuario.nro_documento','tramite.nro_matricula',
            DB::raw("(case 
                when tramite.idUnidad = 1 then CONCAT(tipo_tramite_unidad.descripcion) 
                when tramite.idUnidad = 2 then CONCAT(tipo_tramite_unidad.descripcion) 
                when tramite.idUnidad = 3 then CONCAT(tipo_tramite_unidad.descripcion,'-',tipo_tramite.descripcion) 
            end) as tipo_tramite"),
            DB::raw("(case 
                when tramite.idUnidad = 1 then (select nombre from escuela where idEscuela=tramite.idDependencia_detalle)  
                when tramite.idUnidad = 4 then  (select denominacion from mencion where idMencion=tramite.idDependencia_detalle)
            end) as programa"),
            'voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','tipo_tramite_unidad.costo'
            )
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('usuario','tramite.idUsuario','usuario.idUsuario')
            ->join('tramite_detalle','tramite.idTramite_detalle','tramite_detalle.idTramite_detalle')
            ->join('tipo_tramite_unidad','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->where('voucher.des_estado_voucher','APROBADO')
            ->where(function($query) use ($request)
            {
                if($request->fecha_inicio){
                    $query->where('voucher.fecha_operacion','>=',$request->fecha_inicio);
                }
                if($request->fecha_fin){
                    $query->where('voucher.fecha_operacion','<=',$request->fecha_fin);
                }
            })
            // ->whereMonth('voucher.fecha_operacion',02)
            // ->whereYear('voucher.fecha_operacion',2023)
            ->where('tramite.idTipo_tramite_unidad','!=',37)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.nro_documento','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('voucher.entidad','LIKE','%'.$request->query('search').'%')
                ->orWhere('voucher.nro_operacion','LIKE','%'.$request->query('search').'%')
                ->orWhere('voucher.fecha_operacion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.costo','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy('fecha_operacion','asc')
            ->orderBy('programa','asc')
            ->orderBy('tipo_tramite','asc')
            ->orderBy('solicitante','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();
            $total=Tramite::join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('usuario','tramite.idUsuario','usuario.idUsuario')
            ->join('tramite_detalle','tramite.idTramite_detalle','tramite_detalle.idTramite_detalle')
            ->join('tipo_tramite_unidad','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->where('voucher.des_estado_voucher','APROBADO')
            ->where(function($query) use ($request)
            {
                if($request->fecha_inicio){
                    $query->where('voucher.fecha_operacion','>=',$request->fecha_inicio);
                }
                if($request->fecha_fin){
                    $query->where('voucher.fecha_operacion','<=',$request->fecha_fin);
                }
            })
            ->where('tramite.idTipo_tramite_unidad','!=',37)
            ->where('tramite.idEstado_tramite','!=',29)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.nro_documento','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('voucher.entidad','LIKE','%'.$request->query('search').'%')
                ->orWhere('voucher.nro_operacion','LIKE','%'.$request->query('search').'%')
                ->orWhere('voucher.fecha_operacion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.costo','LIKE','%'.$request->query('search').'%');
            })
            ->count();
        }else {
            $vouchers=Tramite::select(DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'usuario.nro_documento','tramite.nro_matricula',
            DB::raw("(case 
                when tramite.idUnidad = 1 then CONCAT(tipo_tramite_unidad.descripcion) 
                when tramite.idUnidad = 2 then CONCAT(tipo_tramite_unidad.descripcion) 
                when tramite.idUnidad = 3 then CONCAT(tipo_tramite_unidad.descripcion,'-',tipo_tramite.descripcion) 
                when tramite.idUnidad = 4 then CONCAT(tipo_tramite_unidad.descripcion) 
            end) as tipo_tramite"),
            DB::raw("(case 
                when tramite.idUnidad = 1 then (select nombre from escuela where idEscuela=tramite.idDependencia_detalle)  
                when tramite.idUnidad = 4 then  (select denominacion from mencion where idMencion=tramite.idDependencia_detalle)
            end) as programa"),
            'voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','tipo_tramite_unidad.costo'
            )
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('usuario','tramite.idUsuario','usuario.idUsuario')
            ->join('tramite_detalle','tramite.idTramite_detalle','tramite_detalle.idTramite_detalle')
            ->join('tipo_tramite_unidad','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->where('voucher.des_estado_voucher','APROBADO')
            ->where(function($query) use ($request)
            {
                if($request->fecha_inicio){
                    $query->where('voucher.fecha_operacion','>=',$request->fecha_inicio);
                }
                if($request->fecha_fin){
                    $query->where('voucher.fecha_operacion','<=',$request->fecha_fin);
                }
            })
            ->where('tramite.idTipo_tramite_unidad','!=',37)
            ->where('tramite.idEstado_tramite','!=',29)
            ->orderBy('fecha_operacion','asc')
            ->orderBy('programa','asc')
            ->orderBy('tipo_tramite','asc')
            ->orderBy('solicitante','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();
            
            $total =Tramite::join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('usuario','tramite.idUsuario','usuario.idUsuario')
            ->join('tramite_detalle','tramite.idTramite_detalle','tramite_detalle.idTramite_detalle')
            ->join('tipo_tramite_unidad','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->where('voucher.des_estado_voucher','APROBADO')
            ->where(function($query) use ($request)
            {
                if($request->fecha_inicio){
                    $query->where('voucher.fecha_operacion','>=',$request->fecha_inicio);
                }
                if($request->fecha_fin){
                    $query->where('voucher.fecha_operacion','<=',$request->fecha_fin);
                }
            })
            ->where('tramite.idTipo_tramite_unidad','!=',37)
            ->where('tramite.idEstado_tramite','!=',29)
            ->count();
        }
        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200', 'data' =>$vouchers,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);

    }


    public function reporteTesoreria($fecha_inicio,$fecha_fin){
        DB::beginTransaction();
        try {
            $descarga=Excel::download(new ReporteTesoreriaExport($fecha_inicio,$fecha_fin), 'Reporte_tesoreria.xlsx');
            return $descarga;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }


    public function Paginacion($items, $size, $page = null, $options = [])
    {
        // $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return $response=new LengthAwarePaginator($items->forPage($page, $size), $items->count(), $size, $page, $options);
    }
}
