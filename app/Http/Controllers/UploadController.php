<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use File;
use ZipArchive;
use RarArchive;
use App\Tramite;
use App\Tramite_Detalle;
use App\Resolucion;
use App\User;
use App\Historial_Estado;
use Tymon\JWTAuth\Facades\JWTAuth;

class UploadController extends Controller
{
    public function uploadzip(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            // Obteniendo la resolución para el nombre del zip
            $resolucion=Resolucion::find($request->idResolucion);

            // Datos de secretaría general
            $secretariaGeneral=User::where('idUsuario',$idUsuario)->first();

            // Ruta de guardado de archivos según el tipo de resolución
            $ruta=null;
            //Trámites relacionados a esa resolución
            if ($resolucion->tipo_emision=='O') {
                $tramites=Tramite::select('tramite.idTramite','tramite.idEstado_tramite','tramite.idTipo_tramite_unidad')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
                ->where('tramite.idEstado_tramite',46)
                ->where('tramite_detalle.autoridad2',$secretariaGeneral->idUsuario)
                ->where('resolucion.idResolucion',$resolucion->idResolucion)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                ->count(); 
                // RUTA
                $ruta='public/diplomas';
            }elseif ($resolucion->tipo_emision=='D') {
                $tramites=Tramite::select('tramite.idTramite','tramite.idEstado_tramite','tramite.idTipo_tramite_unidad')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
                ->where('tramite.idEstado_tramite',46)
                ->where('tramite_detalle.autoridad2',$secretariaGeneral->idUsuario)
                ->where('resolucion.idResolucion',$resolucion->idResolucion)
                ->where(function($query)
                {
                    $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                    ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
                })
                ->count(); 

                // RUTA
                $ruta='public/diplomas_duplicados';
            }
            
            $resolucion_name=explode("/", $resolucion->nro_resolucion, 2);
            $fileName = $resolucion_name[0]."-".$resolucion_name[1].".zip";

            // Guardando el archivo zip en la misma carpeta de los "diplomas"
            if($request->hasFile("archivo")){
                $file=$request->file("archivo");
                $nombre = $file->getClientOriginalName();
                // Validadndo que el nombre del archivo zip sea el correcto
                if ($nombre!=$fileName) {
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' =>"Esta subiendo el archivo incorrecto, debe ser: ".$fileName], 400);
                }
                // Guardando el zip en la carpeta temporal para luego ser utilizada
                if($file->guessExtension()=="zip"){
                    $file->storeAs($ruta, $nombre);
                }
            }
            // Extrayendo los datos del zip para poder sobreescribir a los que ya están en la carpeta "diplomas" 
            $zip = new ZipArchive;
            if ($zip->open(storage_path('app/'.$ruta.'/'.$nombre)) === TRUE) //en la función open se le pasa la ruta de nuestro archivo (alojada en carpeta temporal)
            {
                // Validando que la cantidad de archivos sea igual al de trámites
                if ($tramites>$zip->numFiles) {
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' =>"La cantidad de archivos es MENOR a la que cantidad de diplomas firmados que se espera."], 400);
                }elseif ($tramites<$zip->numFiles) {
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' =>"La cantidad de archivos es MAYOR a la que cantidad de diplomas firmados que se espera."], 400);
                }

                // Validando que cada archivo esté almacenado su nombre en la bd, renombrando cada archivo quitando la parte "_firmado" y actualizando el estado de los trámites 
                for ($i=0; $i<$zip->numFiles;$i++) {
                    $nombreZip=substr($zip->getNameIndex($i),0,-12);
                    $zip->renameIndex($i,$nombreZip.'.pdf');

                    $arrayNombre=explode("_", $zip->getNameIndex($i), 3);
                    $nro_documento=$arrayNombre[1];

                    if ($resolucion->tipo_emision=='O') {
                        // Verificando si el archivo es bachiller, título o seg, ya que en carpetas regulares puede darse el caso de solicitar grado y título al mismo tiempo
                        $tipo_tramite_unidad=substr($zip->getNameIndex($i),-5,1);

                        if ($tipo_tramite_unidad=='B') {
                            $tipo_tramite_unidad=15;
                        }elseif ($tipo_tramite_unidad=='T') {
                            $tipo_tramite_unidad=16;
                        }elseif($tipo_tramite_unidad=='S'){
                            $tipo_tramite_unidad=34;
                        }
                        
                        // Obteniendo el trámite
                        $tramite = Tramite::join('usuario','usuario.idUsuario','tramite.idUsuario')
                        ->where('usuario.nro_documento',$nro_documento)
                        ->where('tramite.idTipo_tramite_unidad',$tipo_tramite_unidad)
                        ->where('tramite.idEstado_tramite', 46)
                        ->first();
                        
                    }elseif ($resolucion->tipo_emision=='D') {
                        $tramite = Tramite::join('usuario','usuario.idUsuario','tramite.idUsuario')
                        ->join('tipo_tramite_unidad','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
                        ->where('usuario.nro_documento',$nro_documento)
                        ->where('idEstado_tramite', 46)
                        ->whereIn('tipo_tramite_unidad.idTipo_tramite',[6,9])
                        ->first();
                    }

                    $tramite_detalle=Tramite_Detalle::where('nombre_descarga_sunedu',$nombreZip)->where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)->first();                    
                    
                    if ($tramite_detalle) {

                        // CAMBIANDO EL ESTADO DEL TRÁMITE
                        $historial_estado=new Historial_Estado;
                        $historial_estado->idTramite=$tramite->idTramite;
                        $historial_estado->idUsuario=$secretariaGeneral->idUsuario;
                        $historial_estado->idEstado_actual=$tramite->idEstado_tramite;
                        $historial_estado->idEstado_nuevo=47;
                        $historial_estado->fecha=date('Y-m-d h:i:s');
                        $historial_estado->save();
                        
                        $historial_estados=new Historial_Estado;
                        $historial_estados->idTramite=$tramite->idTramite;
                        $historial_estados->idUsuario=$idUsuario;
                        $historial_estados->idEstado_actual=47;
                        $historial_estados->idEstado_nuevo=44;
                        $historial_estados->fecha=date('Y-m-d h:i:s');
                        $historial_estados->save();
                        
                        $tramite->idEstado_tramite=44;
                        $tramite->save();
                    } else {
                        DB::rollback();
                        return response()->json(['status' => '400', 'message' =>"El archivo ".$nombreZip." no corresponde a la resolución."], 400);
                    }
                }
                $zip->close();
            }
            // Abriendo nuevamente el zip actualizado con los nuevos nombres y extrayendo para que se chanquen los firmados digitalmente
            if ($zip->open(storage_path('app/'.$ruta.'/'.$nombre)) === TRUE) //en la función open se le pasa la ruta de nuestro archivo (alojada en carpeta temporal)
            {
                $zip->extractTo(storage_path('app/'.$ruta)); 
                $zip->close();

                // Eliminando el zip almacenado en el storage
                unlink(storage_path('app/'.$ruta.'/'.$nombre));
            }

            if ($resolucion->tipo_emision=='O') {
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
                'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad2', 'tramite.idTipo_tramite_unidad', 
                'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa', 'tipo_tramite_unidad.descripcion as tramite',
                DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'resolucion.idResolucion')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
                ->where('tramite_detalle.autoridad2',$idUsuario)
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                ->orderBy('tramite.idTipo_tramite_unidad','asc')
                ->orderBy('dependencia.nombre','asc')
                ->orderBy('programa.nombre','asc')
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->get(); 
            }elseif ($resolucion->tipo_emision=='D') {
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
                'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad2', 'tramite.idTipo_tramite_unidad', 
                'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa', 'tipo_tramite_unidad.descripcion as tramite',
                DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'resolucion.idResolucion')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
                ->where('tramite_detalle.autoridad2',$idUsuario)
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->where(function($query)
                {
                    $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                    ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
                })
                ->orderBy('tramite.idTipo_tramite_unidad','asc')
                ->orderBy('dependencia.nombre','asc')
                ->orderBy('programa.nombre','asc')
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->get(); 
            }


            DB::commit();
            return response()->json($tramites,200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }



        

        
    }
}
