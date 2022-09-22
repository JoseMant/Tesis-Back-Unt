<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'cargo';
    protected $primaryKey = 'cgo_id';
    public $timestamps = false;
    protected $fillable = [
    ];
    protected $guarded = [];
}
