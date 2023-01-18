<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificacionDecanatoMail extends Mailable
{
    use Queueable, SerializesModels;
    public $decano;
    public $tramite;
    public $tipo_tramite;
    public $tipo_tramite_unidad;

    public function __construct($decano,$tramite,$tipo_tramite,$tipo_tramite_unidad)
    {
        $this->decano = $decano;
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
        $subject = 'NOTIFICACIÓN DEL TRÁMITE N° '.$this->tramite->nro_tramite.' PENDIENTE DE FIRMA DE DECANO';
        $emisor = config('mail.mailers.smtp.username');
        return  $this->from($emisor, 'UNIDAD DE REGISTRO ACADÉMICO ADMINISTRATIVO')->subject($subject)->view('emails.notificacion_decanato');
    }
}
