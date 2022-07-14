<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PersonaSE extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'alumno';
    protected $primaryKey = 'idAlumno';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
