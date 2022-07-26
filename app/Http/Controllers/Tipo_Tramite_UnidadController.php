<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tipo_Tramite_Unidad;
use App\Requisito;
class Tipo_Tramite_UnidadController extends Controller
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
        return Tipo_tramite_unidad::all();
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

    public function getAllByTipo_tramiteUnidad($idTipo_tramite,$idUnidad){
        $tipo_tramites= Tipo_Tramite_Unidad::where('idTipo_tramite',$idTipo_tramite)
        ->where('idUnidad',$idUnidad)
        ->get();
        // $requisitos = Requisito::where('idTipo_tramite_unidad',$idTipo_tramite)->get();
        // return response()->json(['status' => '200', 'tipos_unida_tratmites' => $tipos,'requisitos'=>$requisitos], 200);
        return response()->json(['status' => '200', 'tipo_tramite_unidad' => $tipo_tramites], 200);
    }
}
