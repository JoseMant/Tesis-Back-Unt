<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Acreditacion extends Model
{
    protected $connection = 'mysql';
    protected $table = 'acreditacion';
    protected $primaryKey = 'idAcreditacion';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
