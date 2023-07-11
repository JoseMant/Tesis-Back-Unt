<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\AnularTramiteMail;

class AnularTramiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $usuario;
    public $tramite;
    public $tipo_tramite;
    public $tipo_tramite_unidad;
    public $notificacion;
    public $copia;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad,$notificacion,$copia)
    {
        $this->usuario = $usuario;
        $this->tramite = $tramite;
        $this->tipo_tramite = $tipo_tramite;
        $this->tipo_tramite_unidad = $tipo_tramite_unidad;
        $this->notificacion = $notificacion;
        $this->copia = $copia;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // dd()
        if ($this->copia) {
            Mail::to($this->usuario->correo)
            ->cc($this->copia)
            ->send(new \App\Mail\AnularTramiteMail($this->usuario,$this->tramite,$this->tipo_tramite,$this->tipo_tramite_unidad,$this->notificacion));
        }else {
            Mail::to($this->usuario->correo)
            ->send(new \App\Mail\AnularTramiteMail($this->usuario,$this->tramite,$this->tipo_tramite,$this->tipo_tramite_unidad,$this->notificacion));
        }
        
    }
}
