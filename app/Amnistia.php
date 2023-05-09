<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Amnistia extends Model
{
    protected $connection = 'mysql';
    protected $table = 'amnistia';
    protected $primaryKey = 'idAmnistia';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
