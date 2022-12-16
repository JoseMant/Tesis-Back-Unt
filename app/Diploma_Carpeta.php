<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Diploma_Carpeta extends Model
{
    protected $connection = 'mysql';
    protected $table = 'diploma_carpeta';
    protected $primaryKey = 'idDiploma_carpeta';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
