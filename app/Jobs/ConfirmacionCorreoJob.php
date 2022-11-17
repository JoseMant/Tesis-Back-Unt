<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewMail;
class ConfirmacionCorreoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $usuario;
    public $bandera;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($usuario,$bandera)
    {
        $this->usuario = $usuario;
        $this->bandera = $bandera;
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
        ->send(new \App\Mail\NewMail($this->usuario,$this->bandera));
    
    }
}
