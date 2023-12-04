<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tipo_Resolucion extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tipo_resolucion';
    protected $primaryKey = 'idTipo_resolucion';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
