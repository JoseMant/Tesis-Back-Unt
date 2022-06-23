<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PersonaSuv extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'sistema.persona';
    protected $primaryKey = 'idpersona';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
