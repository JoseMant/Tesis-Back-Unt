<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dependencia extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'dependencia';
    protected $primaryKey = 'dep_id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
