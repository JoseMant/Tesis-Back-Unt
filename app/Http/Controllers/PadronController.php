<?php

namespace App\Http\Controllers;

use App\Exports\HojaPadronOficio;
use Illuminate\Support\Facades\DB;

use App\Oficio;
use App\Resolucion;
use Illuminate\Http\Request;
use App\Exports\PadronSuneduExport;
use App\Exports\PadronSuneduOficioExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CorrecionSuneduImport;
class PadronController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['padron','padronOficio','correccion']]);
    }

    public function padron($idResolucion){
        DB::beginTransaction();
        try {
            // Verificando que el oficio exista
             $resolucion=Resolucion::find($idResolucion);
            
            if (!$resolucion) {
                return response()->json(['status' => '400', 'message' =>"La resolución ingresada no existe"], 400);
            }

            $name_resolucion=explode("/", $resolucion->nro_resolucion, 2);
            $descarga=Excel::download(new PadronSuneduExport($idResolucion), 'PADRON_SUNEDU_'.$name_resolucion[0].'.xlsx');
            return $descarga;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    //PADRON OFICIOOOO
    public function padronOficio($idOficio){
        DB::beginTransaction();
        try {
            // Verificando que el oficio exista
             $oficio=Oficio::find($idOficio);
            
            if (!$oficio) {
                return response()->json(['status' => '400', 'message' =>"El oficio ingresado no existe"], 400);
            }

            $name_oficio=explode("/", $oficio->nro_oficio, 2);
            $descarga=Excel::download(new HojaPadronOficio($idOficio), 'PADRON_SUNEDU_OFICIO_'.$name_oficio[0].'.xlsx');
            return $descarga;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }


    public function correccion(Request $request){
        DB::beginTransaction();
        try {
            $file=$request->file;
            $resolucion=Resolucion::find($request->idResolucion);
            $name_resolucion=explode("/", $resolucion->nro_resolucion,2);
            $nombre=$file->getClientOriginalName();

            if ($nombre != 'PADRON_SUNEDU_'.$name_resolucion[0].'.xlsx') {
                DB::rollback();
                return response()->json(['status' => '400', 'message' => "Archivo incorrecto, subir archivo: PADRON_SUNEDU_".$name_resolucion[0].'.xlsx'], 400);
            }
            
            $importacion = new CorrecionSuneduImport;
            $excel=Excel::import( $importacion, $request->file);
            
            DB::commit();
            return response()->json(['status' => '200'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }

            
    }
}
