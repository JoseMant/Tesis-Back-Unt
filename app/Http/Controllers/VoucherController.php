<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Arr;
use App\Voucher;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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
            $voucher->des_estado_voucher=$request->des_estado_voucher;
            if (strtoupper($request->des_estado_voucher)=="APROBADO") {
                $voucher->validado=1;
            }
            $voucher->idUsuario_aprobador=$idUsuario;
            $voucher -> update();
            DB::commit();
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
                $vouchers=Voucher::select('tramite.nro_tramite', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno')
                ,'tipo_tramite_unidad.descripcion','exonerado_archivo as exonerado','voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion')
                ->join('tramite','tramite.idVoucher','voucher.idVoucher')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->where('des_estado_voucher','PENDIENTE')
                ->where(function($query) use ($request)
                {
                    $query->where('descripcion','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
                    ->orWhere('entidad','LIKE','%'.$request->query('search').'%')
                    ->orWhere('nro_operacion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('fecha_operacion','LIKE','%'.$request->query('search').'%')
                    ->orWhere('usuario.nombres','LIKE','%'.$request->query('search').'%')
                    ->orWhere('usuario.apellidos','LIKE','%'.$request->query('search').'%');
                })
                ->get();
            }else {
                $vouchers=Voucher::select('tramite.nro_tramite', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno')
                ,'tipo_tramite_unidad.descripcion','exonerado_archivo as exonerado','voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion')
                ->join('tramite','tramite.idVoucher','voucher.idVoucher')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->where('des_estado_voucher','PENDIENTE')
                ->orderBy($request->query('sort'), $request->query('order'))
                ->get();
            }
            foreach ($vouchers as $key => $voucher) {
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
    public function Aprobados(){
        DB::beginTransaction();
        try {
            $vouchers=Voucher::select('tramite.nro_tramite', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno'),'usuario.nro_documento','tramite.nro_matricula'
            ,'tipo_tramite_unidad.descripcion','exonerado_archivo as exonerado','voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.idVoucher'
            ,'voucher.archivo','voucher.des_estado_voucher','voucher.idUsuario_aprobador','voucher.validado','voucher.estado','unidad.descripcion as unidad')
            ->join('tramite','tramite.idVoucher','voucher.idVoucher')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tipo_tramite_unidad.idUnidad')
            ->where('des_estado_voucher','APROBADO')->get();
            foreach ($vouchers as $key => $voucher) {
                if ($voucher->exonerado==null) {
                    $voucher->exonerado="NO";
                }else {
                    $voucher->exonerado="SI";
                }
            }
            return response()->json(['status' => '200', 'vouchers' =>$vouchers], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e], 400);
        }   
    }
    public function Rechazados(){
        DB::beginTransaction();
        try {
            $vouchers=Voucher::select('tramite.nro_tramite', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno'),'usuario.nro_documento','tramite.nro_matricula'
            ,'tipo_tramite_unidad.descripcion','exonerado_archivo as exonerado','voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.idVoucher'
            ,'voucher.archivo','voucher.des_estado_voucher','voucher.idUsuario_aprobador','voucher.validado','voucher.estado','unidad.descripcion as unidad')
            ->join('tramite','tramite.idVoucher','voucher.idVoucher')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tipo_tramite_unidad.idUnidad')
            ->where('des_estado_voucher','RECHAZADO')->get();
            foreach ($vouchers as $key => $voucher) {
                if ($voucher->exonerado==null) {
                    $voucher->exonerado="NO";
                }else {
                    $voucher->exonerado="SI";
                }
            }
            return response()->json(['status' => '200', 'vouchers' =>$vouchers], 200);
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
