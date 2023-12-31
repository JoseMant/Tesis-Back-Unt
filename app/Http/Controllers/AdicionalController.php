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
use App\Diploma_Carpeta;
use App\Historial_Codigo_Diploma;
use App\Graduado;
use File;
use ZipArchive;
class AdicionalController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['createCodeDiploma']]);
    }
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
        // return $request->all();
        if ($request->hasFile("archivo")) {
            //obtenemos el archivo de la resolución a chancar
            $file=$request->file("archivo");
            //obtenemos todos los trámites a los que se les va a chancar
            $tramites=Tramite::select('tramite.idTramite','usuario.nro_documento','tramite.idTipo_tramite_unidad')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','tramite.idDependencia','dependencia.idDependencia')
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('tramite.idEstado_tramite','!=',29)
            ->where(function($query) use($request)
            {
                $query->where('tramite.idDependencia',$request->dependencia)
                ->orWhere('dependencia.idDependencia2',$request->dependencia);
            })
            ->where('cronograma_carpeta.fecha_colacion',$request->colacion)
            ->get();
            // return count($tramites);
            //Recorremos los trámites y chancamos cada uno la resolución
            foreach ($tramites as $key => $tramite) {
                $tramite->requisito=Tramite_Requisito::where('idTramite',$tramite->idTramite)
                ->where(function($query)
                {
                    $query->where('idRequisito',21)
                    ->orWhere('idRequisito',31)
                    ->orWhere('idRequisito',68);
                })
                ->first();
                $archivo=explode("/", $tramite->requisito->archivo, 6);
                // $tramite->requisito->archivo=substr($tramite->requisito->archivo,9,-13);
                $tramite->requisito->archivo=$archivo[2].'/'.$archivo[3].'/'.$archivo[4];

                $nombre=$tramite->nro_documento.'.pdf';
                $file->storeAs('/public//'.$tramite->requisito->archivo, $nombre);

                // if ($tramite->requisito->idRequisito==21) {
                //     $file->storeAs('/public//'.$tramite->requisito->archivo, $nombre);
                // }else if($tramite->requisito->idRequisito==31){
                //     $file->storeAs('/public//'.$tramite->requisito->archivo, $nombre);
                // }else if($tramite->requisito->idRequisito==68){
                //     $file->storeAs('/public//'.$tramite->requisito->archivo, $nombre);
                // }
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

    public function setValuesProgramasTramite(){
        DB::beginTransaction();
        try {
            $tramites=Tramite::all();
            foreach ($tramites as $tramite) {
                if ($tramite->idUnidad == 1 && $tramite->idDependencia_detalle <= 49) {
                    $tramite->idPrograma=$tramite->idDependencia_detalle;
                } else if ($tramite->idUnidad == 1 && $tramite->idDependencia_detalle == 51) { 
                    $tramite->idPrograma=50;
                } else if ($tramite->idUnidad == 1 && $tramite->idDependencia_detalle == 52) { 
                    $tramite->idPrograma=51;
                } else if ($tramite->idUnidad == 4) { 
                    $tramite->idPrograma = $tramite->idDependencia_detalle + 51;
                }
                $tramite->save();
            }
            DB::commit();
            return response()->json(['status' => '200', 'message' => 'OK'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function setValuesProgramasDiplomaCarpeta(){
        DB::beginTransaction();
        try {
            $diplomas=Diploma_Carpeta::all();
            foreach ($diplomas as $diploma) {
                if ($diploma->idUnidad == 1 && $diploma->idDependencia_detalle <= 49) {
                    $diploma->idPrograma=$diploma->idDependencia_detalle;
                } else if ($diploma->idUnidad == 1 && $diploma->idDependencia_detalle == 51) { 
                    $diploma->idPrograma=50;
                } else if ($diploma->idUnidad == 1 && $diploma->idDependencia_detalle == 52) { 
                    $diploma->idPrograma=51;
                } else if ($diploma->idUnidad == 4) { 
                    $diploma->idPrograma = $diploma->idDependencia_detalle + 51;
                }
                $diploma->save();
            }
            DB::commit();
            return response()->json(['status' => '200', 'message' => 'OK'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function firmar(){
        DB::beginTransaction();
        try {
            $tramites=Tramite::select('tramite.idTramite_detalle',
            DB::raw("(case 
                    when tramite.idUnidad = 1 then (select idUsuario from usuario where idTipo_usuario=6 and idDependencia=tramite.idDependencia and estado=1) 
                    when tramite.idUnidad = 4 then  (select idUsuario from usuario where idTipo_usuario=6 and idDependencia=dependencia.idDependencia2 and estado=1)
                end) as idDecano"))
            ->join('dependencia','tramite.idDependencia','dependencia.idDependencia')
            ->where('tramite.idEstado_tramite',44)
            ->where('tramite.idTipo_tramite_unidad',34)
            ->get();
            $idRector=User::where('idTipo_usuario',12)->where('estado',1)->pluck('idUsuario')->first();
            $idSec_general=User::where('idTipo_usuario',10)->where('estado',1)->pluck('idUsuario')->first();
            foreach ($tramites as $tramite) {
                $tramite_detalle=Tramite_Detalle::where('idTramite_detalle',$tramite->idTramite_detalle)->first();
                $tramite_detalle->autoridad1=$idRector;
                $tramite_detalle->autoridad2=$idSec_general;
                $tramite_detalle->autoridad3=$tramite->idDecano;
                $tramite_detalle;
                $tramite_detalle->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function diploma_carpeta(Request $request){
        $diplomas=Diploma_carpeta::get();
        foreach ($diplomas as $key => $diploma) {
            $diploma->idPrograma=$diploma->idDependencia_detalle;
            $diploma->save();
        }
    }

    public function setValuesUuid(){
        DB::beginTransaction();
        try {
            $tramites=Tramite::where('uuid', null)->get();
            foreach ($tramites as $tramite) {
                $tramiteUUID=true;
                while ($tramiteUUID) {
                    $uuid=Str::orderedUuid();
                    $tramiteUUID=Tramite::where('uuid', $uuid)->first();
                }
                $tramite -> uuid=$uuid;
                $tramite->save();
            }
            DB::commit();
            return response()->json(['status' => '200', 'message' => 'OK'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }


    public function createCodeDiploma(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            // $token = JWTAuth::getToken();
            // $apy = JWTAuth::getPayload($token);
            // $idUsuario=$apy['idUsuario'];
            
            $tramites=Tramite::select('usuario.apellidos', 'usuario.nombres','tramite.idTramite_detalle')->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa','programa.idPrograma','tramite.idPrograma')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',44)
            ->where('tramite.idTipo_tramite_unidad',15)
            ->where('resolucion.idResolucion',45)
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->get(); 


            $codigoInicial='00047533';
            foreach ($tramites as $key=>$tramite) {
                $codigo=$codigoInicial+$key+1;
                $tamCodigo=strlen($codigo);
                switch ($tamCodigo) {
                    case 1:
                        $codigo="0000000".$codigo;
                        break;
                    case 2:
                        $codigo="000000".$codigo;
                        break;
                    case 3:
                        $codigo="00000".$codigo;
                        break;
                    case 4:
                        $codigo="0000".$codigo;
                        break;
                    case 5:
                        $codigo="000".$codigo;
                        break;
                    case 6:
                        $codigo="00".$codigo;
                        break;
                    case 7:
                        $codigo="0".$codigo;
                        break;
                }
                $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                $tramite_detalle->codigo_diploma="G".$codigo;
                $tramite_detalle->save();
            }
            // return $tramites;
            DB::commit();
            return response()->json( 'ok',200);


            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function createHistorialCodeDiploma(Request $request){
        DB::beginTransaction();
        try {

            $tramites=Tramite::select('tramite.idTramite','tramite_detalle.codigo_diploma')   
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->where('tramite.idEstado_tramite',44)
            ->get(); 
            
            foreach ($tramites as $tramite) {
                $historial_codigos = Historial_Codigo_Diploma::where('idTramite', $tramite->idTramite)->get();
                if(count($historial_codigos) == 0)
                {
                    $historial_estado=Historial_Estado::where('idTramite',$tramite->idTramite)->where('idEstado_nuevo',44)->first();
                    $newHistorial = new Historial_Codigo_Diploma;
                    $newHistorial->idTramite = $tramite->idTramite;
                    $newHistorial->codigo_diploma_before = 'NINGUNO';
                    $newHistorial->codigo_diploma_after = $tramite->codigo_diploma;
                    $newHistorial->descripcion = 'NUEVO CÓDIGO';
                    $newHistorial->idUsuario = $historial_estado->idUsuario;
                    $newHistorial->fecha_historial = substr($historial_estado->fecha,0,10);
                    $newHistorial->save();
                }
            }

               
            // return $tramites;
            DB::commit();
            return response()->json( 'ok',200);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function getGraduado(){
        return Graduado::join('alumno','alumno.Cod_alumno','graduado.cod_alumno')->where('Nro_documento','75411199')->first();
    }

    public function fotosIngresantes2023(){
        DB::beginTransaction();
        try {
            $ingresantes=Tramite::select('usuario.nro_documento','tramite.nro_matricula','tramite_requisito.archivo')
            ->join('usuario','tramite.idUsuario','usuario.idUsuario')
            ->join('tramite_requisito','tramite.idTramite','tramite_requisito.idTramite')
            ->where('tramite.idTipo_tramite_unidad',17)
            ->where('tramite.nro_matricula','like','%23')
            ->get();

            $zip = new ZipArchive;
            $nameZip = "FOTOS INGRESANTES 2023.zip";

            // Variable para el nombre de los archivos
            $filename="";
            // Eliminamos el zip creado de la descarga de hoy(si es que existe) para que al momento de ser creado no se sobreescriba y tenga archivos antiguos
            if ($zip->open($nameZip)===TRUE) {
                $zip->close();
                unlink($nameZip);
            }


            if ($zip->open(public_path($nameZip),ZipArchive::CREATE) === TRUE)
            {
                foreach ($ingresantes as $ingresante) {
                    $file =public_path($ingresante->archivo);
                    
                    if ($ingresante->archivo!=null) {
                        // nombre del archivo
                        $filename = $ingresante->nro_documento.".jpg";
    
                        $zip->addFile($file,"FOTOS INGRESANTES 2023/".$filename);
                    }
                }
                $zip->close();   
            }


            DB::commit();
            return response()->download(public_path($nameZip));
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
}
