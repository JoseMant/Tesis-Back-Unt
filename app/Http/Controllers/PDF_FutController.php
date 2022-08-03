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
        $dependencia=DependenciaURAA::Where('idDependencia',$tramite->idDependencia)->first();
        $tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_Tramite_Unidad',$tramite->idTipo_tramite_unidad)->first();
        $tipo_tramite=Tipo_Tramite::Where('idTipo_Tramite',$tipo_tramite_unidad->idTipo_tramite)->first();
        // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
        $dependenciaDetalle="";
        $personaSE=PersonaSE::Where('alumno.nro_documento',$usuario->nro_documento)->first();
        if ($personaSE) {
            $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
        }else{
          $personaSuv=PersonaSuv::Where('per_dni',$usuario->nro_documento)->first();
          if ($personaSuv) {
              $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
          }else {
            $personaSga=PersonaSga::Where('per_dni',$usuario->nro_documento)->first();
            if ($personaSga) {
                $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
            }
          }
        }

        // =========================
        // ==== CREACIÓN DE PDF ====
        // =========================
        $this->pdf->AliasNbPages();
        $this->pdf->AddPage();
        // $this->pdf->Image( public_path().'/img/fondo.png', 5, 107, -150, -150);

        // LOGO Y TÍTULO

        //$this->pdf->Image( public_path().'/img/logo_unt.png',Horiz,vert,ancho,alto)     mientras más grande el número negativo más pequeño es la imagen
        $this->pdf->Image( public_path().'/img/logo_unt.png', 8, 10, -1300, -1300);
        $this->pdf->SetFont('times', 'B', 18);
        $this->pdf->SetXY(50,15);
        // $this->pdf->Cell(horz, vert,'UNIVERSIDAD NACIONAL DE TRUJILLO',subrayado,indiferente,justificacion);
        $this->pdf->Cell(130, 4,'UNIVERSIDAD NACIONAL DE TRUJILLO',0,0,'C');
        $this->pdf->SetFont('times', 'B', 22);
        $this->pdf->SetXY(50,25);
        $this->pdf->Cell(129, 4,'FORMATO UNICO DE TRAMITE - F.U.T.',0,0,'C');

        // FECHA
        $this->pdf->SetFont('times', '', 12);
        $this->pdf->SetXY(50,35);
        $diassemana = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
        $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
        $this->pdf->Cell(150, 4,'Fecha: Trujillo, '.date('d')." de ".$meses[date('n')-1]. " del ".date('Y'),0,0,'R');
        // NOMBRES
        $this->pdf->SetXY(8,45);
        $this->pdf->Cell(50, 4,'Apellidos y Nombres: '.$usuario->apellidos.' '.$usuario->nombres,0,0,'L');
        $this->pdf->Cell(130, 4,'DNI: '.$usuario->nro_documento,0,0,'R');
        // $this->pdf->Cell(110, 4,'Apellidos y Nombres: '.$usuario->apellidos.' '.$usuario->nombres.' DNI: '.$usuario->nro_documento,0,0,'C');
        // EMAIL
        // $this->pdf->SetXY(50,54);
        // $this->pdf->Cell(110, 4,utf8_decode('Email: ____________________________________________________________  Teléfono: ___________________'),0,0,'C');
        $this->pdf->SetXY(8,55);
        $this->pdf->Cell(50, 4,'Email: '.$usuario->correo,0,0,'L');
        $this->pdf->Cell(130, 4,utf8_decode('Teléfono: '.$usuario->telefono),0,0,'R');
        // FACULTAD/OFICINA
        $this->pdf->SetXY(8,65);
        $this->pdf->Cell(110, 4,'De La Facultad/Programa de: '.$dependencia->nombre,0,0,'L');
        // ESCUELA/DEPARTAMENTO
        $this->pdf->SetXY(8,75);
        $this->pdf->Cell(110, 4,utf8_decode('Escuela/Sección/Mención: '.$dependenciaDetalle->denominacion),0,0,'L');
        // SEDE
        $this->pdf->SetXY(8,85);
        $this->pdf->Cell(110, 4,utf8_decode('Sede: '.$tramite->sede),0,0,'L');
        // INFORMACIÓN BANCO
        $this->pdf->SetXY(8,95);
        $this->pdf->Cell(12, 4,utf8_decode('Banco:    '.$voucher->entidad),0,0,'L');
        $this->pdf->SetXY(8,105);
        $this->pdf->Cell(125, 4,utf8_decode('N° Operación:    '.$voucher->nro_operacion),0,0,'L');
        $this->pdf->Cell(10, 4,utf8_decode('Fecha Operación:    '.$voucher->fecha_operacion),0,0,'C');
        // OBJETO DE LA SOLICITUD
        $this->pdf->SetXY(8,115);
        if ($tipo_tramite_unidad->idTipo_tramite==3) {
          $this->pdf->Cell(110, 4,utf8_decode('Objeto de la Solicitud: '.$tipo_tramite->descripcion.'-'.$tipo_tramite_unidad->descripcion),0,0,'L');
        }else {
          $this->pdf->Cell(110, 4,utf8_decode('Objeto de la Solicitud:'.$tipo_tramite_unidad->descripcion),0,0,'L');
        }
        // COMENTARIO
        $this->pdf->SetXY(8,125);
        $this->pdf->Cell(110, 4,utf8_decode('Comentario: '),0,0,'L');
        $this->pdf->SetXY(8,135);
        $this->pdf->MultiCell(195, 4,utf8_decode($tramite->comentario),0,'L', false);

        // DESCRIPCIÓN
        $this->pdf->SetXY(8,155);
        $this->pdf->Cell(110, 4,utf8_decode('Los datos consignados en el presente formulario y la información contenida en los documentos que acompaño'),0,0,'L');
        $this->pdf->SetXY(8,160);
        $this->pdf->Cell(110, 4,utf8_decode('son verdaderos y tienen el carácter de DECLARACIÓN JURADA, los mismos que están sujetos a fiscalización'),0,0,'L');
        $this->pdf->SetXY(8,165);
        $this->pdf->Cell(110, 4,utf8_decode('posterior, que en caso de acreditarse falsedad o fraude, me someto a las sanciones establecidas en la Ley 27444.'),0,0,'L');
        
        // FIRMA
        $this->pdf->Image( public_path().$tramite->firma_tramite, 70, 185, -200, -250);
        $this->pdf->SetXY(8,205);
        $this->pdf->Cell(110, 4,utf8_decode('_______________________'),0,0,'R');
        $this->pdf->SetXY(8,215);
        $this->pdf->Cell(170, 4,utf8_decode('Firma'),0,0,'C');

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