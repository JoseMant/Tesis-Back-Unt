<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Oficio extends Model
{
    protected $connection = 'mysql';
    protected $table = 'oficio';
    protected $primaryKey = 'idOficio';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
