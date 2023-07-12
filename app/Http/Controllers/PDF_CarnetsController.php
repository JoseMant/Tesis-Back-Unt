<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;

use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Mail\Mailable;
use App\Tramite;
use App\User;
use App\ProgramaURAA;
use App\Usuario_Programa;



class PDF_CarnetsController extends Controller
{
    protected $pdf;

  public function __construct(\App\PDF_Fut $pdf)
  {
    $this->pdf = $pdf;
    $this->middleware('jwt', ['except' => ['pdf_carnetsSolicitados','pdf_carnetsRecibidos']]);
  }
  
  public function pdf_carnetsSolicitados(Request $request)
  {
    $token = JWTAuth::setToken($request->access);
    $apy = JWTAuth::getPayload($token);
    

    

// $idDependencia=5;
    $tramites=Tramite::select('tramite.sede','programa.nombre as escuela',DB::raw('count(tramite.idEstado_tramite) as carnets'))
    ->join('programa','programa.idPrograma','tramite.idPrograma')
    ->where('tramite.idTipo_tramite_unidad',17)
    ->orWhere('tramite.idTipo_tramite_unidad',18)
    ->orWhere('tramite.idTipo_tramite_unidad',30)
    ->where('tramite.idEstado_tramite',27)
    ->groupBy('tramite.sede')
    ->groupBy('programa.nombre')
    ->orderBy('tramite.sede')
    
    ->get();



    $this->pdf->AliasNbPages();
     $this->pdf->AddPage('P');

    $this->pdf->SetFont('Arial','', 9);
    $this->pdf->SetXY(10,10);
    $this->pdf->Cell(65, 4,'UNIVERSIDAD NACIONAL DE TRUJILLO',0,0,'C');
    $this->pdf->SetXY(10,14);
    $this->pdf->Cell(65, 4,utf8_decode('UNIDAD DE REGISTROS ACADEMICOS'),0,0,'C');
    $this->pdf->SetXY(10,18);
    $this->pdf->Cell(40, 4,utf8_decode('SECCIÓN DE INFORMÁTICA Y SISTEMAS'),0,0,'L');

    $this->pdf->SetXY(-65,10);
    $this->pdf->Cell(80, 4,'FECHA : '.date("j/ n/ Y"),0,0,'C');
    $this->pdf->SetXY(-65,14);
    $this->pdf->Cell(80, 4,'HORA : '.date("H:i:s"),0,0,'C');
    //TITULO
    $this->pdf->SetFont('Arial','B', 10);
    $this->pdf->SetXY(10,25);
    $this->pdf->Cell(192, 4,utf8_decode('ENTREGA DE CARNÉS A SECRETARÍA DE ESCUELA'),0,0,'C');
    
    //TABLA
    //SEDE
    $this->pdf->SetFont('Arial','B', 7);
    $this->pdf->SetXY(10,30);
    $this->pdf->Cell(33, 7,'SEDE',1,0,'C');
    //ESCUELA
    $this->pdf->SetXY(43,30);
    $this->pdf->Cell(114, 7,'ESCUELA',1,0,'C');
    //#CARNETS
    $this->pdf->SetXY(157,30);
    $this->pdf->Cell(18, 7,'#CARNETS',1,0,'C');
    //FIRMA
    $this->pdf->SetXY(175,30);
    $this->pdf->Cell(27, 7,'FIRMA',1,0,'C');

    //DATOS
    //SEDE
    // $this->pdf->SetXY(10,34);
    // $this->pdf->Cell(30, 4,'VALLE JEQUETEPEQUE',1,0,'L');
    // //ESCUELA
    // $this->pdf->SetXY(40,34);
    // $this->pdf->Cell(112, 4,utf8_decode(' EDUCACION SECUNDARIA, MENCIÓN EN: IDIOMAS: INGLÉS - FRANCÉS O INGLÉS - ALEMÁN'),1,0,'L');
    // //#CARNETS
    // $this->pdf->SetXY(152,34);
    // $this->pdf->Cell(18, 4,'#CARNETS',1,0,'C');
    // //FIRMA
    // $this->pdf->SetXY(170,34);
    // $this->pdf->Cell(35, 4,'FIRMA',1,0,'C');

    $salto=0;
    $i=0;
    $inicioY=37;
    $this->pdf->SetFont('Arial','', 7);
    foreach ($tramites as $key => $tramite) {
        
        //SEDE
        $this->pdf->SetXY(10,$inicioY+$salto);
        $sede=$tramite->sede;
        if (strlen($tramite->sede)>20) {
            $sede=substr($tramite->sede, 0, -5)."...";
        }
        $this->pdf->Cell(33, 8,$sede,1,0,'L');
        //ESCUELA
        $this->pdf->SetXY(43,$inicioY+$salto);
        // $escuela=$tramite->escuela;
        // if (strlen($tramite->escuela)>50) {
        //     $escuela=substr($tramite->escuela, 0, -20)."...";
        // }
        $this->pdf->Cell(114, 8,utf8_decode($tramite->escuela),1,0,'L');
        //#CARNETS
        $this->pdf->SetXY(157,$inicioY+$salto);
        $this->pdf->Cell(18, 8,$tramite->carnets,1,0,'C');
        //FIRMA
        $this->pdf->SetXY(175,$inicioY+$salto);
        $this->pdf->Cell(27, 8,' ',1,0,'C');
        $salto+=8;
        $i+=1;
        if (($inicioY+$salto)>=269) {
            $this->pdf->AddPage();
            $inicioY=17;
            $salto=0;
            //TABLA
            $this->pdf->SetFont('Arial','B', 7);
            $this->pdf->SetXY(10,10);
            $this->pdf->Cell(33, 7,'SEDE',1,0,'C');
            //ESCUELA
            $this->pdf->SetXY(43,10);
            $this->pdf->Cell(114, 7,'ESCUELA',1,0,'C');
            //#CARNETS
            $this->pdf->SetXY(157,10);
            $this->pdf->Cell(18, 7,'#CARNETS',1,0,'C');
            //FIRMA
            $this->pdf->SetXY(175,10);
            $this->pdf->Cell(27, 7,'FIRMA',1,0,'C');
            $this->pdf->SetFont('Arial','', 7);
        }
    }

    return response($this->pdf->Output('i',"Reporte_carnets_recibos".".pdf", false))
     ->header('Content-Type', 'application/pdf');    


        
  }

  public function pdf_carnetsRecibidos(Request $request)
  {
    $token = JWTAuth::setToken($request->access);
    $apy = JWTAuth::getPayload($token);
    // $idUsuario=$apy['idUsuario'];
    $idUsuario=73;
    $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');
    // return count($usuario_programas)
    // $a=0;
    // foreach ($usuario_programas as $key ) {
    //     $a++;
    // }
    // return $a;
    // return $usuario_programas;
    // $idDependencia=5;
    $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.sede', 'usuario.apellidos as apellidos','usuario.nombres as nombres'
    ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite','dependencia.nombre as facultad'
    ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
    , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
    ,'tramite.exonerado_archivo','tramite.idUnidad','programa.nombre as programa','programa.idPrograma as idPrograma')
    ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
    ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
    ->join('unidad','unidad.idUnidad','tramite.idUnidad')
    ->join('usuario','usuario.idUsuario','tramite.idUsuario')
    ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
    ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
    ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
    ->join('voucher','tramite.idVoucher','voucher.idVoucher')
    ->join('programa','tramite.idPrograma','programa.idPrograma')
    ->where('tramite.idEstado_tramite',27)
    ->where('tipo_tramite.idTipo_tramite',3)
    ->where(function($query) use ($usuario_programas)
    {
        if (count($usuario_programas) > 0) {
            $query->whereIn('tramite.idPrograma',$usuario_programas);
        }
    })
    ->orderBy('programa')
    ->orderBy('tramite.sede')
    ->orderBy('apellidos')
    ->get();

    
    

    $this->pdf->AliasNbPages();
    $this->pdf->AddPage('P');

    $this->pdf->SetFont('Arial','', 9);
    $this->pdf->SetXY(10,10);
    $this->pdf->Cell(65, 4,'UNIVERSIDAD NACIONAL DE TRUJILLO',0,0,'C');
    $this->pdf->SetXY(10,14);
    $this->pdf->Cell(65, 4,utf8_decode('UNIDAD DE REGISTROS ACADEMICOS'),0,0,'C');
    $this->pdf->SetXY(10,18);
    $this->pdf->Cell(40, 4,utf8_decode('SECCIÓN DE INFORMÁTICA Y SISTEMAS'),0,0,'L');

    $this->pdf->SetXY(-65,10);
    $this->pdf->Cell(80, 4,'FECHA : '.date("j/ n/ Y"),0,0,'C');
    $this->pdf->SetXY(-65,14);
    $this->pdf->Cell(80, 4,'HORA : '.date("H:i:s"),0,0,'C');
    //TITULO
    $this->pdf->SetFont('Arial','B', 15);
    $this->pdf->SetXY(10,23);
    $this->pdf->Cell(188, 4,utf8_decode('CARNETS RECIBIDOS'),0,0,'C');

    //TITULO
    
    
        
            

            //TABLA
            //NRO MATRICULA
            $this->pdf->SetFont('Arial','B', 7);
            $this->pdf->SetXY(10,34);
            $this->pdf->Cell(25, 5,'NRO MATRICULA',1,0,'C');
            //APELLIDOS Y NOMBRES
            $this->pdf->SetXY(35,34);
            $this->pdf->Cell(65, 5,'APELLIDOS',1,0,'C');
            //NOMBRES
            $this->pdf->SetXY(100,34);
            $this->pdf->Cell(53, 5,'NOMBRES',1,0,'C');
            //SEDE
            $this->pdf->SetXY(153,34);
            $this->pdf->Cell(45, 5,'SEDE',1,0,'C');


            // //TABLA
            // //NRO MATRICULA
            // $this->pdf->SetXY(10,34);
            // $this->pdf->Cell(25, 4,'1513300920',1,0,'C');
            // //APELLIDOS Y NOMBRES
            // $this->pdf->SetXY(35,34);
            // $this->pdf->Cell(67, 4,utf8_decode(' NIÑO LADRON DE GUEVARA ARROYO'),1,0,'L');
            // //NOMBRES
            // $this->pdf->SetXY(102,34);
            // $this->pdf->Cell(51, 4,utf8_decode(' CARMEN IDALIA'),1,0,'L');
            // //SEDE
            // $this->pdf->SetXY(153,34);
            // $this->pdf->Cell(40, 4,' VALLE JEQUETEPEQUE',1,0,'L');
            
            $salto=0;
            $i=0;
            $inicioY=39;

            $programaY=29;
            foreach ($tramites as $key => $tramite) {
                // if ($key!=0) {
                //     return $tramite[$key-1]->programa;
                // }
                if($key==0||$tramites[$key-1]['programa']!=$tramites[$key]['programa']){

                    if($key!=0&&$tramites[$key-1]['programa']!=$tramites[$key]['programa']){
                        $this->pdf->AddPage();
                        $inicioY=20;
                        $salto=0;
                        
                        //TABLA
                        //NRO MATRICULA
                        $this->pdf->SetFont('Arial','B', 7);
                        $this->pdf->SetXY(10,15);
                        $this->pdf->Cell(25, 5,'NRO MATRICULA',1,0,'C');
                        //APELLIDOS Y NOMBRES
                        $this->pdf->SetXY(35,15);
                        $this->pdf->Cell(65, 5,'APELLIDOS',1,0,'C');
                        //NOMBRES
                        $this->pdf->SetXY(100,15);
                        $this->pdf->Cell(53, 5,'NOMBRES',1,0,'C');
                        //SEDE
                        $this->pdf->SetXY(153,15);
                        $this->pdf->Cell(45, 5,'SEDE',1,0,'C');
                        $programaY=10;
                    
                    }


                    $this->pdf->SetFont('Arial','B', 8);
                    $this->pdf->SetXY(10,$programaY);
                    $this->pdf->Cell(188, 4,utf8_decode('PROGRAMA: '.$tramite->programa),0,0,'L');
                    $this->pdf->SetFont('Arial','', 7);
                }
                

                $this->pdf->SetXY(10,$inicioY+$salto);
                $this->pdf->Cell(25, 5,$tramite->nro_matricula,1,0,'C');
                //APELLIDOS Y NOMBRES
                $this->pdf->SetXY(35,$inicioY+$salto);
                $this->pdf->Cell(65, 5,utf8_decode($tramite->apellidos),1,0,'L');
                //NOMBRES
                $this->pdf->SetXY(100,$inicioY+$salto);
                $this->pdf->Cell(53, 5,utf8_decode($tramite->nombres),1,0,'L');
                //SEDE
                $this->pdf->SetXY(153,$inicioY+$salto);
                $this->pdf->Cell(45, 5,$tramite->sede,1,0,'L');

                $salto+=5;
                $i+=1;
                if (($inicioY+$salto)>=272) {
                    $this->pdf->AddPage();
                    $inicioY=15;
                    $salto=0;
                    //TABLA
                    //NUM.ORD.
                    $this->pdf->SetFont('Arial','B', 7);
                    $this->pdf->SetXY(10,10);
                    $this->pdf->Cell(25, 5,'NRO MATRICULA',1,0,'C');
                    //APELLIDOS Y NOMBRES
                    $this->pdf->SetXY(35,10);
                    $this->pdf->Cell(65, 5,'APELLIDOS',1,0,'C');
                    //NOMBRES
                    $this->pdf->SetXY(100,10);
                    $this->pdf->Cell(53, 5,'NOMBRES',1,0,'C');
                    //SEDE
                    $this->pdf->SetXY(153,10);
                    $this->pdf->Cell(45, 5,'SEDE',1,0,'C');
                    $this->pdf->SetFont('Arial','', 7);
                }
            }
    
    
        
        
        
    
    

  return response($this->pdf->Output('i',"Reporte_carnets_recibos".".pdf", false))
  ->header('Content-Type', 'application/pdf');
  }

}
