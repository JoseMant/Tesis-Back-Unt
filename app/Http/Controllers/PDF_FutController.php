<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Tramite;
use App\User;
use App\Voucher;
use App\PersonaSE;
use App\DependenciaURAA;
use App\ProgramaURAA;
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
      $programa=ProgramaURAA::Where('idPrograma',$tramite->idPrograma)->first();
      $tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_Tramite_Unidad',$tramite->idTipo_tramite_unidad)->first();
      $tipo_tramite=Tipo_Tramite::Where('idTipo_Tramite',$tipo_tramite_unidad->idTipo_tramite)->first();
      
      // =========================
      // ==== CREACIÓN DE PDF ====
      // =========================
      $this->pdf->AliasNbPages();
      $this->pdf->AddPage();

      // LOGO Y TÍTULO

      $this->pdf->Image( public_path().'/img/logo_unt.png', 8, 10, -1300, -1300);
      $this->pdf->SetFont('times', 'B', 18);
      $this->pdf->SetXY(50,15);
      $this->pdf->Cell(130, 4,'UNIVERSIDAD NACIONAL DE TRUJILLO',0,0,'C');
      $this->pdf->SetFont('times', 'B', 22);
      $this->pdf->SetXY(50,25);
      $this->pdf->Cell(129, 4,'FORMATO UNICO DE TRAMITE - F.U.T.',0,0,'C');

      // FECHA
      $fecha = Carbon::parse($tramite->created_at);
      // return $fecha->day;
      $this->pdf->SetFont('times', '', 12);
      $this->pdf->SetXY(50,35);
      $diassemana = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
      $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
      $this->pdf->Cell(150, 4,'Fecha: Trujillo, '.$fecha->day." de ".$meses[($fecha->month)-1]. " del ".$fecha->year,0,0,'R');
      // NOMBRES
      $this->pdf->SetXY(8,45);
      $this->pdf->Cell(50, 4,utf8_decode('Apellidos y Nombres: '.$usuario->apellidos.' '.$usuario->nombres),0,0,'L');
      $this->pdf->Cell(130, 4,'DNI: '.$usuario->nro_documento,0,0,'R');
      // EMAIL
      $this->pdf->SetXY(8,55);
      $this->pdf->Cell(50, 4,'Email: '.$usuario->correo,0,0,'L');
      $this->pdf->Cell(130, 4,utf8_decode('Teléfono: '.$usuario->celular),0,0,'R');
      // FACULTAD/OFICINA
      $this->pdf->SetXY(8,65);
      $this->pdf->Cell(110, 4,'Dependencia: '.$dependencia->nombre,0,0,'L');
      // ESCUELA/DEPARTAMENTO
      $this->pdf->SetXY(8,75);
      $this->pdf->Cell(110, 4,utf8_decode('Programa: '.$programa->nombre),0,0,'L');

      // NÚMERO DE MATRÍCULA
      $y=$this->pdf->GetY();
      $this->pdf->SetXY(8,$y+10);
      $this->pdf->Cell(110, 4,utf8_decode('Nro. matrícula: '.$tramite->nro_matricula),0,0,'L');

      // SEDE
      $y=$this->pdf->GetY();
      $this->pdf->SetXY(8,$y+10);
      $this->pdf->Cell(110, 4,utf8_decode('Sede: '.$tramite->sede),0,0,'L');
      // INFORMACIÓN BANCO
      $y=$this->pdf->GetY();
      $this->pdf->SetXY(8,$y+10);
      $this->pdf->Cell(12, 4,utf8_decode('Banco:    '.$voucher->entidad),0,0,'L');
      
      $y=$this->pdf->GetY();
      $this->pdf->SetXY(8,$y+10);
      $this->pdf->Cell(125, 4,utf8_decode('N° Operación:    '.$voucher->nro_operacion),0,0,'L');
      $this->pdf->Cell(10, 4,utf8_decode('Fecha Operación:    '.$voucher->fecha_operacion),0,0,'C');
      // OBJETO DE LA SOLICITUD
      $y=$this->pdf->GetY();
      $this->pdf->SetXY(8,$y+10);
      if ($tipo_tramite_unidad->idTipo_tramite==3) {
        $this->pdf->Cell(110, 4,utf8_decode('Objeto de la Solicitud: '.$tipo_tramite->descripcion.'-'.$tipo_tramite_unidad->descripcion),0,0,'L');
      }else {
        $this->pdf->Cell(110, 4,utf8_decode('Objeto de la Solicitud: '.$tipo_tramite_unidad->descripcion),0,0,'L');
      }
      // COMENTARIO
      if ($tramite->comentario!=null) {
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+10);
        $this->pdf->Cell(110, 4,utf8_decode('Comentario: '),0,0,'L');
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+10);
        $this->pdf->MultiCell(0, 4,utf8_decode($tramite->comentario),0,'L', false);
        
        // DESCRIPCIÓN
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+10);
        $this->pdf->Cell(110, 4,utf8_decode('Los datos consignados en el presente formulario y la información contenida en los documentos que acompaño'),0,0,'L');
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+5);
        $this->pdf->Cell(110, 4,utf8_decode('son verdaderos y tienen el carácter de DECLARACIÓN JURADA, los mismos que están sujetos a fiscalización'),0,0,'L');
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+5);
        $this->pdf->Cell(110, 4,utf8_decode('posterior, que en caso de acreditarse falsedad o fraude, me someto a las sanciones establecidas en la Ley 27444.'),0,0,'L');
        
        // FIRMA
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+40);
        // $this->pdf->Image( public_path().$tramite->firma_tramite, 68, 185, 50, 30); 
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y);
        $this->pdf->Cell(110, 4,utf8_decode('_______________________'),0,0,'R');
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+10); 
        $this->pdf->Cell(170, 4,utf8_decode('Firma'),0,0,'C');
      }else {
        // DESCRIPCIÓN
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+10);
        $this->pdf->Cell(110, 4,utf8_decode('Los datos consignados en el presente formulario y la información contenida en los documentos que acompaño'),0,0,'L');
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+5);
        $this->pdf->Cell(110, 4,utf8_decode('son verdaderos y tienen el carácter de DECLARACIÓN JURADA, los mismos que están sujetos a fiscalización'),0,0,'L');
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+5);
        $this->pdf->Cell(110, 4,utf8_decode('posterior, que en caso de acreditarse falsedad o fraude, me someto a las sanciones establecidas en la Ley 27444.'),0,0,'L');
        
        // FIRMA
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+40);
        if (file_exists(public_path().$tramite->firma_tramite)) {
          $this->pdf->Image( public_path().$tramite->firma_tramite, 68, 155, 50, 30);
        }
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y-5);
        $this->pdf->Cell(110, 4,utf8_decode('_______________________'),0,0,'R');
        $y=$this->pdf->GetY();
        $this->pdf->SetXY(8,$y+10);
        $this->pdf->Cell(170, 4,utf8_decode('Firma'),0,0,'C');
      }

      $nombre_descarga = utf8_decode("FUT");
      $this->pdf->SetTitle( $nombre_descarga );
      return response($this->pdf->Output('i', $nombre_descarga.".pdf", false))
      ->header('Content-Type', 'application/pdf');
  }
}
