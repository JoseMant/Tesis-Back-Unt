<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
  protected $connection = 'pgsql';
  protected $table = 'admision.banco';
  protected $primaryKey = 'idbanco';
  public $timestamps = false;
  protected $fillable = [
    'ban_descripcion',
    'ban_estado'
  ];
  protected $guarded = [];
}
