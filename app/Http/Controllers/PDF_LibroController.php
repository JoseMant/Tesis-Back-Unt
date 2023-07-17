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
      
      // tramites----------------------
      $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombreComp')
      ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
      ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','tipo_tramite_unidad.costo'
      ,'tramite.exonerado_archivo','tramite.idUnidad','tipo_tramite.idTipo_tramite','tramite.idEstado_tramite','cronograma_carpeta.fecha_cierre_alumno',
      'cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion',
      'tramite_detalle.diploma_final','tramite.idTramite_detalle','diploma_carpeta.descripcion as denominacion','diploma_carpeta.codigo as diploma',
      'tipo_tramite_unidad.idTipo_tramite_unidad as idFicha','dependencia.idDependencia','tramite_detalle.nro_libro as nro_libro','tramite_detalle.folio as folio'
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
      ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
      ->join('resolucion','cronograma_carpeta.idResolucion','resolucion.idResolucion')
      ->where('tramite_detalle.nro_libro','!=',null)
      ->where('tramite_detalle.folio','!=',null)
      ->where('tramite_detalle.nro_registro','!=',null)
      // ->where('tramite.idTipo_tramite_unidad','!=',37)
      ->where('tramite.idTipo_tramite_unidad',34)
      ->orderBy('tramite_detalle.nro_registro', 'asc')
      ->orderBy('tramite_detalle.folio', 'asc')
      ->orderBy('tramite_detalle.nro_libro', 'asc')
      ->get();
      
      
      $this->pdf=new FPDF('P', 'mm', 'A3');
      $this->pdf->AliasNbPages();
      $this->pdf->AddPage('O');

      $this->pdf->Image( public_path().'/img/logo_unt.png', 8, 0, -1300, -1300);
      $this->pdf->SetFont('times', 'B', 70);
      $this->pdf->SetXY(0,70);
      $this->pdf->Cell(420, 70,'LIBRO '.$tramites[0]['nro_libro'],0,0,'C');
      $this->pdf->SetFont('times', 'B', 18);

      


      $this->pdf->AliasNbPages();
      $this->pdf->AddPage('O');
      // LOGO Y TÍTULO
      $this->pdf->Image( public_path().'/img/logo_unt.png', 8, 0, -1300, -1300);
      $this->pdf->SetFont('times', 'B', 22);
      $this->pdf->SetXY(0,15);
      $this->pdf->Cell(420, 10,'UNIVERSIDAD NACIONAL DE TRUJILLO',0,0,'C');
      $this->pdf->SetFont('times', 'B', 18);
      $this->pdf->SetXY(0,25);
      $this->pdf->Cell(420, 10,utf8_decode('LIBRO DIGITAL DE REGISTRO DE GRADOS Y TÍTULOS'),0,0,'C');
      
      $this->pdf->SetFont('times', 'B', 9);
      $this->pdf->SetXY(8,35);
      $this->pdf->multiCell(80,5,'LIBRO: '.$tramites[0]['nro_libro'].', FOLIO: '.$tramites[0]['folio'],0,'L');
    
      $this->pdf->SetXY(8,40);
      $this->pdf->multiCell(20,5,"NRO DE REGISTRO",1,'C');
          

          
      $this->pdf->SetXY(28,40);
      $this->pdf->multiCell(80,10,"APELLIDOS Y NOMBRES",1,'C');

      $this->pdf->SetXY(108,40);
      $this->pdf->multiCell(40,10,utf8_decode("CÓDIGO DEL DIPLOMA"),1,'C');

      
      $this->pdf->SetXY(148,40);
      $this->pdf->multiCell(135,10,utf8_decode("DENOMINACIÓN"),1,'C');


      $this->pdf->SetXY(283,40);
      $this->pdf->multiCell(65,10,utf8_decode("FACULTAD"),1,'C');

      $this->pdf->SetXY(348,40);
      $this->pdf->multiCell(20,5,utf8_decode("FECHA DE COLACIÓN"),1,'C');
      
      $this->pdf->SetXY(368,40);
      $this->pdf->multiCell(40,5,utf8_decode("FECHA Y NRO DE RESOLUCIÓN"),1,'C');

      $iterador= 1;
      $y=50;
      $salto=10;

      foreach ($tramites as $key => $tramite) {
      
            

        if ($iterador>20) {


          $iterador= 1;
          $this->pdf->AddPage('O');

          $this->pdf->Image( public_path().'/img/logo_unt.png', 8, 0, -1300, -1300);
          $this->pdf->SetFont('times', 'B', 22);
          $this->pdf->SetXY(0,15);
          $this->pdf->Cell(420, 10,'UNIVERSIDAD NACIONAL DE TRUJILLO',0,0,'C');
          $this->pdf->SetFont('times', 'B', 18);
          $this->pdf->SetXY(0,25);
          $this->pdf->Cell(420, 10,utf8_decode('LIBRO DIGITAL DE REGISTRO DE GRADOS Y TÍTULOS'),0,0,'C');
            
          
          $this->pdf->SetFont('times', 'B', 9);
          $this->pdf->SetXY(8,35);
          $this->pdf->Cell(40,5,'LIBRO: '.$tramite->nro_libro.', FOLIO: '.$tramite->folio,0,'L');
      
          

          $this->pdf->SetXY(8,40);
          $this->pdf->multiCell(20,5,"NRO DE REGISTRO",1,'C');
              

              
          $this->pdf->SetXY(28,40);
          $this->pdf->Cell(80,10,"APELLIDOS Y NOMBRES",1,'C');

          $this->pdf->SetXY(108,40);
          $this->pdf->Cell(40,10,utf8_decode("CÓDIGO DEL DIPLOMA"),1,'C');

      
          $this->pdf->SetXY(148,40);
          $this->pdf->multiCell(135,10,utf8_decode("DENOMINACIÓN"),1,'C');

    
          $this->pdf->SetXY(283,40);
          $this->pdf->Cell(65,10,utf8_decode("FACULTAD"),1,'C');
    
          $this->pdf->SetXY(348,40);
          $this->pdf->multiCell(20,5,utf8_decode("FECHA DE COLACIÓN"),1,'C');
          
          $this->pdf->SetXY(368,40);
          $this->pdf->multiCell(40,5,utf8_decode("FECHA Y NRO DE RESOLUCIÓN"),1,'C');
          $y=50;
          $salto=10;
          
        }
        
      
        $this->pdf->SetFont('times', '',9);
        $this->pdf->SetXY(8,$y);
        $this->pdf->multiCell(20,10,$tramite->nro_registro,1,'C');
        
        $this->pdf->SetXY(28,$y);   
        if (strlen($tramite->nombreComp)<45) {
          $this->pdf->multiCell(80,10,utf8_decode($tramite->nombreComp),1,'L');
        }else{
          $this->pdf->multiCell(80,5,utf8_decode($tramite->nombreComp),1,'L');
        }

        $this->pdf->SetXY(108,$y);
        $this->pdf->multiCell(40,10,utf8_decode($tramite->codigo_diploma),1,'C');
        // $this->pdf->Cell(40,10,utf8_decode('AAAAAAAAAAAAAAA'),1,'C');

        $this->pdf->SetXY(148,$y);
        if (strlen($tramite->denominacion)<70) {
          $this->pdf->Cell(135,10,utf8_decode($tramite->denominacion),1,'L');
        }else{
          $this->pdf->multiCell(135,5,utf8_decode($tramite->denominacion),1,'L');
        }
        
        // $this->pdf->multiCell(135,5,utf8_decode('SEGUNDA ESPECIALIDAD PROFESIONAL EN FARMACIA Y BIOQUÍMICA MENCIÓN: FARMACIA HOSPITALARIA Y COMUNITARIA'),1,'C');
        
        
        $this->pdf->SetXY(283,$y);
        if (strlen($tramite->facultad)<35) {
          $this->pdf->Cell(65,10,utf8_decode($tramite->facultad),1,'L');
        }else{
        
          $this->pdf->multiCell(65,5,utf8_decode($tramite->facultad),1,'L');
         
        }

  
        $this->pdf->SetXY(348,$y);
        $this->pdf->MultiCell(20,10,utf8_decode($tramite->fecha_colacion),1,'C');
       
        
        $this->pdf->SetXY(368,$y);
        $this->pdf->MultiCell(40,10,utf8_decode($tramite->fecha_resolucion." ".$tramite->nro_resolucion),1,'C');

        $y+=10;
        $iterador++;

        // if ($key<(count($tramites)-1)) {
        //   if ($tramites[$key]['nro_libro']!=$tramites[$key+1]['nro_libro']) {
        //     $this->pdf->AddPage('O');
        //     $iterador=21;
        //     $this->pdf->SetFont('times', 'B', 22);
        //     $this->pdf->SetXY(0,15);
        //     $this->pdf->Cell(420, 10,'LIBRO:'.$tramites[$key+1]['nro_libro'],0,0,'C');
        //     $this->pdf->SetFont('times', '', 9);
        //   }
        // }

      }

     

      $nombre_descarga = utf8_decode("LIBRO DE GRADOS Y TÍTULOS");
      $this->pdf->SetTitle( $nombre_descarga );
      return response($this->pdf->Output('i', $nombre_descarga.".pdf", false))
      ->header('Content-Type', 'application/pdf');
    }

}
