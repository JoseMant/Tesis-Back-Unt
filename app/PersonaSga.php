<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PersonaSga extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'persona';
    protected $primaryKey = 'per_id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
