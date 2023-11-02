<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Requisito;
use App\Amnistia;
class RequisitoController extends Controller
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
        return Requisito::All();
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

    public function getAllByTipo_tramite_unidad($idTipo_tramite_unidad){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $dni=$apy['nro_documento'];

        $amnistiado=Amnistia::where('nro_documento',$dni)->where('idTipo_tramite_unidad',$idTipo_tramite_unidad)->first();
        if ($amnistiado) {
            if ($idTipo_tramite_unidad==15 && $amnistiado->tipo='G') {
                $requisitos = Requisito::where('idTipo_tramite_unidad',$idTipo_tramite_unidad)
                ->where('estado',true)
                // ->orWhere('idRequisito',71) // Se ha comentado el requisito de curso intensivo por el bachiller automático
                ->orWhere('idRequisito',136) // constancia de no adeudo de amnistía
                ->get();
            }elseif ($idTipo_tramite_unidad==16 && $amnistiado->tipo='T') {
                $requisitos = Requisito::where('idTipo_tramite_unidad',$idTipo_tramite_unidad)
                ->where('estado',true)
                ->orWhere('idRequisito',72) //curso intensivo
                ->orWhere('idRequisito',137) // constancia de no adeudo de amnistía
                ->get();
            }else {
                $requisitos = Requisito::where('idTipo_tramite_unidad',$idTipo_tramite_unidad)
                ->where('estado',true)
                ->orWhere('idRequisito',136) // constancia de no adeudo de amnistía
                ->get();
            }
        }else {
            $requisitos = Requisito::where('idTipo_tramite_unidad',$idTipo_tramite_unidad)
            ->where('estado',true)->get();
        }
        return response()->json(['status' => '200', 'requisitos'=>$requisitos], 200);
    }
}
