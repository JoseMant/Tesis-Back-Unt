<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Historial_Codigo_Diploma extends Model
{
    protected $connection = 'mysql';
    protected $table = 'historial_codigo_diploma';
    protected $primaryKey = 'idHistorial_codigo_diploma';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
