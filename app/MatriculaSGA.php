<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MatriculaSGA extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'sga_matricula';
    protected $primaryKey = 'mat_id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
