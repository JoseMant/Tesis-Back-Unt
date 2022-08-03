<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
  protected $connection = 'pgsql3';
  protected $table = 'admision.banco';
  protected $primaryKey = 'idbanco';
  public $timestamps = false;
  protected $fillable = [];
  protected $guarded = [];
}
