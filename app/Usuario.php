<?php

namespace App;
use Tymon\JWTAuth\Contracts\JWTSubject;
// use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

// class Usuario extends Model
class Usuario extends Authenticatable implements JWTSubject

{
    protected $connection = 'mysql2';
    protected $table = 'usuario';
    protected $primaryKey = 'idUsuario';
    public $timestamps = false;
    protected $fillable = [];
    protected $guarded = [];


    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

}
