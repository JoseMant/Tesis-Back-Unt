<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mencion extends Model
{
    protected $connection = 'mysql';
    protected $table = 'mencion';
    protected $primaryKey = 'idMencion';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
