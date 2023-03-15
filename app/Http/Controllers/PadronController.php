<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use App\Exports\PadronSuneduExport;
use Maatwebsite\Excel\Facades\Excel;
class PadronController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['padron']]);
    }
    public function padron($idOficio){
        DB::beginTransaction();
        try {
            $descarga=Excel::download(new PadronSuneduExport($idOficio), 'PADRON_SUNEDU.xlsx');
            return $descarga;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
}
