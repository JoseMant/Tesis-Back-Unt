<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MatriculaSUV extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'matriculas.matricula';
    protected $primaryKey = 'idmatricula';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
