<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Tramite;
use App\Historial_Estado;
use Illuminate\Http\Request;
use App\Exports\TramitesExport;
use Maatwebsite\Excel\Facades\Excel;
class ExcelController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('jwt');
    // }
    public function export()
    {
        DB::beginTransaction();
        try {
            

            $descarga=Excel::download(new TramitesExport, '004_ESTUDIANTES_CARNE_2022.xlsx');

            $tramites = Tramite::join('usuario','tramite.idUsuario','usuario.idUsuario')
            ->where('tramite.idEstado_tramite',25)
            ->get();
            if (count($tramites)>0) {
                foreach ($tramites as $key => $tramite) {
                   //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                   $historial_estados=new Historial_Estado;
                   $historial_estados->idTramite=$tramite->idTramite;
                   $historial_estados->idUsuario=2;
                   $historial_estados->idEstado_actual=25;
                   $historial_estados->idEstado_nuevo=26;
                   $historial_estados->fecha=date('Y-m-d h:i:s');
                   $historial_estados->save();
                   $tramite->idEstado_tramite=26;
                   $tramite->update();
                }
            }else {
                return response()->json(['status' => '400', 'message' => "No hay trámites para exportar"], 400);
            }
            DB::commit();
            return $descarga;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
        // $tramitesExports=new TramitesExport;
        // return $tramitesExports->download('invoices.xlsx');
    }
}
