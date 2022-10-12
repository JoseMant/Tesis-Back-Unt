<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tipo_Usuario extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tipo_usuario';
    protected $primaryKey = 'idTipo_usuario';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
