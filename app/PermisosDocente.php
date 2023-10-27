<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PermisosDocente extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'permiso';
    protected $primaryKey = 'pso_id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
