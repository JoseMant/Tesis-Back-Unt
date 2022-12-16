<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Programa_Estudios_Carpeta extends Model
{
    protected $connection = 'mysql';
    protected $table = 'programa_estudios_carpeta';
    protected $primaryKey = 'idPrograma_estudios_carpeta';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
