<?php

namespace App;
// use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

// class Usuario extends Model
class Usuario extends Authenticatable
{
    protected $connection = 'mysql2';
    protected $table = 'usuario';
    protected $primaryKey = 'idUsuario';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
