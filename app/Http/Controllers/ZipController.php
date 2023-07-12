<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use File;
use ZipArchive;
use App\Tramite;
use App\Tramite_Requisito;
use App\User;
use App\PersonaSuv;
use App\PersonaSga;
use App\Escuela;
use App\Historial_Estado;
use App\Resolucion;
use App\PDF_Fut;
use Illuminate\Support\Str;

use App\Http\Controllers\PDF_FutController;

class ZipController extends Controller
{
    
    public function downloadFotos()
    {
        DB::beginTransaction();
        try {
            //obtener carnets validados
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tramite.idEstado_tramite')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tipo_tramite.idTipo_tramite',3)
            ->where('tramite.idEstado_tramite',16)
            // ->where(function($query)
            // {
            //     $query->where('tramite.idEstado_tramite',7)
            //     ->orWhere('tramite.idEstado_tramite',16);
            // })
            ->get(); 
            foreach ($tramites as $key => $tramite) {
                $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
                'tramite_requisito.comentario','tramite_requisito.des_estado_requisito')
                ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
                ->where('idTramite',$tramite->idTramite)
                ->where('requisito.nombre','FOTO CARNET')
                // ->where('tramite_requisito.des_estado_requisito','PENDIENTE')
                ->get();
            }
            // return $tramites;
            $zip = new ZipArchive;
            // $date=date('Y-m-d h-i-s');
            $dateToday=date('Y-m-d');
            $fileName = 'Fotos_Carnet_'.$dateToday.'.zip';
            // $dateYesterday=date('Y-m-d',strtotime("yesterday"));

            // Eliminamos el zip creado con la descarga de días anteriores(si es que existe) para que no se guarde en el proyecto
            for ($i=1; $i <= 31; $i++) { 
                $date=date('Y-m-'.$i);
                if ($zip->open('Fotos_Carnet_'.$date.'.zip')===TRUE) {
                    $zip->close();
                    unlink('Fotos_Carnet_'.$date.'.zip');
                }
            }

            //Eliminamos el zip creado de la descarga de hoy(si es que existe) para que al momento de ser creado no se sobreescriba y tenga fotos antiguas
            if ($zip->open($fileName)===TRUE) {
                $zip->close();
                unlink($fileName);
            }
            if ($zip->open(public_path($fileName),ZipArchive::CREATE) === TRUE)
            {
                foreach ($tramites as $key => $tramite) {
                    $usuario=User::findOrFail($tramite->idUsuario);
                    foreach ($tramite->requisitos as $key => $requisito) {
                        $value =public_path($requisito->archivo);
                        if ($requisito->archivo!=null) {
                            # code...
                            $relativeNameInZipFile = basename(public_path($requisito->archivo));
                            $zip->addFile(public_path($requisito->archivo), $usuario->tipo_documento."_".$relativeNameInZipFile);
                        }
                    }
                    
                }
                $zip->close();
            }
            DB::commit();
            return response()->download(public_path($fileName));
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function backupFiles($idResolucion){
        DB::beginTransaction();
        try {
            // Obteniendo la resolución para el nombre del zip
            $resolucion=Resolucion::find($idResolucion);
            // Obteniendo los trámites de dicha resolución con los archivos de voucher y resolución
            $tramites=Tramite::select('tramite.idTramite','voucher.archivo','tramite.exonerado_archivo','tramite.idUsuario','tramite.idTipo_tramite_unidad',
            'tramite.idEstado_tramite','tramite.idUsuario','tramite_detalle.certificado_final')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('voucher','voucher.idVoucher','tramite.idVoucher')
            ->join('tramite_detalle','tramite.idTramite_detalle','tramite_detalle.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->where('tramite.idEstado_tramite',44)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('cronograma_carpeta.idResolucion',$idResolucion)
            ->get();
            
            foreach ($tramites as $key => $tramite) {
                // // finalización de trámite
                $historial_estados=new Historial_Estado;
                $historial_estados->idTramite=$tramite->idTramite;
                $historial_estados->idUsuario=$tramite->idUsuario;
                $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                $historial_estados->idEstado_nuevo=15;
                $historial_estados->fecha=date('Y-m-d h:i:s');
                $historial_estados->save();
                
                $tramite->idEstado_tramite=15;
                $tramite->save();

                // Obteniendo los requisitos de cada trámite
                $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
                'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.extension')
                ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
                ->where('idTramite',$tramite->idTramite)
                ->get();
            }

            if (count($tramites)>0) {
                // Creando el zip
                $zip = new ZipArchive;
                $resolucion=explode("/", $resolucion->nro_resolucion, 2);
                $fileName = $resolucion[0]."-".$resolucion[1].".zip";

                // Eliminamos el zip creado con la descarga de días anteriores(si es que existe) para que no se guarde en el proyecto
                for ($i=1; $i <= 31; $i++) { 
                    if ($zip->open($fileName)===TRUE) {
                        $zip->close();
                        unlink($fileName);
                    }
                }

                //Eliminamos el zip creado de la descarga de hoy(si es que existe) para que al momento de ser creado no se sobreescriba y tenga fotos antiguas
                if ($zip->open($fileName)===TRUE) {
                    $zip->close();
                    unlink($fileName);
                }

                if ($zip->open(public_path($fileName),ZipArchive::CREATE) === TRUE)
                {
                    foreach ($tramites as $key => $tramite) {
                            $usuario=User::findOrFail($tramite->idUsuario);
                            
                            // Agragando los requisitos de cada trámite al zip
                            foreach ($tramite->requisitos as $key => $requisito) {
                                $value =public_path($requisito->archivo);
                                if ($requisito->archivo!=null) {
                                    // nombre del archivo
                                    $relativeNameInZipFile = $requisito->nombre.".".$requisito->extension;
                                    // añadiendo archivos al zip 
                                    if ($tramite->idTipo_tramite_unidad==15) {
                                        $zip->addFile(public_path($requisito->archivo), "GRADO DE BACHILLER"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                    }
                                    if ($tramite->idTipo_tramite_unidad==16) {
                                        $zip->addFile(public_path($requisito->archivo), "TITULO PROFESIONAL"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                    }
                                    if ($tramite->idTipo_tramite_unidad==34) {
                                        $zip->addFile(public_path($requisito->archivo), "TITULO DE SEGUNDA ESPECIALIDAD PROFESIONAL"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                    }
                                }
                            }
                            
                            // Agregando el voucher al zip
                            if ($tramite->archivo!=null) {
                                // nombre del archivo
                                $relativeNameInZipFile ="voucher.pdf";
                                // añadiendo archivo al zip 
                                if ($tramite->idTipo_tramite_unidad==15) {
                                    $zip->addFile(public_path($tramite->archivo), "GRADO DE BACHILLER"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                }
                                if ($tramite->idTipo_tramite_unidad==16) {
                                    $zip->addFile(public_path($tramite->archivo), "TITULO PROFESIONAL"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                }
                                if ($tramite->idTipo_tramite_unidad==34) {
                                    $zip->addFile(public_path($tramite->archivo), "TITULO DE SEGUNDA ESPECIALIDAD PROFESIONAL"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                }
                            }
                            
                            // Agregando el exonerado al zip
                            if ($tramite->exonerado_archivo!=null) {
                                // nombre del archivo
                                $relativeNameInZipFile ="resolucion_exoneracion.pdf";
                                // añadiendo archivo al zip 
                                if ($tramite->idTipo_tramite_unidad==15) {
                                    $zip->addFile(public_path($tramite->exonerado_archivo), "GRADO DE BACHILLER"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                }
                                if ($tramite->idTipo_tramite_unidad==16) {
                                    $zip->addFile(public_path($tramite->exonerado_archivo), "TITULO PROFESIONAL"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                }
                                if ($tramite->idTipo_tramite_unidad==34) {
                                    $zip->addFile(public_path($tramite->exonerado_archivo), "TITULO DE SEGUNDA ESPECIALIDAD PROFESIONAL"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                }
                            }

                            // Agregando el certificado en caso lo tenga al zip
                            if ($tramite->certificado_final!=null) {
                                // nombre del archivo
                                $relativeNameInZipFile ="certificado.pdf";
                                // añadiendo archivo al zip 
                                if ($tramite->idTipo_tramite_unidad==15) {
                                    $zip->addFile(public_path($tramite->certificado_final), "GRADO DE BACHILLER"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                }
                                if ($tramite->idTipo_tramite_unidad==16) {
                                    $zip->addFile(public_path($tramite->certificado_final), "TITULO PROFESIONAL"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                }
                                if ($tramite->idTipo_tramite_unidad==34) {
                                    $zip->addFile(public_path($tramite->certificado_final), "TITULO DE SEGUNDA ESPECIALIDAD PROFESIONAL"."/".$usuario->apellidos." ".$usuario->nombres."/".$relativeNameInZipFile);
                                }
                            }
                            
                    }
                    $zip->close();   
                }

                DB::commit();
                return response()->download(public_path($fileName));
            }else {
                return response()->json(['status' => '400', 'message' =>"La resolución no tramites"], 400);
            }

            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }


        
    }

    public function downloadDiplomas($idResolucion){
        DB::beginTransaction();
        try {
            $secretariaGeneral=User::where('idTipo_usuario',10)->where('estado',true)->first();
            $secretariaGeneral->idUsuario=2; // Para pruebas,luego eliminar
        
            // Obteniendo la resolución para el nombre del zip
            $resolucion=Resolucion::find($idResolucion);
            
            // Obteniendo los trámites de dicha resolución
            $tramites=Tramite::select('tramite.idTramite','usuario.nro_documento','tipo_tramite_unidad.diploma_obtenido',
            'tramite.idTipo_tramite_unidad','tramite.idEstado_tramite')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where('tramite.idEstado_tramite',46)
            ->where('tramite_detalle.autoridad2',$secretariaGeneral->idUsuario)
            ->where('resolucion.idResolucion',$idResolucion)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->get();  
            
            // return count($tramites);
            if (count($tramites)>0) {
                // Creando el zip
                $zip = new ZipArchive;
                $resolucion=explode("/", $resolucion->nro_resolucion, 2);
                if (count($resolucion)!=2) {
                    return response()->json(['status' => '400', 'message' => "Corregir nombre de resolucion por ejemplo: 1234-4321/UNT"], 400);
                }
                $fileName = $resolucion[0]."-".$resolucion[1].".zip";
                $fileName2 = $resolucion[0]."-".$resolucion[1];

                // Eliminamos el zip creado con la descarga de días anteriores(si es que existe) para que no se guarde en el proyecto
                for ($i=1; $i <= 31; $i++) { 
                    if ($zip->open($fileName)===TRUE) {
                        $zip->close();
                        unlink($fileName);
                    }
                }

                //Eliminamos el zip creado de la descarga de hoy(si es que existe) para que al momento de ser creado no se sobreescriba y tenga fotos antiguas
                if ($zip->open($fileName)===TRUE) {
                    $zip->close();
                    unlink($fileName);
                }
                // return public_path($fileName);
                if ($zip->open(public_path($fileName),ZipArchive::CREATE) === TRUE)
                {
                    foreach ($tramites as $key => $tramite) {
                            // nombre del archivo
                            $relativeNameInZipFile = 'T004_'.$tramite->nro_documento.'_'.substr($tramite->diploma_obtenido, 0,1).'.pdf';
                            
                            // añadiendo archivos al zip 
                            if ($tramite->idTipo_tramite_unidad==15 || $tramite->idTipo_tramite_unidad==16 || $tramite->idTipo_tramite_unidad==34) {
                                $zip->addFile(storage_path('app/public').'/diplomas/T004_'.$tramite->nro_documento.'_'.substr($tramite->diploma_obtenido, 0,1).'.pdf', $fileName2.'/'.$relativeNameInZipFile);
                            }
                    }
                    $zip->close();   
                }

                DB::commit();
                return response()->download(public_path($fileName));
            }else {
                DB::rollback();
                return response()->json(['status' => '400', 'message' =>"La resolución no contiene trámites"], 400);
            }

            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }


        
    }
}
