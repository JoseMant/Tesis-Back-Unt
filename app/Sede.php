<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    protected $connection = 'mysql';
    protected $table = 'sga_sede';
    protected $primaryKey = 'sed_id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
