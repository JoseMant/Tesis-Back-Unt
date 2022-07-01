<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Mail\Mailable;
class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login','register']]);
    }


    public function Register(Request $request){
        DB::beginTransaction();
        try {
            $usuarioValidate=User::Where('nro_doc',$request->input('nro_doc'))->first();
            if(isset($usuarioValidate)){
              return response()->json(['status' => '400', 'message' => 'El usuario ya se encuentra registrado!!'], 400);
            }else{
                
                $request->validate([
                    'password'=>['required','min:8']
                ]);
                $usuario = new User;
                $usuario -> username=$request->input('username');
                $usuario -> password=Hash::make($request->input('password'));
                $usuario -> nombres=strtoupper($request->input('nombres'));
                $usuario -> apellidos=strtoupper($request->input('apellidos'));
                $usuario -> nro_matricula=$request->input('nro_matricula');
                $usuario -> tipo_doc=$request->input('tipo_doc');
                $usuario -> nro_doc=$request->input('nro_doc');
                $usuario -> correo=$request->input('correo');
                $usuario -> celular=$request->input('celular');
                $usuario -> sexo=$request->input('sexo');
                $usuario -> idTipoUsuario=$request->input('idTipoUsuario');
                $usuario -> confirmation_code=Str::random(25);;
                $usuario -> save();
                DB::commit();
                \Mail::to($usuario->correo)->send(new \App\Mail\NewMail($usuario));
                // \Mail::to('kevinkjjuarez@gmail.com')->send(new \App\Mail\NewMail($usuario));
                return response()->json(['status' => '200', 'message' => 'Confirmar correo!!'], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => 'Error al registrar usuario'], 400);
        }
    }

    public function login()
    {
        $credentials = request(['username', 'password']);
        
        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => Hash::make('12345678')], 401);
        }
        
        return $this->respondWithToken($token);
    }


    public function logout()
    {
        auth()->logout();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'access_token' => JWTAuth::refresh(),
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }

    public function me()
    {
        return response()->json(
            JWTAuth::user()
        );
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

}