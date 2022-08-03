<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistroTramiteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $usuario;
    public $tramite;
    public $tipo_tramite;
    public $tipo_tramite_unidad;

    public function __construct($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad)
    {
        $this->usuario = $usuario;
        $this->tramite = $tramite;
        $this->tipo_tramite = $tipo_tramite;
        $this->tipo_tramite_unidad = $tipo_tramite_unidad;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'TRÁMITE N° '.$this->tramite->nro_tramite.' REGISTRADO CORRECTAMENTE';
        $emisor = config('mail.mailers.smtp.username');
        return  $this->from($emisor, 'UNIDAD DE REGISTRO ACADÉMICO ADMINISTRATIVO')->subject($subject)->view('emails.registro_tramite');
    }
}
