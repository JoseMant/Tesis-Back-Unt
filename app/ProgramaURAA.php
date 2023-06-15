<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProgramaURAA extends Model
{
    protected $connection = 'mysql';
    protected $table = 'programa';
    protected $primaryKey = 'idPrograma';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
