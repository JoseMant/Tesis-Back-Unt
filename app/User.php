<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
// use Illuminate\Contracts\Auth\Authenticatable;
// use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;


// class Usuario extends Model
class User extends Authenticatable implements JWTSubject

{
    protected $connection = 'mysql';
    protected $table = 'usuario';
    protected $primaryKey = 'idUsuario';
    public $timestamps = false;
    protected $fillable = [
        'username','password'
    ];
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
        return [
            'idUsuario'=> $this->idUsuario,
            'nro_documento'=>$this->nro_documento,
            'idTipo_usuario'=>$this->idTipo_usuario,
        ];
        // return [
        //     'idUsuario'              => $this->idUsuario,
        //     'nombres'      => $this->nombres,
        //     'apellidos'       => $this->last_name,
        //     'username'           => $this->username,
        //     'nro_matricula'         => $this->nro_matricula,
        //     'tipo_doc'       => $this->tipo_doc,
        //     'registered_at'   => $this->created_at->toIso8601String(),
        //     'last_updated_at' => $this->updated_at->toIso8601String(),
        // ];
    }

}
