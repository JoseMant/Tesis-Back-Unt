<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'voucher';
    protected $primaryKey = 'idVoucher';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];
}
