<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SemestreAcademico extends Model
{
    protected $connection = 'mysql';
    protected $table = 'semestre_academico';
    protected $primaryKey = 'idSemestre_academico';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
