<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Tramite;
use App\Escuela;
use App\Mencion;

class PDF_Enviados_ImpresionController extends Controller
{
    protected $pdf;

    public function __construct(\App\PDF_Libro $pdf)
    {
      $this->pdf = $pdf;
      // $this->pdf = new FPDF('P', 'mm', array(200,200));
    }

    public function pdf_enviados_impresion($idResolucion)
    {
        // DATA NECESARIA
        // tramites----------------------
      $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante')
      ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
      ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
      , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
      ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
      'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
      'tramite_detalle.diploma_final','tramite.idTramite_detalle','diploma_carpeta.descripcion as denominacion','diploma_carpeta.codigo as diploma',
      'tipo_tramite_unidad.idTipo_tramite_unidad as idFicha','dependencia.idDependencia','tramite_detalle.nro_libro','tramite_detalle.folio'
      ,'tramite_detalle.nro_registro','resolucion.nro_resolucion','resolucion.fecha as fecha_resolucion','tramite.sede')
      ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
      ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
      ->join('unidad','unidad.idUnidad','tramite.idUnidad')
      ->join('usuario','usuario.idUsuario','tramite.idUsuario')
      ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
      ->join('diploma_carpeta','tramite_detalle.idDiploma_carpeta','diploma_carpeta.idDiploma_carpeta')
      ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
      ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
      ->join('voucher','tramite.idVoucher','voucher.idVoucher')
      ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
      ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
      ->where('tramite.idEstado_tramite',44)
      ->where('tramite_detalle.nro_libro','!=',null)
      ->where('tramite_detalle.folio','!=',null)
      ->where('tramite_detalle.nro_registro','!=',null)
      ->where('resolucion.idResolucion',$idResolucion)
      ->orderBy('tramite_detalle.nro_libro', 'asc')
      ->orderBy('tramite_detalle.folio', 'asc')
      ->orderBy('tramite_detalle.nro_registro', 'asc')
      ->get();
        // --------------------------------


      // =========================
      // ==== CREACIÓN DE PDF ====
      // =========================
      $this->pdf=new FPDF('P', 'mm', array(219,305));
      $this->pdf->AliasNbPages();
      $this->pdf->AddPage('O');

      // LOGO Y TÍTULO

      $this->pdf->Image( public_path().'/img/logo_unt.png', 8, 0, -1300, -1300);
      $this->pdf->SetFont('times', 'B', 22);
      $this->pdf->SetXY(50,5);
      $this->pdf->Cell(200, 4,'UNIVERSIDAD NACIONAL DE TRUJILLO',0,0,'C');
      $this->pdf->SetFont('times', 'B', 15);
      $this->pdf->SetXY(50,15);
      $this->pdf->Cell(200, 4,utf8_decode('LISTA DE ALUMNOS ENVIADOS A IMPRESIÓN'),0,0,'C');
        
      $y=$this->pdf->GetY();
      $this->pdf->SetXY(8,$y+15);
      $this->pdf->SetFont('times', 'B', 9);
      $this->pdf->MultiCell(30,4,utf8_decode("COD. MATRÍCULA"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+28,$y+15);
      $this->pdf->MultiCell(75,8,"NOMBRE COMPLETO",1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+103,$y+15);
      $this->pdf->MultiCell(25,8,utf8_decode("NRO. RES."),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+128,$y+15);
      $this->pdf->MultiCell(30,8,utf8_decode("FECHA RES."),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+158,$y+15);
      $this->pdf->MultiCell(25,8,utf8_decode("NUM. LIBRO"),1,'C');

      $x=$this->pdf->GetX();
    //   $this->pdf->SetFont('times', 'B', 8);
      $this->pdf->SetXY($x+183,$y+15);
      $this->pdf->MultiCell(20,4,utf8_decode("NUM. FOLIO"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+203,$y+15);
      $this->pdf->MultiCell(25,4,utf8_decode("NUM. REGISTRO"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+228,$y+15);
      $this->pdf->MultiCell(59,8,utf8_decode("ESCUELA"),1,'C');

      // $x=$this->pdf->GetX();
      // $this->pdf->SetXY($x+262,$y+15);
      // $this->pdf->MultiCell(25,8,utf8_decode("SEDE"),1,'C');


    foreach ($tramites as $key => $tramite) {

      if ($this->pdf->GetY()>190) {
        $this->pdf->AddPage('O');
      }

        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y);
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->MultiCell(30,8,$tramite->nro_matricula,0,'C');
  
        $this->pdf->SetFont('times', '', 9);
        $nombres=$tramite->solicitante;
        $tamNombres= strlen($nombres);
        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+28,$y);
        if ($tamNombres>=47) {
          $this->pdf->MultiCell(75,4,utf8_decode($nombres),0,'C');
        }else {
          $this->pdf->MultiCell(75,8,utf8_decode($nombres),0,'C');
        }
  
        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+103,$y);
        $this->pdf->MultiCell(25,8,utf8_decode($tramite->nro_resolucion),0,'C');

        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+128,$y);
        $this->pdf->MultiCell(30,8,utf8_decode($tramite->fecha_resolucion),0,'C');
  
        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+158,$y);
        $this->pdf->MultiCell(25,8,utf8_decode($tramite->nro_libro),0,'C');

        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+183,$y);
        $this->pdf->MultiCell(20,8,utf8_decode($tramite->folio),0,'C');

        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+203,$y);
        $this->pdf->MultiCell(25,8,utf8_decode($tramite->nro_registro),0,'C');

        // escuela
        // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
        $dependenciaDetalle=null;
        if ($tramite->idUnidad==1) {
            $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();    
        }else if ($tramite->idUnidad==2) {
            
        }else if ($tramite->idUnidad==3) {
            
        }else{
            $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
        }
        $tramite->escuela=$dependenciaDetalle->nombre;
        // -------
        $this->pdf->SetFont('times', '', 8);
        $tamEscuela=strlen($tramite->escuela);
        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+228,$y);
        if ($tamEscuela>=16) {
            $this->pdf->MultiCell(59,4,utf8_decode($tramite->escuela),0,'C');
        }else {
          $this->pdf->MultiCell(59,8,utf8_decode($tramite->escuela),0,'C');
        }


        // $this->pdf->SetFont('times', '', 8);
        // $tamSede=strlen($tramite->sede);
        // $x=$this->pdf->GetX();
        // $this->pdf->SetXY($x+262,$y);
        // if ($tamSede>=12) {
        //     $this->pdf->MultiCell(25,4,utf8_decode($tramite->sede),0,'C');
        // }else {
        //   $this->pdf->MultiCell(25,8,utf8_decode($tramite->sede),0,'C');
        // }

        $this->pdf->SetY($y+10);
        // $this->pdf->SetFont('times', '', 9);
        // $tamSede=strlen($tramite->sede);
        // $x=$this->pdf->GetX();
        // $this->pdf->SetXY($x+262,$y);
        // if ($tamSede>=16) {
        //     $this->pdf->MultiCell(25,4,utf8_decode($tramite->sede),0,'C');

        // }else {
        //     $this->pdf->MultiCell(25,8,utf8_decode($tramite->sede),0,'C');
        // }
        

    }

      $nombre_descarga = utf8_decode("ALUMNOS ENVIADOS A IMPRESIÓN");
      $this->pdf->SetTitle( $nombre_descarga );
      return response($this->pdf->Output('i', $nombre_descarga.".pdf", false))
      ->header('Content-Type', 'application/pdf');
    }
}
