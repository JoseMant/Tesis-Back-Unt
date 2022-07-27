<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Segunda_Especialidad extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'segunda_especialidad';
    protected $primaryKey = 'idSegunda_Especialidad';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
