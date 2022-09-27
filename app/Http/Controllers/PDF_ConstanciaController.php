<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tramite;
use App\User;
use App\Voucher;
use App\PersonaSE;
use App\DependenciaURAA;
use App\Mencion;
use App\Escuela;
use App\PersonaSuv;
use App\PersonaSga;
use App\Tipo_Tramite_Unidad;
use App\Tipo_Tramite;

class PDF_ConstanciaController extends Controller
{
    protected $pdf;

    public function __construct(\App\PDF_Constancia $pdf)
    {
        $this->pdf = $pdf;
    }
    public function pdf_constancia($idTramite)
    {

        // DATOS
        $tramite=Tramite::find($idTramite);
        // =========================
        // ==== CREACIÓN DE PDF ====
        // =========================
        $this->pdf->SetLineWidth(0.3);

        $this->pdf->AliasNbPages();
        $this->pdf->AddPage();


        //Logo 
        $this->pdf->Image( public_path().'/img/fondo.png', 10, 50, -160, -140);

        //contenido
        $this->pdf->SetFont('Times','BIU', 16);
        $this->pdf->SetRightMargin(23);
        $this->pdf->SetXY(0,50);
        $this->pdf->Cell(0, 5,utf8_decode('CONSTANCIA N° 001270921'),0,0,'R');
        // $this->pdf->Line(115, 55, 185, 55);
        $this->pdf->SetRightMargin(0);
        $this->pdf->SetFont('Times','B', 22);
        $this->pdf->SetXY(0,63);
        $this->pdf->Cell(0, 4,utf8_decode('QUINTO SUPERIOR'),0,0,'C');

        $this->pdf->SetFont('Times','B', 12);
        $this->pdf->SetXY(25,80);
        $this->pdf->MultiCell(165, 6,utf8_decode('          EL JEFE DE LA UNIDAD DE REGISTRO ACADEMICO - ADMINISTRATIVO DE LA UNIVERSIDAD NACIONAL DE TRUJILLO'),0,'L', false);
        
        $this->pdf->SetFont('Times','', 12);
        $this->pdf->SetXY(123,86.5);
        $this->pdf->Cell(165, 4,utf8_decode(', que suscribe;'),0,'L', false);
        //----------
        $this->pdf->SetFont('Times','BU', 13);
        $this->pdf->SetXY(36,95);
        $this->pdf->MultiCell(29.5, 4,utf8_decode('CERTIFICA:'),0,'L', false);

        $this->pdf->SetFont('Times','', 13);        
        $this->pdf->SetXY(36,105);
        $inicio="Que doña";
        $nombre="VEREAU RODRIGUEZ VIRGINIA SOLEDAD";
        $facultad="CIENCIAS ECONOMICAS";
        $this->pdf->WriteText(utf8_decode($inicio.' <'.$nombre.'> ex alumna de la Facultad de <'.$facultad.'>, Escuela Profesional de <CONTABILIDAD Y FINANZAS>, ha completado las exigencias curriculares estando ubicado de acuerdo al <ORDEN DE MÉRITO en el CUARTO (4°)> puesto en su promoción, con <3202>  puntos,  que es el producto de la sumatoria de las notas por los créditos obtenidos en los 10 ciclos  de estudios Profesionales, comprendidos entre los años <MIL NOVECIENTOS OCHENTA> y <MIL NOVECIENTOS OCHENTA Y CUATRO>, años académicos.'));
 
        $y=$this->pdf->GetY();

        $this->pdf->SetFont('Times','', 12);
        $this->pdf->SetXY(25,$y+15);
        $this->pdf->MultiCell(165, 6,utf8_decode('          Se expide la presente, a solicitud de la parte interesada y para los fines a que hubiese lugar, tomada de los archivos de la Sub Unidad de Informática y Estadística de la Unidad de Registro Académico - Administrativo, a los 15 días del mes de agosto del dos mil veintidós --------------------------------------------------------------------------------------------------------------'),0,'L', false);

        $y=$this->pdf->GetY();
        $this->pdf->SetFont('Times','B', 12);
        $this->pdf->SetXY(110,$y+40);
        $this->pdf->MultiCell(76, 6,utf8_decode('Ing. Víctor Miguel Vergara Azabache Jefe de la Unidad de Registro Académico - Administrativo'),0,'C', false);
        // $this->pdf->SetXY(0,230);
        // $this->pdf->MultiCell(76, 6,utf8_decode('Jefe de la Unidad de Registro'),1,'C', false);
        // $this->pdf->SetXY(0,235);
        // $this->pdf->MultiCell(76, 6,utf8_decode('Académico - Administrativo'),1,'C', false);


        $nombre_descarga = utf8_decode("CONSTANCIA");
        $this->pdf->SetTitle( $nombre_descarga );
        return response($this->pdf->Output('i', $nombre_descarga.".pdf", false))
        ->header('Content-Type', 'application/pdf');
  }
}
