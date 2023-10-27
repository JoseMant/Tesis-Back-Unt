<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Resolucion_Secretaria extends Model
{
    protected $connection = 'mysql';
    protected $table = 'resolucion_secretaria';
    protected $primaryKey = 'idResolucion_secretaria';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
