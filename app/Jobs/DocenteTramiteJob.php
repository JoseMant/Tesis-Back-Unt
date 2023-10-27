<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocenteTramiteMail;




class DocenteTramiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $departamento;
    public $usuario;
    public $docente;
    public $tramite;
    public $tipo_tramite;
    public $tipo_tramite_unidad;
    public $copias;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($departamento,$usuario,$docente,$tramite,$tipo_tramite,$tipo_tramite_unidad,$copias)
    {   
        $this->departamento = $departamento;
        $this->usuario = $usuario;
        $this->docente = $docente;
        $this->tramite = $tramite;
        $this->tipo_tramite = $tipo_tramite;
        $this->tipo_tramite_unidad = $tipo_tramite_unidad;
        $this->copias = $copias;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->usuario->correo)
            ->cc($this->copias)
            ->send(new \App\Mail\DocenteTramiteMail($this->departamento,$this->usuario,$this->docente,$this->tramite,$this->tipo_tramite,$this->tipo_tramite_unidad));
    }
}
