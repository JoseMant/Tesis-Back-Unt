<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tramite extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tramite';
    protected $primaryKey = 'idTramite';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
