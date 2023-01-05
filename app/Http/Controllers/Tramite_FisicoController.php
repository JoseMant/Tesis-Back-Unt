<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Tipo_Tramite;
use App\Tipo_Tramite_Unidad;
use App\Historial_Estado;
use App\Tramite_Requisito;
use App\User;
use App\Tramite_Detalle;
use App\Estado_Tramite;
use App\Jobs\RegistroTramiteJob;
use App\Jobs\ActualizacionTramiteJob;
use App\Jobs\ObservacionTramiteJob;
use App\Jobs\FinalizacionCarnetJob;
use App\Jobs\NotificacionCertificadoJob;
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
use App\Tramite_Fisico;

class Tramite_FisicoController extends Controller
{
        //tramite fisico *************************************************
        public function PostTramiteFisicoByUser(Request $request){
            DB::beginTransaction();
            try{
                // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
                $token = JWTAuth::getToken();
                $apy = JWTAuth::getPayload($token);
                $idUsuario=$apy['idUsuario'];
                $dni=$apy['nro_documento'];
                $usuario = User::findOrFail($idUsuario);
                //idvouche exonerado firma idUsaurio_asignado idTramite_detalle
                $tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_tramite_unidad',$request->idTipo_tramite_unidad)->first();
                if ($tipo_tramite_unidad->idTipo_tramite==2) {
                    if ($request->idUnidad==1) {
                        $alumnoSUV=PersonaSuv::join('matriculas.alumno','matriculas.alumno.idpersona','persona.idpersona')
                        ->where('alu_estado',6)->Where('per_dni',$dni)->first();
                        if (!$alumnoSUV) {
                            $alumnoSGA=PersonaSga::join('perfil','persona.per_id','perfil.per_id')
                            ->join('sga_datos_alumno','sga_datos_alumno.pfl_id','perfil.pfl_id')
                            ->Where('sga_datos_alumno.con_id',6)
                            ->Where('per_dni',$dni)
                            ->first();
                            if (!$alumnoSGA) {
                                return response()->json(['status' => '400', 'message' => 'Usted no se encuentra registrado como egresado para realizar este trámite. Coordinar con tu secretaria de escuela para actualizar tu condición.'], 400);
                            }
                        }
                    }elseif ($request->idUnidad==2) {
                        
                    }elseif ($request->idUnidad==3) {
                        
                    }else {
                       
                    }
                }
                $tramite=new Tramite_Fisico;
                $inicio=date('Y-m-d')." 00:00:00";
                $fin=date('Y-m-d')." 23:59:59";
                $last_tramite=Tramite_Fisico::whereBetween('created_at', [$inicio , $fin])->orderBy("created_at","DESC")->first();
                if ($last_tramite) {
                    $correlativo=(int)(substr($last_tramite->nro_tramite,0,3));
                    $correlativo++;
                    if ($correlativo<10) {
                        $tramite -> nro_tramite="00".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                    } elseif($correlativo<100){
                        $tramite -> nro_tramite="0".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                    }else{
                        $tramite -> nro_tramite=$correlativo.date('d').date('m').substr(date('Y'),2,3);
                    }
                }else{
                    $tramite -> nro_tramite="001".date('d').date('m').substr(date('Y'),2,3);
                }
                $tipo_tramite = Tipo_Tramite::select('tipo_tramite.idTipo_tramite','tipo_tramite.descripcion','tipo_tramite.filename')->join('tipo_tramite_unidad', 'tipo_tramite_unidad.idTipo_tramite', 'tipo_tramite.idTipo_tramite')
                ->where('tipo_tramite_unidad.idTipo_tramite_unidad', $request->idTipo_tramite_unidad)->first();
                $tramite_detalle=new Tramite_Detalle();

                switch ($tipo_tramite->idTipo_tramite) {
                    case 1:
                        $tramite_detalle->idCronograma_carpeta = null;
                        $tramite_detalle->idModalidad_carpeta=null;
                        $tramite_detalle->idMotivo_certificado=2;//trim($request->idMotivo_certificado);
                        break;
                        /*
                    case 2:
                        if ($request->idCronograma_carpeta!=null) {
                            $tramite_detalle->idCronograma_carpeta = trim($request->idCronograma_carpeta);
                        }else {
                            DB::rollback();
                            return response()->json(['status' => '400', 'message' => "Seleccionar una fecha de colación"], 400);
                        }
                        // $tramite_detalle->idModalidad_titulo_carpeta=1;//trim($request->idModalidad_titulo_carpeta);//por defecto null por ahora
                        $tramite_detalle->idMotivo_certificado=null;
                        break;
                    case 3:
                        $tramite_detalle->idCronograma_carpeta = null;
                        $tramite_detalle->idModalidad_carpeta=null;
                        $tramite_detalle->idMotivo_certificado=null;
                        break;*/
                }
                $tramite_detalle->save();

                $tramite -> idTramite_detalle=$tramite_detalle->idTramite_detalle;
                $tramite -> idTipo_tramite_unidad=trim($request->idTipo_tramite_unidad);
                $tramite -> idUsuario=$idUsuario;
                $tramite -> idUnidad=trim($request->idUnidad);
                $tramite -> idDependencia=trim($request->idDependencia);
                $tramite -> idDependencia_detalle=trim($request->idDependencia_detalle);
                $tramite -> nro_matricula=trim($request->nro_matricula);
                $tramite -> comentario=trim($request->comentario);
                $tramite -> sede=trim($request->sede);
                $tramite -> idEstado_tramite=2;

                $tramite -> save();
                

                //dispatch(new RegistroTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
                DB::commit();
                return response()->json(['status' => '200', 'fut'=>"fut_fisico/".$tramite->idTramite_fisico], 200);
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['status' => '400', 'message' => $e], 400);
            } 
        }
    
}
