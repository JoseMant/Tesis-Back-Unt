<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'pais';
    protected $primaryKey = 'pais_cod';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
