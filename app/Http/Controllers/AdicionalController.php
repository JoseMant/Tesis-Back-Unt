<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Mail\Mailable;
use App\Tramite;
use App\Tipo_Tramite;
use App\Tipo_Tramite_Unidad;
use App\Historial_Estado;
use App\Tramite_Requisito;
use App\Voucher;
use App\User;
use App\Tramite_Detalle;
use App\Estado_Tramite;
use App\Jobs\RegistroTramiteJob;
use App\Jobs\ActualizacionTramiteJob;
use App\Jobs\ObservacionTramiteJob;
use App\Jobs\FinalizacionCarnetJob;
use App\Jobs\NotificacionCertificadoJob;
use App\Jobs\NotificacionCarpetaJob;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use App\Imports\TramitesImport;
use App\Exports\TramitesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\PersonaSE;

use App\Mencion;
use App\Escuela;
use App\Motivo_Certificado;
use App\PersonaSuv;
use App\PersonaSga;

class AdicionalController extends Controller
{
    public function eliminarHistorial(){
        try {
            // return "hola";
            $tramites=Tramite::select('tramite.idTramite','tramite.idEstado_tramite')
            ->join('tramite_detalle' ,'tramite.idTramite_detalle','tramite_detalle.idTramite_detalle')
            ->join('cronograma_carpeta' , 'tramite_detalle.idCronograma_carpeta','cronograma_carpeta.idCronograma_carpeta')
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16);
            })
            ->where('tramite.idEstado_tramite',20)
            ->where('tramite.idDependencia',1)
            ->get();
            foreach ($tramites as $key => $tramite) {
                $historiales=Historial_Estado::where('idTramite',$tramite->idTramite)->where('idEstado_actual','>=',17)->get();
                foreach ($historiales as $key => $historial) {
                    // $historial=Historial_Estado::find($historial->idHistorial_estado);
                    $historial->delete();
                }
                $tramite->idEstado_tramite = 17;
                $tramite->save();
            }
            
            DB::commit();
            return response()->json(200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
    public function getFecha(){
        return $inicio=date('Y-m-d')." 00:00:00";
    }
    public function rechazar(){
        try {
            $correlativo = 2000;
            $inicio="2023-04-19 00:00:00";
            $fin="2023-04-19 23:59:59";
            $tramites=Tramite::whereBetween('created_at', [$inicio , $fin])
            // ->where('idEstado_tramite','!=',29)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',17)
                ->orWhere('tramite.idTipo_tramite_unidad',18)
                ->orWhere('tramite.idTipo_tramite_unidad',30);
            })
            ->orderBy("created_at","DESC")->get();
            foreach ($tramites as $key => $tramite) {
                $correlativo++;
                $tramite->nro_tramite = $correlativo.'190423';
                $tramite->save();
            }
            DB::commit();
            return response()->json(200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
}
