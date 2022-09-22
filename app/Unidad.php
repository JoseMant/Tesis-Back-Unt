<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    protected $connection = 'mysql';
    protected $table = 'unidad';
    protected $primaryKey = 'idUnidad';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
