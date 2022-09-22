<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EnvioCertificadoMail extends Mailable
{
    use Queueable, SerializesModels;
    public $usuario;
    public $tramite;
    public $tipo_tramite;
    public $tipo_tramite_unidad;
    public $ruta;

    public function __construct($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad,$ruta)
    {
        $this->usuario = $usuario;
        $this->tramite = $tramite;
        $this->tipo_tramite = $tipo_tramite;
        $this->tipo_tramite_unidad = $tipo_tramite_unidad;
        $this->ruta = $ruta;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // $subject = 'TRÁMITE N° '.$this->tramite->nro_tramite.' REGISTRADO CORRECTAMENTE';
        // $subject = 'ACTUALIZACIÓN DEL ESTADO DEL TRÁMITE N° '.$this->tramite->nro_tramite;
        $emisor = config('mail.mailers.smtp.username');
        // return  $this->from($emisor, 'UNIDAD DE REGISTRO ACADÉMICO ADMINISTRATIVO')->subject($subject)->view('emails.actualizacion_tramite');
        return  $this->from($emisor,'DIRECCION REGISTRO TECNICO')->subject('TRAMITE DE CERTIFICADO DE ESTUDIO COMPLETADO')
                ->view('emails.envio_certificado')->attach($this->ruta, [
                                                            'as' => $this->tramite->nro_tramite.'.pdf',
                                                            'mime' => 'application/pdf',
                                                        ]);
    }
}
