<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistroUsuarioMail extends Mailable
{
    use Queueable, SerializesModels;
    public $usuario;
    public $rol;

    public function __construct($usuario,$rol)
    {
        $this->usuario = $usuario;
        $this->rol = $rol;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'USUARIO REGISTRADO';
        $emisor = config('mail.mailers.smtp.username');
        return  $this->from($emisor, 'UNIDAD DE REGISTRO ACADÉMICO ADMINISTRATIVO')->subject($subject)->view('emails.registro_usuario');
    }
}
