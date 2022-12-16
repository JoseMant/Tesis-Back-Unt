<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Codedge\Fpdf\Fpdf\Fpdf;

class PDF_Libro extends Fpdf
{
    // public  function __construct(){
    //     $this->FPDF('P', 'mm', array(100,150));
    // }
    public function Footer(){

        // $this->SetY(-15);
        // $this->SetFont('Arial','B', 10);
        // $this->Cell(0,5,utf8_decode('F-M01.03.05-DDA/PG-02'),0,0,'L');
    
        // Position at 1.5 cm from bottom
        $this->SetY(-7);
        // Arial italic 8
        $this->SetFont('Arial','B', 8);
        // Page number
        $this->Cell(0,5,utf8_decode('Universidad Nacional de Trujillo | Jr. Juan Pablo II s/n Ciudad Universitaria - Telf. 239239 | Dirección de Registro Técnico - Telf. 205377'),'T',0,'C');
    }



}