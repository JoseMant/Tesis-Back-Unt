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
        $tramite=Tramite::findOrFail($idTramite);
        $usuario=User::findOrFail($tramite->idUsuario);
        $dni=$usuario->nro_documento;
        $dependencia=DependenciaURAA::Where('idDependencia',$tramite->idDependencia)->first();
        $tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_Tramite_Unidad',$tramite->idTipo_tramite_unidad)->first();
        $tipo_tramite=Tipo_Tramite::Where('idTipo_Tramite',$tipo_tramite_unidad->idTipo_tramite)->first();
        // VERIFICAR A QUÉ UNIDAD PERTENECE EL USUARIO PARA OBTENER ESCUELA/MENCION/PROGRAMA
        $dependenciaDetalle="";
        if ($tramite->idUnidad==1) {
            $dependenciaDetalle=Escuela::Where('idEscuela',$tramite->idDependencia_detalle)->first();
        }else if ($tramite->idUnidad==2) {
            // Http::get('http://www.epgnew.unitru.edu.pe/epg_admin/api/matricula.php', [
            //     'dni' => $dni
            //   ]);
        }else if ($tramite->idUnidad==3) {
            // Http::get('http://www.epgnew.unitru.edu.pe/epg_admin/api/matricula.php', [
            //     'dni' => $dni
            //   ]);
        }else{
            $dependenciaDetalle=Mencion::Where('idMencion',$tramite->idDependencia_detalle)->first();
        }
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
        $this->pdf->Cell(0, 5,utf8_decode('CONSTANCIA N° '.$tramite->nro_tramite),0,0,'R');
        // $this->pdf->Line(115, 55, 185, 55);
        $this->pdf->SetRightMargin(0);
        $this->pdf->SetFont('Times','B', 22);
        $this->pdf->SetXY(0,63);
        $this->pdf->Cell(0, 4,utf8_decode($tipo_tramite_unidad->descripcion),0,0,'C');

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
        $inicio="";
        $alumno="";
        if ($usuario->sexo=='M') {
            $inicio="Don";
            $alumno="alumno";
        }else {
            $inicio="Doña";
            $alumno="alumna";
        }
        $this->pdf->WriteText(utf8_decode($inicio.' <'.$usuario->apellidos.' '.$usuario->nombres.'> ex '.$alumno.' de la Facultad de <'.$dependencia->nombre.'>, Escuela Profesional de <'.$dependenciaDetalle->nombre.'>, ha completado las exigencias curriculares estando ubicado de acuerdo al <ORDEN DE MÉRITO en el CUARTO (4°)> puesto en su promoción, con <3202>  puntos,  que es el producto de la sumatoria de las notas por los créditos obtenidos en los 10 ciclos  de estudios Profesionales, comprendidos entre los años <MIL NOVECIENTOS OCHENTA> y <MIL NOVECIENTOS OCHENTA Y CUATRO>, años académicos.'));
 
        $y=$this->pdf->GetY();

        $this->pdf->SetFont('Times','', 12);
        $this->pdf->SetXY(25,$y+15);
        $this->pdf->MultiCell(165, 6,utf8_decode('          Se expide la presente, a solicitud de la parte interesada y para los fines a que hubiese lugar, tomada de los archivos de la Sub Unidad de Informática y Estadística de la Unidad de Registro Académico - Administrativo, a los 15 días del mes de agosto del dos mil veintidós --------------------------------------------------------------------------------------------------------------'),0,'L', false);

        $y=$this->pdf->GetY();
        $this->pdf->SetFont('Times','B', 12);
        $this->pdf->SetXY(110,$y+40);
        $this->pdf->MultiCell(76, 6,utf8_decode('Ing. Víctor Miguel Vergara Azabache Jefe de la Unidad de Registro Académico - Administrativo'),0,'C', false);

        $nombre_descarga = utf8_decode($tramite->nro_tramite);
        $this->pdf->SetTitle( $nombre_descarga );
        return response($this->pdf->Output('i', $nombre_descarga.".pdf", false))
        ->header('Content-Type', 'application/pdf');
  }
}
