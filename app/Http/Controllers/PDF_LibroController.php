<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;

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
      $this->pdf=new FPDF('P', 'mm', array(200,305));
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
      $this->pdf->MultiCell(30,8,utf8_decode("FACULTAD"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetFont('times', 'B', 8);
      $this->pdf->SetXY($x+218,$y+15);
      $this->pdf->MultiCell(30,4,utf8_decode("FECHA DE COLACIÓN"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetFont('times', 'B', 9);
      $this->pdf->SetXY($x+248,$y+15);
      $this->pdf->MultiCell(40,4,utf8_decode("FECHA Y NRO DE RESOLUCIÓN"),1,'C');

      
      // CONTENIDO DE LA TABLA 
      // $this->pdf->Ln();

      $y=$this->pdf->GetY();
      $this->pdf->SetXY(8,$y);
      $this->pdf->SetFont('times', '', 9);
      $this->pdf->MultiCell(25,8,"9999999999",1,'C');

      $nombres="HUACANJULCA CHIMANCHUMO MAXIMILIANO MAXIMILIANO";
      $tamNombres= strlen($nombres);
      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+23,$y);
      if ($tamNombres>=29) {
        $this->pdf->MultiCell(75,4,$nombres,1,'C');
      }else {
        $this->pdf->MultiCell(75,8,$nombres,1,'C');
      }

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+98,$y);
      $this->pdf->MultiCell(25,8,utf8_decode("G00055555"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+123,$y);
      $this->pdf->MultiCell(65,4,utf8_decode("BACHILLER EN FARMACIA Y BIOQUÍMICA"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetXY($x+188,$y);
      $this->pdf->MultiCell(30,4,utf8_decode("CIENCIAS DE LA COMUNICACION"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetFont('times', '', 10);
      $this->pdf->SetXY($x+218,$y);
      $this->pdf->MultiCell(30,8,utf8_decode("23-12-12"),1,'C');

      $x=$this->pdf->GetX();
      $this->pdf->SetFont('times', '', 9);
      $this->pdf->SetXY($x+248,$y);
      $this->pdf->MultiCell(40,8,utf8_decode("23-12-12 021-2022"),1,'C');

      $nombre_descarga = utf8_decode("LIBRO DE GRADOS Y TÍTULOS");
      $this->pdf->SetTitle( $nombre_descarga );
      return response($this->pdf->Output('i', $nombre_descarga.".pdf", false))
      ->header('Content-Type', 'application/pdf');
    }

}
