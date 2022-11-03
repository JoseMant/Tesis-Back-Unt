<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificacionCertificadoMail;
class NotificacionCertificadoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $decano;
    public $copias;
    public $usuario;
    public $tramite;
    public $tipo_tramite;
    public $tipo_tramite_unidad;
    public $notificacion;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($decano,$copias,$usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad,$notificacion)
    {
        $this->decano = $decano;
        $this->copias = $copias;
        $this->usuario = $usuario;
        $this->tramite = $tramite;
        $this->tipo_tramite = $tipo_tramite;
        $this->tipo_tramite_unidad = $tipo_tramite_unidad;
        $this->notificacion = $notificacion;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->decano)
            ->cc($this->copias)
            ->send(new \App\Mail\NotificacionCertificadoMail($this->usuario,$this->tramite,$this->tipo_tramite,$this->tipo_tramite_unidad,$this->notificacion));
        // if (empty($this->cc)) {
        //     Mail::to($this->decano)
        //     ->cc($this->copias)
        //     ->send(new \App\Mail\NotificacionCertificadoMail($this->usuario,$this->tramite,$this->tipo_tramite,$this->tipo_tramite_unidad,$this->notificacion));
        // }else {
        //     Mail::to($this->decano)
        //     ->cc($this->secretaria,$this->usuario->correo,$this->usuario->cc)
        //     ->send(new \App\Mail\NotificacionCertificadoMail($this->usuario,$this->tramite,$this->tipo_tramite,$this->tipo_tramite_unidad,$this->notificacion));
        // }
    }
}
