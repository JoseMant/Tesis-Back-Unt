<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tramite_Requisito extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'tramite_requisito';
    protected $primaryKey = 'idTramite,idRequisito';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
