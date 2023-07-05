<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Usuario_Programa extends Model
{
    protected $connection = 'mysql';
    protected $table = 'usuario_programa';
    protected $primaryKey = 'idUsuario_programa';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
