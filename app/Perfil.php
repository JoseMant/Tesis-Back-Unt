<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'perfil';
    protected $primaryKey = 'pfl_id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
