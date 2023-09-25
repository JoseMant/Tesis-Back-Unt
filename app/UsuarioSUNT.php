<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UsuarioSUNT extends Model
{
    protected $connection = 'mysql5';
    protected $table = 'usuario';
    protected $primaryKey = 'usu_id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
