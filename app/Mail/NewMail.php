<?php

namespace App\Mail;
   
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Usuario;
class NewMail extends Mailable{

    use Queueable, SerializesModels;

    public $user;

    public function __construct($user){

        $this->user = $user;

    }

    // public function build(){

    //     return $this->subject('This is Testing Mail')
    //                 ->view('emails.confirmation_code');
    // }

    public function build()
    {
    //   $subject = 'REVISIÓN DE PADRÓN DE MATRICULADOS - "'.$this->padron.'"';
      $emisor = config('mail.mailers.smtp.username');
      return  $this->from($emisor, 'UNIDAD DE REGISTRO ACADÉMICO ADMINISTRATIVO')->subject('This is Testing Mail')->view('emails.confirmation_code');
    }
}