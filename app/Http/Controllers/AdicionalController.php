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

    public function anularCarnes() {
        $idsTramites = [2146,
        2155,
        1934,
        1872,
        1952,
        1701,
        1595,
        1694,
        1619,
        1578,
        1805,
        1581,
        1628,
        1704,
        2109,
        1871,
        1702,
        1716,
        1881,
        1882,
        1885,
        2082];
        foreach ($idsTramites as $key => $idTramite) {
            $historial_estado = new Historial_Estado;
            $historial_estado->idEstado_actual = 26;
            $historial_estado->idEstado_nuevo = 29;
            // $historial_estado->fecha = date('Y-m-d');
            $historial_estado->idTramite = $idTramite;
            $historial_estado->idUsuario = 2;
            $historial_estado->save();
            $tramite = Tramite::where('idTramite', $idTramite)->first();
            $tramite->idEstado_tramite = 29;
            $tramite->save();

        }
    }
    public function chancarArchivo(Request $request){
        if ($request->hasFile("archivo")) {
            //obtenemos el archivo de la resolución a chancar
            $file=$request->file("archivo");
            //obtenemos todos los trámites a los que se les va a chancar
            $ingenieria=Tramite::select('tramite.idTramite','usuario.nro_documento')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16);
            })
            ->where('tramite.idEstado_tramite','!=',29)
            ->where('tramite.idDependencia',13)
            ->where('cronograma_carpeta.fecha_colacion','2023-04-28')
            ->get();
            // return count($ingenieria);
            //Recorremos los trámites y chancamos cada uno la resolución
            foreach ($ingenieria as $key => $tramite) {
                $requisito=Tramite_Requisito::where('idTramite',$tramite->idTramite)
                ->where(function($query)
                {
                    $query->where('idRequisito',21)
                    ->orWhere('idRequisito',31);
                })
                ->first();
                $nombre=$tramite->nro_documento.'.pdf';
                if ($requisito->idRequisito==21) {
                    $file->storeAs('public/elaboracion_carpeta/GRADO DE BACHILLER/RESOLUCION DE DECANATO', $nombre);
                }else if($requisito->idRequisito==31){
                    $file->storeAs('public/elaboracion_carpeta/TÍTULO PROFESIONAL/RESOLUCION DE DECANATO', $nombre);
                }
            }
        }
        
        // CÓDIGO PARA CHANCAR EXONERADO
        // if ($request->hasFile("archivo")) {
        //     // GUARDAMOS EL ARCHIVO DEL EXONERADO
        //     $file=$request->file("archivo");
        //     $nombre = $file->getClientOriginalName();
        //     // $nombreBD = "/storage/exonerados/".$nombre;
        //     if($file->guessExtension()=="pdf"){
        //       $file->storeAs('public/elaboracion_carpeta/TÍTULO PROFESIONAL/RESOLUCION DE DECANATO', $nombre);
        //     //   $tramite->exonerado_archivo = $nombreBD;
        //     }
        // }
    }

    public function separarApellidos(){
        DB::beginTransaction();
        try {
            $usuarios=User::where('apellido_paterno',null)->get();
            // return count($usuarios);
            // $apellidos=[];
            foreach ($usuarios as $key => $usuario) {
                try {
                    $apellidos=explode(" ", $usuario->apellidos, 2);
                    $usuario->apellido_paterno= $apellidos[0];
                    $usuario->apellido_materno=$apellidos[1];
                    // return $usuario;
                    $usuario->update();
                } catch (\Throwable $th) {
                    //throw $th;
                }
                // $apellidos=explode(" ", $usuario->apellidos, 2);
                // $usuario->apellido_paterno= $apellidos[0];
                // $usuario->apellido_materno=$apellidos[1];
                // // return $usuario;
                // $usuario->update();
                
                // 164020323
            }
            DB::commit();
            return response()->json(200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
}
