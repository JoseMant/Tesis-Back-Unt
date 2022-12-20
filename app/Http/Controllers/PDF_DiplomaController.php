<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spipu\Html2Pdf\Html2Pdf;
use App\Tramite;
class PDF_DiplomaController extends Controller
{
    public function Diploma(){
        try {
            $html2pdf = new Html2Pdf('L', 'A4', 'es', true, 'UTF-8');
            $html2pdf->writeHTML(view('emails.diploma', ['name' => 'Kevin', 'testVar' => 'demo']));
            $html2pdf->output('diploma.pdf');
        }catch(Html2PdfException $e) {
            echo $e;
            exit;
        }
    }
}
