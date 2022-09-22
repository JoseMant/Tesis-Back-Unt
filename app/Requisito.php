<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Requisito extends Model
{
    protected $connection = 'mysql';
    protected $table = 'requisito';
    protected $primaryKey = 'idRequisito';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
