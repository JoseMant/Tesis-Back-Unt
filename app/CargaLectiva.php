<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CargaLectiva extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'asignacion.cargalectiva';
    protected $primaryKey = 'idcargalectiva';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
