<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Modalidad_Carpeta extends Model
{
    protected $connection = 'mysql';
    protected $table = 'modalidad_carpeta';
    protected $primaryKey = 'idModalidad_carpeta';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
