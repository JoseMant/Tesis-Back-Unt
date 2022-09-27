<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Codedge\Fpdf\Fpdf\Fpdf;

class PDF_Constancia extends Fpdf
{
    function Header()
    {
        // $this->SetFont('Arial','B', 8);
        $this->SetFont('Arial','', 8);
        // Logo
        $this->Image(public_path().'/img/logo_unt.png',15,8,50,30);
        $this->Image(public_path().'/img/uraa.png',59,5,130,35);
        $this->Line(22, 37, 185, 37);
        $this->SetXY(22,38);
        $this->Cell(0,5,utf8_decode('Av. Juan Pablo II S/N - 3 Puerta Ciudad Universitaria                                                                                      Telefax: 044 - 205377'),0,'C');
        
    }
    public function Footer(){
        // Position at 1.5 cm from bottom
        $this->SetY(-7);
        // Arial italic 8
        $this->SetFont('Arial','B', 8);
        // Page number
        $this->Cell(10,5,utf8_decode('Correo: uraa@unitru.edu.pe'),'T',0,'L');
        $this->Cell(150,5,utf8_decode('Sitio Web: www.unitru.edu.pe'),'T',0,'R');
    }
    function WriteText($text)
    {
        $intPosIni = 0;
        $intPosFim = 0;
        if (strpos($text,'<')!==false && strpos($text,'[')!==false)
        {
            if (strpos($text,'<')<strpos($text,'['))
            {
                $this->Write(6,substr($text,0,strpos($text,'<')));
                $this->Write(6,substr($text,0,strpos($text,'<')));
                $intPosIni = strpos($text,'<');
                $intPosFim = strpos($text,'>');
                $this->SetFont('','B');
                $this->Write(6,substr($text,$intPosIni+1,$intPosFim-$intPosIni-1));
                $this->SetFont('','');
                $this->WriteText(substr($text,$intPosFim+1,strlen($text)));
            }
            else
            {
                $this->Write(6,substr($text,0,strpos($text,'[')));
                $intPosIni = strpos($text,'[');
                $intPosFim = strpos($text,']');
                $w=$this->GetStringWidth('a')*($intPosFim-$intPosIni-1);
                $this->Cell($w,$this->FontSize+0.75,substr($text,$intPosIni+1,$intPosFim-$intPosIni-1),1,0,'');
                $this->WriteText(substr($text,$intPosFim+1,strlen($text)));
            }
        }
        else
        {
            if (strpos($text,'<')!==false)
            {
                //Este es el if que hace las negritas
                // $this->SetXY(25,110);
                $this->SetFont('','');
                $this->SetLeftMargin(25);
                $this->SetRightMargin(20);
                $this->Write(6,substr($text,0,strpos($text,'<')));
                $intPosIni = strpos($text,'<');
                $intPosFim = strpos($text,'>');
                $this->SetFont('','B');
                $this->WriteText(substr($text,$intPosIni+1,$intPosFim-$intPosIni-1));
                $this->SetFont('','');
                $this->WriteText(substr($text,$intPosFim+1,strlen($text)));
            }
            elseif (strpos($text,'[')!==false)
            {
                $this->Write(6,"hola");
                $this->Write(6,substr($text,0,strpos($text,'[')));
                $intPosIni = strpos($text,'[');
                $intPosFim = strpos($text,']');
                $w=$this->GetStringWidth('a')*($intPosFim-$intPosIni-1);
                $this->Cell($w,$this->FontSize+0.75,substr($text,$intPosIni+1,$intPosFim-$intPosIni-1),1,0,'');
                $this->WriteText(substr($text,$intPosFim+1,strlen($text)));
            }
            else
            {
                $this->Write(6,$text);
            }
        
        }
    }
}