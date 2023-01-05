<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tramite_Fisico extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tramite_fisico';
    protected $primaryKey = 'idTramite_fisico';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
