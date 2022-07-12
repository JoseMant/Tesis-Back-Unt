<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Estructura extends Model
{
    protected $connection = 'pgsql2';
    protected $table = 'patrimonio.estructura';
    protected $primaryKey = 'idestructura';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
