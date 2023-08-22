<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Historial_Codigo_Diploma;
use App\Tramite;
use App\Tramite_Detalle;

class Historial_Codigo_DiplomaController extends Controller
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
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramite = Tramite::findOrFail($request->idTramite);
        $idTramite_detalle = $tramite->idTramite_detalle;
        $tramite_detalle = Tramite_Detalle::findOrFail($idTramite_detalle);

        $codigo_unique = Historial_Codigo_Diploma::where('codigo_diploma_after', $request->codigo_diploma_after)->first(); 

        DB::beginTransaction();
        try {
    
            if($codigo_unique == null)
            {
                $fecha_conver = substr($request->fecha_historial,0,-14); 

                $historial = new Historial_Codigo_Diploma;
               
                $historial->idTramite = $request->idTramite;
                $historial->codigo_diploma_before = $request->codigo_diploma_before;
                $historial->codigo_diploma_after = $request->codigo_diploma_after;
                $historial->descripcion = $request->descripcion;
                $historial->fecha_historial = $fecha_conver;
                $historial->idUsuario = $idUsuario;
                $historial->estado = 1;
                $historial -> save();
                
                //Actualizar el codigo de diploma del tramite mismo
                $tramite_detalle->codigo_diploma = $request->codigo_diploma_after;
                $tramite_detalle->save();

                DB::commit();
                return response()->json($historial, 200); 

            }

            else
            {
                return response()->json(['status' => '400', 'message' => 'Código Diploma ya existe'], 400);
            }            

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }



    public function GuardarCodigoDiplomaInTramiteDetalle(Request $request)
    {
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramite = Tramite::findOrFail($request->idTramite);
        $idTramite_detalle = $tramite->idTramite_detalle;
        $tramite_detalle = Tramite_Detalle::findOrFail($idTramite_detalle);

        DB::beginTransaction();
        try {
    
            //Actualizar el codigo de diploma del tramite mismo
            $tramite_detalle->codigo_diploma = $request->codigo_diploma_after;
            $tramite_detalle->save();
            
            DB::commit();
            return response()->json($tramite_detalle, 200);
       

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
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


    public function GetHistorialDiplomaAnulacion(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idTramite=$apy['idTramite'];

        $historial=Historial_Codigo_Diploma::where('idTramite',$idTramite)->get();

        return response()->json(['status' => '200', 'historial_diploma_anulacion' => $historial], 200);
        
    }





}
