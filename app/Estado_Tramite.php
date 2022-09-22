<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Estado_Tramite extends Model
{
    protected $connection = 'mysql';
    protected $table = 'estado_tramite';
    protected $primaryKey = 'idEstado_tramite';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
