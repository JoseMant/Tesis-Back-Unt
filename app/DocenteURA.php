<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DocenteURA extends Model
{
    protected $connection = 'mysql';
    protected $table = 'docentes';
    protected $primaryKey = 'idDocente';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
