<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PersonaSUNT extends Model
{
    protected $connection = 'mysql5';
    protected $table = 'persona';
    protected $primaryKey = 'per_id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
