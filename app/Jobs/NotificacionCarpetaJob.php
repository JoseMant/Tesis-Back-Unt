<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificacionCarpetaMail;
class NotificacionCarpetaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $usuario;
    public $secretariaEscuela;
    public $tramite;
    public $tipo_tramite;
    public $tipo_tramite_unidad;
    public $notificacion;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($usuario,$secretariaEscuela,$tramite,$tipo_tramite,$tipo_tramite_unidad,$notificacion)
    {
        $this->usuario = $usuario;
        $this->secretariaEscuela = $secretariaEscuela;
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
        Mail::to($this->secretariaEscuela->correo)
            ->cc($this->usuario->correo)
        ->send(new \App\Mail\NotificacionCarpetaMail($this->usuario,$this->tramite,$this->tipo_tramite,$this->tipo_tramite_unidad,$this->notificacion));
    }
}
