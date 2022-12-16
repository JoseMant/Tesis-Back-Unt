<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Resolucion extends Model
{
    protected $connection = 'mysql';
    protected $table = 'resolucion';
    protected $primaryKey = 'idResolucion';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
