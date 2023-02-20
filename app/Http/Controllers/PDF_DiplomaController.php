<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spipu\Html2Pdf\Html2Pdf;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Tramite;
use App\Escuela;
use App\Mencion;
use App\User;
use App\DependenciaURAA;

class PDF_DiplomaController extends Controller
{
    public function Diploma($idTramite){
        try {
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombreComp')
            ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
            ,'tramite.nro_matricula','usuario.tipo_documento','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
            , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
            ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
            'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
            'tramite_detalle.diploma_final','tramite.idTramite_detalle','diploma_carpeta.descripcion as denominacion','diploma_carpeta.codigo as diploma',
            'tipo_tramite_unidad.idTipo_tramite_unidad as idFicha','dependencia.idDependencia','tramite_detalle.nro_libro','tramite_detalle.folio'
            ,'tramite_detalle.nro_registro','resolucion.nro_resolucion','resolucion.fecha as fecha_resolucion','tipo_tramite_unidad.diploma_obtenido',
            'modalidad_carpeta.acto_academico')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('diploma_carpeta','tramite_detalle.idDiploma_carpeta','diploma_carpeta.idDiploma_carpeta')
            ->join('modalidad_carpeta','tramite_detalle.idModalidad_carpeta','modalidad_carpeta.idModalidad_carpeta')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->Find($idTramite);
            // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
            $dependenciaDetalle=null;
            if ($tramite->idUnidad==1) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
                $decano=User::select(DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombres'),'usuario.cargo','usuario.sexo','usuario.grado')
                ->where('idDependencia',$tramite->idDependencia)->where('idTipo_usuario',6)->where('estado',true)->first();
            }else if ($tramite->idUnidad==2) {
                
            }else if ($tramite->idUnidad==3) {
                
            }else{
                $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
                $dependencia=DependenciaURAA::find($tramite->idDependencia);
                $dependencia2=DependenciaURAA::find($dependencia->idDependencia2);
                $tramite->facultad=$dependencia2->nombre;
                // ----------------------------------------
                $decano=User::select(DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombres'),'usuario.cargo','usuario.sexo','usuario.grado')
                ->where('idDependencia',$dependencia2->idDependencia)->where('idTipo_usuario',6)->where('estado',true)->first();
            }
            $tramite->escuela=$dependenciaDetalle->nombre;


            $rector=User::select(DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombres'),'usuario.cargo','usuario.sexo','usuario.grado')->where('idTipo_usuario',12)->where('estado',true)->first();
            $secretaria=User::select(DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombres'),'usuario.cargo','usuario.sexo','usuario.grado')
            ->where('idTipo_usuario',10)->where('estado',true)->first();
            // $tramite->escuela="RESIDENTADO MÉDICO";
            // return $tramite;


            $html2pdf = new Html2Pdf('L', 'A4', 'es', true, 'UTF-8');
            $html2pdf->writeHTML(view('emails.diploma', ['opcFoto' => 1, 'diploma' => 'T110','ficha' => 'PREGRADO','fotoAlumno'=>'foto',
                                                        'denominacion'=>'BACHILLER EN CIENCIAS DE LA COMUNICACIÓN','nombreComp'=>
                                                    'KEVIN JOEL','facultad'=>' FACULTAD DE INGENIERIA','idFicha'=>$tramite->idUnidad,'escuela'=>'INGENIERIA DE SISTEMAS',
                                                'decano'=>$decano,'secretaria'=>$secretaria,'rectorCargo'=>'RECTOR(A)','rector'=>$rector,
                                            'nrolibro'=>1,'folio'=>1,'nroRegistro'=>12,'nroDoc'=>'75411199','tipoFicha'=>'Bachiller','tipoActo'=>'tesis',
                                        'numResolucionUniv'=>'123-2022','fechaResolucionCU'=>'23/03/2022','diplomasEstado'=>'original','tramite'=>$tramite]));
            $html2pdf->output($tramite->codigo.'.pdf');
        }catch(Html2PdfException $e) {
            echo $e;
            exit;
        }
    }
}
