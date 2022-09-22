<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Historial_Estado extends Model
{
    protected $connection = 'mysql';
    protected $table = 'historial_estado';
    protected $primaryKey = 'idHistorial_estado';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
