<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tramite_Detalle extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'tramite_detalle';
    protected $primaryKey = 'idTramite_detalle';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
