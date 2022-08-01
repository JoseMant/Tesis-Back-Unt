<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tramite;
use App\User;
use App\Voucher;

class PDF_FutController extends Controller
{
    protected $pdf;

    public function __construct(\App\PDF_Fut $pdf)
    {
      $this->pdf = $pdf;
    }

    public function pdf_fut($idTramite)
    {
        // DATOS
        $tramite=Tramite::findOrFail($idTramite);
        $usuario=User::findOrFail($tramite->idUsuario);
        $voucher=Voucher::findOrFail($tramite->idVoucher);



        // =========================
        // ==== CREACIÓN DE PDF ====
        // =========================
        $this->pdf->AliasNbPages();
        $this->pdf->AddPage();
        // $this->pdf->Image( public_path().'/img/fondo.png', 5, 107, -150, -150);

        // LOGO Y TÍTULO

        //$this->pdf->Image( public_path().'/img/logo_unt.png',Horiz,vert,ancho,alto)     mientras más grande el número negativo más pequeño es la imagen
        $this->pdf->Image( public_path().'/img/logo_unt.png', 8, 1, -1300, -1300);
        $this->pdf->SetFont('times', 'B', 18);
        $this->pdf->SetXY(50,6);
        // $this->pdf->Cell(horz, vert,'UNIVERSIDAD NACIONAL DE TRUJILLO',subrayado,indiferente,justificacion);
        $this->pdf->Cell(130, 4,'UNIVERSIDAD NACIONAL DE TRUJILLO',0,0,'C');
        $this->pdf->SetFont('times', 'B', 22);
        $this->pdf->SetXY(50,16);
        $this->pdf->Cell(129, 4,'FORMATO UNICO DE TRAMITE - F.U.T.',0,0,'C');

        // FECHA
        $this->pdf->SetFont('times', '', 12);
        $this->pdf->SetXY(50,34);
        $diassemana = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
        $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
        $this->pdf->Cell(150, 4,'Fecha: Trujillo, '.date('d')." de ".$meses[date('n')-1]. " del ".date('Y'),0,0,'R');
        // NOMBRES
        $this->pdf->SetXY(3,44);
        $this->pdf->Cell(50, 4,'Apellidos y Nombres: '.$usuario->apellidos.' '.$usuario->nombres,0,0,'L');
        $this->pdf->Cell(130, 4,'DNI: '.$usuario->nro_documento,0,0,'R');
        // $this->pdf->Cell(110, 4,'Apellidos y Nombres: '.$usuario->apellidos.' '.$usuario->nombres.' DNI: '.$usuario->nro_documento,0,0,'C');
        // EMAIL
        // $this->pdf->SetXY(50,54);
        // $this->pdf->Cell(110, 4,utf8_decode('Email: ____________________________________________________________  Teléfono: ___________________'),0,0,'C');
        $this->pdf->SetXY(3,54);
        $this->pdf->Cell(50, 4,'Email: '.$usuario->correo,0,0,'L');
        $this->pdf->Cell(130, 4,utf8_decode('Teléfono: '.$usuario->telefono.'95847587'),0,0,'R');
        // FACULTAD/OFICINA
        $this->pdf->SetXY(50,64);
        $this->pdf->Cell(110, 4,'De La Facultad/Programa de: _____________________________________________________________________',0,0,'C');
        // ESCUELA/DEPARTAMENTO
        $this->pdf->SetXY(50,74);
        $this->pdf->Cell(110, 4,utf8_decode('Escuela/Sección/Mención: _________________________________________________________________________________'),0,0,'C');
        // SEDE
        $this->pdf->SetXY(50,84);
        $this->pdf->Cell(110, 4,utf8_decode('Sede: _________________________________________________________________________________________'),0,0,'C');
        // INFORMACIÓN BANCO
        $this->pdf->SetXY(10,94);
        $this->pdf->Cell(15, 4,utf8_decode('Banco:    '.'BCP'),0,0,'C');
        $this->pdf->Cell(125, 4,utf8_decode('N° Operación:    '.'123'),0,0,'C');
        $this->pdf->Cell(55, 4,utf8_decode('Fecha:    '.'23/03/2022'),0,0,'C');
        // OBJETO DE LA SOLICITUD
        $this->pdf->SetXY(50,104);
        $this->pdf->Cell(110, 4,utf8_decode('Objeto de la Solicitud: ____________________________________________________________________________'),0,0,'C');


        // $this->pdf->Cell(110, 4,utf8_decode('N° Operación:    '.'123'),0,0,'C');
        // $this->pdf->Cell(110, 4,utf8_decode('Fecha:    '.'23/03/2022'),0,0,'C');



        // // CÓDIGO
        // $this->pdf->SetXY(47,54);
        // $this->pdf->Cell(110, 4,utf8_decode('Alumno            con N° Matrícula ______________ Docente           Administrativo            Cod. Trabajador _____ '),0,0,'C');
        // $this->pdf->Rect(21, 50, 8, 8);
        // $this->pdf->Rect(112, 50, 8, 8);
        // $this->pdf->Rect(152, 50, 8, 8);
        // // $this->pdf->Rect(120, 50, 8, 8);
        // // FACULTAD/OFICINA
        // $this->pdf->SetXY(50,64);
        // $this->pdf->Cell(110, 4,'De La Facultad (u Oficina) de: ____________________________________________________________________',0,0,'C');
        // // ESCUELA/DEPARTAMENTO
        // $this->pdf->SetXY(50,74);
        // $this->pdf->Cell(110, 4,utf8_decode('Escuela o Dpto: __________________________________________________ Ciclo o Año ____________________'),0,0,'C');
        // // ASUNTO
        // $this->pdf->SetXY(1,84);
        // $this->pdf->Cell(22, 4,'Asunto:',0,0,'C');
        // $this->pdf->SetXY(50,92);
        // $this->pdf->Cell(110, 4,'______________________________________________________________________________________________',0,0,'C');
        // $this->pdf->SetXY(50,102);
        // $this->pdf->Cell(110, 4,'______________________________________________________________________________________________',0,0,'C');
        // $this->pdf->SetXY(50,112);
        // $this->pdf->Cell(110, 4,'______________________________________________________________________________________________',0,0,'C');
        // $this->pdf->SetXY(50,122);
        // $this->pdf->Cell(110, 4,'______________________________________________________________________________________________',0,0,'C');
        // $this->pdf->SetXY(50,132);
        // $this->pdf->Cell(110, 4,'______________________________________________________________________________________________',0,0,'C');
        // // PROCEDIMIENTO
        // $this->pdf->SetXY(20,148);
        // $this->pdf->Cell(22, 4,utf8_decode('N° Procedimiento del TUPA'),0,0,'C');



        //background 
        // $this->pdf->SetFillColor(230,230,230);
        // $this->pdf->SetXY(70,30);
        // $this->pdf->Cell(45,6, "Hola", 0,1,'C',1);





        $nombre_descarga = utf8_decode("FUT");
        $this->pdf->SetTitle( $nombre_descarga );
        return response($this->pdf->Output('i', $nombre_descarga.".pdf", false))
        ->header('Content-Type', 'application/pdf');
    }
}
