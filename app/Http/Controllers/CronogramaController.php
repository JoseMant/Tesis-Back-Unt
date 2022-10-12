<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Cronograma;

class CronogramaController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }
    public function index(){
        DB::beginTransaction();
        try {
            $cronogramas=Cronograma::select('cronograma_carpeta.*','dependencia.nombre as dependencia','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite')
            ->join('dependencia','cronograma_carpeta.idDependencia','dependencia.idDependencia')
            ->join('tipo_tramite_unidad','cronograma_carpeta.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
            ->join('unidad','cronograma_carpeta.idUnidad','unidad.idUnidad')
            ->get();
            DB::commit();
            return response()->json($cronogramas, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
        
    }
    public function getCronogramasActivos($idDependencia,$idTipo_tramite_unidad){
        DB::beginTransaction();
        try {
            $cronogramas=Cronograma::where('cronograma_carpeta.estado',1)
            ->where('cronograma_carpeta.idDependencia',$idDependencia)
            ->where('cronograma_carpeta.idTipo_tramite_unidad',$idTipo_tramite_unidad)
            ->where('cronograma_carpeta.fecha_cierre_alumno','>=',date('Y-m-d'))
            ->get();
            DB::commit();
            return response()->json($cronogramas, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
        
    }
    public function buscar(Request $request){
        DB::beginTransaction();
        try {
            if ($request->query('query')!="") {
                $cronogramas=Cronograma::select('cronograma_carpeta.*','dependencia.nombre as dependencia','unidad.descripcion as unidad'
                ,'tipo_tramite_unidad.descripcion as tramite')
                ->join('dependencia','cronograma_carpeta.idDependencia','dependencia.idDependencia')
                ->join('tipo_tramite_unidad','cronograma_carpeta.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
                ->join('unidad','cronograma_carpeta.idUnidad','unidad.idUnidad')
                ->where(function($query) use ($request)
                    {
                        $query->where('cronograma_carpeta.fecha_colacion','LIKE', '%'.$request->query('query').'%')
                        ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('query').'%')
                        ->orWhere('dependencia.nombre','LIKE', '%'.$request->query('query').'%')
                        ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('query').'%');
                    })
                ->get();
            }else{
                $cronogramas=Cronograma::select('cronograma_carpeta.*','dependencia.nombre as dependencia','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite')
                ->join('dependencia','cronograma_carpeta.idDependencia','dependencia.idDependencia')
                ->join('tipo_tramite_unidad','cronograma_carpeta.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
                ->join('unidad','cronograma_carpeta.idUnidad','unidad.idUnidad')
                ->get();
            }
            DB::commit();
            return response()->json($cronogramas, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function store(Request $request){
        DB::beginTransaction();
        try {
            //validar cantidad de colaciones
            // $fechaComoEntero = strtotime($request->fecha_colacion);
            // $año=date("Y", $fechaComoEntero);
            // $mes=date("m", $fechaComoEntero);
            $inicio = date("Y-m-01", strtotime($request->fecha_colacion));
            $fin = date("Y-m-t", strtotime($request->fecha_colacion));
            $idUnidad=$request->idUnidad;
            $idDependencia=$request->idDependencia;
            if ($request->tipo_colacion=="ORDINARIO") {
                $ordinarios=Cronograma::where('tipo_colacion',$request->tipo_colacion)
                ->where('idUnidad',$idUnidad)
                ->where('idDependencia',$idDependencia)
                ->where('idDependencia',$idDependencia)
                ->whereBetween('fecha_colacion', [$inicio, $fin])
                ->get();
                if (count($ordinarios)>5) {
                    return response()->json($cronograma, 200);
                }
            }

            $cronograma=new Cronograma();
            $cronograma->idDependencia=$request->idDependencia;
            $cronograma->idUnidad=$request->idUnidad;
            $cronograma->idTipo_tramite_unidad=$request->idTipo_tramite_unidad;
            $cronograma->fecha_cierre_alumno=$request->fecha_cierre_alumno;
            $cronograma->fecha_cierre_secretaria=$request->fecha_cierre_secretaria;
            $cronograma->fecha_cierre_decanato=$request->fecha_cierre_decanato;
            $cronograma->tipo_colacion=$request->tipo_colacion;
            $cronograma->fecha_colacion=$request->fecha_colacion;
            $cronograma->estado=1;
            $cronograma->save();

            $cronograma=Cronograma::select('cronograma_carpeta.*','dependencia.nombre as dependencia','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite')
            ->join('dependencia','cronograma_carpeta.idDependencia','dependencia.idDependencia')
            ->join('tipo_tramite_unidad','cronograma_carpeta.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
            ->join('unidad','cronograma_carpeta.idUnidad','unidad.idUnidad')
            ->where('cronograma_carpeta.idCronograma_carpeta',$cronograma->idCronograma_carpeta)
            ->first();
            DB::commit();
            return response()->json($cronograma, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }  
    
    public function update(Request $request,$id){
        DB::beginTransaction();
        try {
            $cronograma=Cronograma::find($id);
            $cronograma->idDependencia=$request->idDependencia;
            $cronograma->idUnidad=$request->idUnidad;
            $cronograma->idTipo_tramite_unidad=$request->idTipo_tramite_unidad;
            $cronograma->fecha_cierre_alumno=$request->fecha_cierre_alumno;
            $cronograma->fecha_cierre_secretaria=$request->fecha_cierre_secretaria;
            $cronograma->fecha_cierre_decanato=$request->fecha_cierre_decanato;
            $cronograma->fecha_cierre_uraa=$request->fecha_cierre_uraa;
            $cronograma->fecha_colacion=$request->fecha_colacion;
            $cronograma->estado=1;
            $cronograma->update();
            DB::commit();
            return response()->json(['status' => '200', 'message' =>"Cronograma editado correctamente"], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }  
}


