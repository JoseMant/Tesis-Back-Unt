<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\EnvioConstanciaMail;
class EnvioConstanciaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $usuario;
    public $tramite;
    public $tipo_tramite;
    public $tipo_tramite_unidad;
    public $ruta;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad,$ruta)
    {
        $this->usuario = $usuario;
        $this->tramite = $tramite;
        $this->tipo_tramite = $tipo_tramite;
        $this->tipo_tramite_unidad = $tipo_tramite_unidad;
        $this->ruta = $ruta;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->usuario->correo)
        // ->cc($this->correoUsuario)
        ->send(new \App\Mail\EnvioConstanciaMail($this->usuario,$this->tramite,$this->tipo_tramite,$this->tipo_tramite_unidad,$this->ruta));
    }
}
