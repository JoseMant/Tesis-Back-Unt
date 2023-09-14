<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocenteSGAController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }


    public function store(Request $request)
    {
        
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            $usuario = User::findOrFail($idUsuario);
            // return $request->all();
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_tramite_unidad',$request->idTipo_tramite_unidad)->first();
            
            
            
            $tramite=new Tramite;
            //AÑADIMOS EL NÚMERO DE TRÁMITE
            $inicio=date('Y-m-d')." 00:00:00";
            $fin=date('Y-m-d')." 23:59:59";
            $last_tramite=Tramite::whereBetween('created_at', [$inicio , $fin])->where('idTipo_tramite_unidad','!=',37)
            ->orderBy("created_at","DESC")->first();
            
            if ($last_tramite) {
                $correlativo=(int)(substr($last_tramite->nro_tramite,0,4));
                $correlativo++;
                if ($correlativo<10) $tramite->nro_tramite = "000".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                else if ($correlativo<100) $tramite->nro_tramite = "00".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                else if ($correlativo<1000) $tramite->nro_tramite = "0".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                else $tramite->nro_tramite = $correlativo.date('d').date('m').substr(date('Y'),2,3);
            }else{
                $tramite -> nro_tramite="0001".date('d').date('m').substr(date('Y'),2,3);
            }
            
            // REGISTRAMOS EL DETALLE DEL TRÁMITE REGISTRADO
            $tramite_detalle = new Tramite_Detalle();
            $tramite_detalle->save();
            $tipo_tramite = Tipo_Tramite::select('tipo_tramite.idTipo_tramite','tipo_tramite.descripcion','tipo_tramite.filename')->join('tipo_tramite_unidad', 'tipo_tramite_unidad.idTipo_tramite', 'tipo_tramite.idTipo_tramite')
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad', $request->idTipo_tramite_unidad)->first();
            // REGISTRAMOS EL TRÁMITE
            $tramite -> idTramite_detalle=$tramite_detalle->idTramite_detalle;
            $tramite -> idTipo_tramite_unidad=trim($request->idTipo_tramite_unidad);
            $tramite -> idUsuario=$idUsuario;
            $tramite -> idUnidad=trim($request->idUnidad);
            $tramite -> idDependencia=trim($request->idDependencia);
            $tramite -> idPrograma=trim($request->idPrograma);
            $tramite -> comentario=trim($request->comentario);
            $tramite -> idUsuario_asignado=null;
            $tramite -> idEstado_tramite=2;

            // Creando un uudi para realizar el llamado a los trámites por ruta

                // Verificando que no haya un uuid ya guardado en bd
                $tramiteUUID=true;
                while ($tramiteUUID) {
                    $uuid=Str::orderedUuid();
                    $tramiteUUID=Tramite::where('uuid',$uuid)->first();
                }
                $tramite -> uuid=$uuid;    
            $tramite -> save();

            // REGISTRAMOS LOS REQUISITOS DEL TRÁMITE REGISTRADO
            if($request->hasFile("files")){
                foreach ($request->file("files") as $key => $file) {
                    $requisito=json_decode($request->requisitos[$key],true);
                    $tramite_requisito=new Tramite_Requisito;
                    $tramite_requisito->idTramite=$tramite->idTramite;
                    $tramite_requisito->idRequisito=$requisito["idRequisito"];
                    $nombre = $dni.".".$file->guessExtension();
                    if ($tipo_tramite_unidad->idTipo_tramite==5) {
                        $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"]."/".$nombre;
                    }else {
                        $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$requisito["nombre"]."/".$nombre;
                    }
                    if ($file->getClientOriginalName()!=="vacio.kj") {
                        if($file->guessExtension()==$requisito["extension"]){
                            if ($tipo_tramite->idTipo_tramite==5) {
                                $file->storeAs("/public"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"], $nombre);
                            }else {
                                $file->storeAs("/public"."/".$tipo_tramite->filename."/".$requisito["nombre"], $nombre);
                            }
                            $tramite_requisito->archivo = $nombreBD;
                        }else {
                            DB::rollback();
                            return response()->json(['status' => '400', 'message' => "Subir ".$requisito["nombre"]." en ".$requisito["extension"]], 400);
                        }
                    }
                    $tramite_requisito -> save();
                }
            }

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estado=$this->setHistorialEstado($tramite->idTramite, null, 1, $idUsuario);
            $historial_estado->save();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estado=$this->setHistorialEstado($tramite->idTramite, 1, 2, $idUsuario);
            $historial_estado->save();

            DB::commit();
            return response()->json(['status' => '200', 'usuario' => 'Trámite registrado correctamente'], 200);
        
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function setHistorialEstado($idTramite, $idEstado_actual, $idEstado_nuevo, $idUsuario)
    {
        $historial_estados = new Historial_Estado;
        $historial_estados->idTramite = $idTramite;
        $historial_estados->idEstado_actual = $idEstado_actual;
        $historial_estados->idEstado_nuevo = $idEstado_nuevo;
        $historial_estados->idUsuario = $idUsuario;
        $historial_estados->fecha = date('Y-m-d h:i:s');
        return $historial_estados;
    }
}
