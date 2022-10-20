<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegistroUsuarioMail;
class RegistroUsuarioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $usuario;
    public $rol;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($usuario,$rol)
    {
        $this->usuario = $usuario;
        $this->rol = $rol;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->usuario->correo)
        ->send(new \App\Mail\RegistroUsuarioMail($this->usuario,$this->rol));
    }
}
