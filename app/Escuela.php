<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Escuela extends Model
{
    protected $connection = 'mysql';
    protected $table = 'escuela';
    protected $primaryKey = 'idEscuela';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
