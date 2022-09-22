<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'sga_banco';
    protected $primaryKey = 'ban_id';
    public $timestamps = false;
    protected $fillable = [
      'ban_descripcion',
      'ban_estado'
    ];
    protected $guarded = [];
}
