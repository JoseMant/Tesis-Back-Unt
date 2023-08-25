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
use App\Tramite_Requisito;

class PDF_DiplomaController extends Controller
{
    public function Diploma($idTramite){
        try {
            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idPrograma', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombreComp')
            ,'tramite.created_at as fecha','tramite.nro_tramite as codigo',
            DB::raw("(case 
                    when tramite.idUnidad = 1 then dependencia.denominacion  
                    when tramite.idUnidad = 4 then  (select denominacion from dependencia d where d.idDependencia=dependencia.idDependencia2)
                end) AS dependencia")
            ,'usuario.tipo_documento','usuario.nro_documento','tramite.idUnidad','cronograma_carpeta.fecha_colacion','diploma_carpeta.descripcion as denominacion',
            'diploma_carpeta.codigo as diploma',
            'tramite_detalle.nro_libro','tramite_detalle.folio','tramite_detalle.nro_registro','resolucion.nro_resolucion','resolucion.fecha as fecha_resolucion',
            'tipo_tramite_unidad.diploma_obtenido','modalidad_carpeta.acto_academico','tramite_detalle.codigo_diploma', 'programa.denominacion as programa','tramite_detalle.autoridad1','tramite_detalle.autoridad2'
            ,'tramite_detalle.autoridad3')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('diploma_carpeta','tramite_detalle.idDiploma_carpeta','diploma_carpeta.idDiploma_carpeta')
            ->join('modalidad_carpeta','tramite_detalle.idModalidad_carpeta','modalidad_carpeta.idModalidad_carpeta')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->join('programa','tramite.idPrograma','programa.idPrograma')
            ->Find($idTramite);
            

            $rector=User::select(DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombres'),'usuario.cargo','usuario.sexo','usuario.grado')->find($tramite->autoridad1);
            $secretaria=User::select(DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombres'),'usuario.cargo','usuario.sexo','usuario.grado')
            ->find($tramite->autoridad2);
            $decano=User::select(DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombres'),'usuario.cargo','usuario.sexo','usuario.grado')
            ->find($tramite->autoridad3);

            $requisito_foto=Tramite_Requisito::where('idTramite',$tramite->idTramite)
            ->where(function($query)
            {
                $query->where('idRequisito',15)
                ->orWhere('idRequisito',23)
                ->orWhere('idRequisito',61);
            })->first();


            $html2pdf = new Html2Pdf('L', 'A4', 'es', true, 'UTF-8');
            $html2pdf->writeHTML(view('emails.diploma', 
                [
                    'opcFoto' => 1,
                    'fotoAlumno'=>$requisito_foto->archivo,
                    'idFicha'=>$tramite->idUnidad,
                    'decano'=>$decano,
                    'secretaria'=>$secretaria,
                    'rector'=>$rector,
                    'diplomasEstado'=>'O - ORIGINAL',
                    'tramite'=>$tramite,
                    'diploma'=>''
                ]
            ));
            $html2pdf->output($tramite->codigo.'.pdf');
        }catch(Html2PdfException $e) {
            echo $e;
            exit;
        }
    }
}
