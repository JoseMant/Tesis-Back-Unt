<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DedicacionDocente extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'dedicacion';
    protected $primaryKey = 'ded_id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
