<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'escalafon.trabajador';
    protected $primaryKey = 'idtrabajador';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
