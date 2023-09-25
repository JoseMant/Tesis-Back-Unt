<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoriaSGA extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'categoria';
    protected $primaryKey = 'cia_id';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
