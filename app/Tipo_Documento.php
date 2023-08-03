<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tipo_Documento extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tipo_documento';
    protected $primaryKey = 'idTipo_documento';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
