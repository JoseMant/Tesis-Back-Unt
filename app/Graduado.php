<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Graduado extends Model
{
    protected $connection = 'mysql4';
    protected $table = 'graduado';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
