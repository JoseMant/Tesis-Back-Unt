<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'usuario';
    protected $primaryKey = 'idUsuario';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
