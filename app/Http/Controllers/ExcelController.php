<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Tramite;
use App\Historial_Estado;
use Illuminate\Http\Request;
use App\Exports\CarnetsSolicitadosExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
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
            $fecha = Carbon::parse(Carbon::now());
            $afecha = $fecha->year;
            $descarga=Excel::download(new CarnetsSolicitadosExport, '004_ESTUDIANTES_CARNE_'.$fecha->year.'.xlsx');
            DB::commit();
            return $descarga;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
}
