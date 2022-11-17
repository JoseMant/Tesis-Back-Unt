<?php

namespace App\Mail;
   
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
// use App\Usuario;
class NewMail extends Mailable{

    use Queueable, SerializesModels;

    public $usuario;
    public $bandera;
    public $cabecera;
    public $mensaje;

    public function __construct($usuario,$bandera){

        $this->usuario = $usuario;
        $this->bandera = $bandera;
    }

    // public function build(){

    //     return $this->subject('This is Testing Mail')
    //                 ->view('emails.confirmation_code');
    // }

    public function build()
    {
      if ($this->bandera==true) {
        $subject = 'CONFIRMACIÓN DE CORREO ELECTRÓNICO PARA EL REGISTRO DE USUARIO';
        $this->cabecera='NOTIFICACIÓN DE REGISTRO DE USUARIO';
        $this->mensaje='el registro de tu usuario';
      }else {
        $subject = 'CONFIRMACIÓN DE CORREO ELECTRÓNICO PARA LA ACTUALIZACIÓN DE CORREO';
        $this->cabecera='NOTIFICACIÓN DE ACTUALIZACIÓN DE CORREO';
        $this->mensaje='la actualización de tu correo';
      }
      $emisor = config('mail.mailers.smtp.username');
      return  $this->from($emisor, 'UNIDAD DE REGISTRO ACADÉMICO ADMINISTRATIVO')->subject($subject)->view('emails.confirmation_code');
    }
}