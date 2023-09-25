<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProfesionDocente extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'profesion';
    protected $primaryKey = 'pon_id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
