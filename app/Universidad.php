<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Universidad extends Model
{
    protected $connection = 'mysql';
    protected $table = 'universidad';
    protected $primaryKey = 'idUniversidad';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
