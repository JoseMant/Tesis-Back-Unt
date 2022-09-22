<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Motivo_Certificado extends Model
{
    protected $connection = 'mysql';
    protected $table = 'motivo_certificado';
    protected $primaryKey = 'idMotivo_certificado';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
