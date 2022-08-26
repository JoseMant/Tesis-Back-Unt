<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'matriculas.alumno';
    protected $primaryKey = 'idalumno';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
