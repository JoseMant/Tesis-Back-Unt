<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Tramite;
class PDF_LibroController extends Controller
{
    protected $pdf;

    public function __construct(\App\PDF_Libro $pdf)
    {
      $this->pdf = $pdf;
      // $this->pdf = new FPDF('P', 'mm', array(200,200));
    }

    public function pdf_libro()
    {
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
      $this->pdf->SetFont('times', 'B', 18);
      $this->pdf->SetXY(50,15);
      $this->pdf->Cell(200, 4,utf8_decode('LIBRO DIGITAL DE REGISTRO DE GRADOS Y TÍTULOS'),0,0,'C');
        
      $y=$this->pdf->GetY();
      $this->pdf->SetXY(8,$y+15);
      $this->pdf->SetFont('times', 'B', 9);
      $this->pdf->MultiCell(25,4,"NRO DE REGISTRO",1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+23,$y+15);
      $this->pdf->MultiCell(75,8,"APELLIDOS Y NOMBRES",1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+98,$y+15);
      $this->pdf->MultiCell(25,4,utf8_decode("CÓDIGO DEL DIPLOMA"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+123,$y+15);
      $this->pdf->MultiCell(65,8,utf8_decode("DENOMINACIÓN"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+188,$y+15);
      $this->pdf->MultiCell(39.85,8,utf8_decode("FACULTAD"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetFont('times', 'B', 8);
      $this->pdf->SetXY($x+228,$y+15);
      $this->pdf->MultiCell(20,4,utf8_decode("FECHA DE COLACIÓN"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetFont('times', 'B', 9);
      $this->pdf->SetXY($x+248,$y+15);
      $this->pdf->MultiCell(40,4,utf8_decode("FECHA Y NRO DE RESOLUCIÓN"),1,'C');

      
      // tramites----------------------
      $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombreComp')
      ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
      ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
      , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
      ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
      'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
      'tramite_detalle.diploma_final','tramite.idTramite_detalle','diploma_carpeta.descripcion as denominacion','diploma_carpeta.codigo as diploma',
      'tipo_tramite_unidad.idTipo_tramite_unidad as idFicha','dependencia.idDependencia','tramite_detalle.nro_libro','tramite_detalle.folio'
      ,'tramite_detalle.nro_registro','tramite_detalle.codigo_diploma', 
      DB::raw("(case 
                  when tramite.idUnidad = 1 then dependencia.nombre  
                  when tramite.idUnidad = 4 then  (select nombre from dependencia d where d.idDependencia=dependencia.idDependencia2)
              end) as facultad"),
      'resolucion.nro_resolucion','resolucion.fecha as fecha_resolucion'        
      )
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
      ->join('resolucion','cronograma_carpeta.idResolucion','resolucion.idResolucion')
      ->where('tramite_detalle.nro_libro','!=',null)
      ->where('tramite_detalle.folio','!=',null)
      ->where('tramite_detalle.nro_registro','!=',null)
      ->where('tramite.idTipo_tramite_unidad','!=',37)
      ->orderBy('tramite_detalle.nro_libro', 'asc')
      ->orderBy('tramite_detalle.folio', 'asc')
      ->orderBy('tramite_detalle.nro_registro', 'asc')
      ->get();
      // CONTENIDO DE LA TABLA 
      $numTramites=count($tramites);
      $iterador= (int)($numTramites/20);
      $iterador= 1;
      foreach ($tramites as $key => $tramite) {
        if ($iterador>20) {
          $iterador= 1;
          $this->pdf->AddPage('O');
        }
        # code...
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y);
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->MultiCell(25,8,$tramite->nro_registro,1,'C');

        $this->pdf->SetFont('times', '', 8);
        $nombres=$tramite->nombreComp;
        $tamNombres= strlen($nombres);
        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+23,$y);
        if ($tamNombres>=47) {
          $this->pdf->MultiCell(75,4,utf8_decode($nombres),1,'C');
        }else {
          $this->pdf->MultiCell(75,8,utf8_decode($nombres),1,'C');
        }

        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+98,$y);
        $this->pdf->MultiCell(25,8,utf8_decode($tramite->codigo_diploma),1,'C');

        $this->pdf->SetFont('times', '', 8);
        $denominacion=$tramite->denominacion;
        $tamDenominacion= strlen($denominacion);
        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+123,$y);
        if ($tamDenominacion>=38) {
          $this->pdf->MultiCell(65,4,utf8_decode($denominacion),1,'C');
        }else {
          $this->pdf->MultiCell(65,8,utf8_decode($denominacion),1,'C');
        }

        $facultad=$tramite->facultad;
        $tamFacultad=strlen($facultad);
        $this->pdf->SetFont('times', '', 6.5);
        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+188,$y);
        // $this->pdf->MultiCell(39.85,4,utf8_decode($facultad),1,'C');
        if ($tamFacultad>=28) {
          $this->pdf->MultiCell(39.85,4,utf8_decode($facultad),1,'C');
        }else {
          $this->pdf->MultiCell(39.85,8,utf8_decode($facultad),1,'C');
        }

        $x=$this->pdf->GetX();
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->SetXY($x+228,$y);
        $this->pdf->MultiCell(20,8,utf8_decode($tramite->fecha_colacion),1,'C');

        $x=$this->pdf->GetX();
        $this->pdf->SetFont('times', '', 9);
        $this->pdf->SetXY($x+248,$y);
        $this->pdf->MultiCell(40,8,utf8_decode($tramite->fecha_resolucion." ".$tramite->nro_resolucion),1,'C');

        $iterador++;
      }

      // for ($i=0; $i <= $iterador; $i++) { 
      //   # code...
      //   $y=$this->pdf->GetY();
      //   $this->pdf->SetXY(8,$y);
      //   $this->pdf->SetFont('times', '', 9);
      //   $this->pdf->MultiCell(25,8,"9999999999",1,'C');
  
      //   $this->pdf->SetFont('times', '', 8);
      //   $nombres="TANTAQUISPE TANTAQUISPE TANTAQUISPE TANTAQUISPE TANTAQUISPE TANTAQUISPE";
      //   $tamNombres= strlen($nombres);
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetXY($x+23,$y);
      //   if ($tamNombres>=47) {
      //     $this->pdf->MultiCell(75,4,utf8_decode($nombres),1,'C');
      //   }else {
      //     $this->pdf->MultiCell(75,8,utf8_decode($nombres),1,'C');
      //   }
  
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetXY($x+98,$y);
      //   $this->pdf->MultiCell(25,8,utf8_decode("G-00055555"),1,'C');
  
      //   $this->pdf->SetFont('times', '', 8);
      //   $denominacion="DOCTOR EN CIENCIAS DE LA COMUNICACION";
      //   $tamDenominacion= strlen($denominacion);
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetXY($x+123,$y);
      //   if ($tamDenominacion>=38) {
      //     $this->pdf->MultiCell(65,4,utf8_decode($denominacion),1,'C');
      //   }else {
      //     $this->pdf->MultiCell(65,8,utf8_decode($denominacion),1,'C');
      //   }
  
      //   $facultad="FACULTAD DE ENFERMERIA";
      //   $tamFacultad=strlen($facultad);
      //   $this->pdf->SetFont('times', '', 6.5);
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetXY($x+188,$y);
      //   // $this->pdf->MultiCell(39.85,4,utf8_decode($facultad),1,'C');
      //   if ($tamFacultad>=28) {
      //     $this->pdf->MultiCell(39.85,4,utf8_decode($facultad),1,'C');
      //   }else {
      //     $this->pdf->MultiCell(39.85,8,utf8_decode($facultad),1,'C');
      //   }
  
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetFont('times', '', 10);
      //   $this->pdf->SetXY($x+228,$y);
      //   $this->pdf->MultiCell(20,8,utf8_decode("23-12-12"),1,'C');
  
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetFont('times', '', 9);
      //   $this->pdf->SetXY($x+248,$y);
      //   $this->pdf->MultiCell(40,8,utf8_decode("23-12-12 021-2022"),1,'C');

      //   # code...
      //   $y=$this->pdf->GetY();
      //   $this->pdf->SetXY(8,$y);
      //   $this->pdf->SetFont('times', '', 9);
      //   $this->pdf->MultiCell(25,8,"9999999999",1,'C');
  
      //   $this->pdf->SetFont('times', '', 8);
      //   $nombres="TANTAQUISPE TANTAQUISPE TANTAQUISPE";
      //   $tamNombres= strlen($nombres);
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetXY($x+23,$y);
      //   if ($tamNombres>=47) {
      //     $this->pdf->MultiCell(75,4,utf8_decode($nombres),1,'C');
      //   }else {
      //     $this->pdf->MultiCell(75,8,utf8_decode($nombres),1,'C');
      //   }
  
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetXY($x+98,$y);
      //   $this->pdf->MultiCell(25,8,utf8_decode("G-00055555"),1,'C');
  
      //   $this->pdf->SetFont('times', '', 8);
      //   $denominacion="DOCTOR EN CIENCIAS DE LA COMUNICACION";
      //   $tamDenominacion= strlen($denominacion);
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetXY($x+123,$y);
      //   if ($tamDenominacion>=38) {
      //     $this->pdf->MultiCell(65,4,utf8_decode($denominacion),1,'C');
      //   }else {
      //     $this->pdf->MultiCell(65,8,utf8_decode($denominacion),1,'C');
      //   }
  
      //   $facultad="FACULTAD DE ENFERMERIA";
      //   $tamFacultad=strlen($facultad);
      //   $this->pdf->SetFont('times', '', 6.5);
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetXY($x+188,$y);
      //   // $this->pdf->MultiCell(39.85,4,utf8_decode($facultad),1,'C');
      //   if ($tamFacultad>=28) {
      //     $this->pdf->MultiCell(39.85,4,utf8_decode($facultad),1,'C');
      //   }else {
      //     $this->pdf->MultiCell(39.85,8,utf8_decode($facultad),1,'C');
      //   }
  
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetFont('times', '', 10);
      //   $this->pdf->SetXY($x+228,$y);
      //   $this->pdf->MultiCell(20,8,utf8_decode("23-12-12"),1,'C');
  
      //   $x=$this->pdf->GetX();
      //   $this->pdf->SetFont('times', '', 9);
      //   $this->pdf->SetXY($x+248,$y);
      //   $this->pdf->MultiCell(40,8,utf8_decode("23-12-12 021-2022"),1,'C');
      // }

      $nombre_descarga = utf8_decode("LIBRO DE GRADOS Y TÍTULOS");
      $this->pdf->SetTitle( $nombre_descarga );
      return response($this->pdf->Output('i', $nombre_descarga.".pdf", false))
      ->header('Content-Type', 'application/pdf');
    }

}
