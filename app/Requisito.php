<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Requisito extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'requisitos';
    protected $primaryKey = 'idRequisito';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
