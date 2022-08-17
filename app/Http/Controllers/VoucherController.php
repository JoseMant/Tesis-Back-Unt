<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Arr;
use App\Voucher;
use App\Tramite;
use App\Tipo_Tramite_Unidad;
use App\Tipo_Tramite;
use App\User;
use App\Historial_Estado;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Jobs\ActualizacionTramiteJob;

class VoucherController extends Controller
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
            if (strtoupper($request->des_estado_voucher)=="APROBADO") {
                $historial_estados->idEstado_nuevo=3;
            }elseif (strtoupper($request->des_estado_voucher)=="RECHAZADO") {
                $historial_estados->idEstado_nuevo=4;
            }
            $historial_estados->fecha=date('Y-m-d h:i:s');
            $historial_estados->save();
            $tramite->idEstado_tramite=$historial_estados->idEstado_nuevo;
            $tramite->save();
            DB::commit();
            // mensaje de validación de voucher
            dispatch(new ActualizacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));

            return response()->json(['status' => '200', 'message' => "Voucher validado con éxito"], 200);
        } catch (\Exception $e) {
          DB::rollback();
          return response()->json(['status' => '400', 'message' => $e], 400);
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
                ,'exonerado_archivo as exonerado','voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.archivo',
                DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),'voucher.comentario')
                ->join('tramite','tramite.idVoucher','voucher.idVoucher')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite','tipo_tramite.descripcion')
                ->where('des_estado_voucher','PENDIENTE')
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
                ->where('des_estado_voucher','PENDIENTE')
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();
            }
            foreach ($vouchers as $key => $voucher) {
                $voucher->archivo="http://127.0.0.1:8000".$voucher->archivo;
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
                $voucher->archivo="http://127.0.0.1:8000".$voucher->archivo;
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
                $voucher->archivo="http://127.0.0.1:8000".$voucher->archivo;
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
    public function Paginacion($items, $size, $page = null, $options = [])
    {
        // $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return $response=new LengthAwarePaginator($items->forPage($page, $size), $items->count(), $size, $page, $options);
    }
}
