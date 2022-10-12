<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cronograma extends Model
{
    protected $connection = 'mysql';
    protected $table = 'cronograma_carpeta';
    protected $primaryKey = 'idCronograma_carpeta';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
