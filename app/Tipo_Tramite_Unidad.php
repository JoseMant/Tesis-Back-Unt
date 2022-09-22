<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tipo_Tramite_Unidad extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tipo_tramite_unidad';
    protected $primaryKey = 'idTipo_tramite_unidad';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
