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

    public function getTramitesforPendientesImpresion()
    {
        return Tipo_tramite_unidad::whereIn('idTipo_tramite_unidad',[15,16,34])->get();
    }

    public function getTramitesforValidaUraDuplicados()
    {
        return Tipo_tramite_unidad::whereIn('idTipo_tramite_unidad',[42,43,44])->get();
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
        ->where('estado',true)
        ->orderBy('descripcion', 'ASC')
        ->get();
        return response()->json(['status' => '200', 'tipo_tramite_unidad' => $tipo_tramites], 200);
    }
}
