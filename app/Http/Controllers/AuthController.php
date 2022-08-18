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
use Illuminate\Support\Facades\Auth;
use App\Jobs\ConfirmacionCorreoJob;
use App\Jobs\ResetPasswordJob;
class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login','register','forgotPassword','verifyCodePassword','ResetPassword']]);
    }


    public function Register(Request $request){
        DB::beginTransaction();
        try {
            $dniValidate=User::Where('nro_documento',$request->input('nro_documento'))->first();
            $correoValidate=User::Where('correo',$request->input('correo'))->first();
            $usernameValidate=User::Where('username',$request->input('username'))->first();
            if($dniValidate){
              return response()->json(['status' => '400', 'message' => 'El dni ya se encuentra registrado!!'], 400);
            }else if(isset($correoValidate)){
                return response()->json(['status' => '400', 'message' => 'El correo ya se encuentra registrado!!'], 400);
            }else if($usernameValidate){
                return response()->json(['status' => '400', 'message' => 'El nombre de usuario ya se encuentra registrado!!'], 400);
            }
            else{
                $request->validate([
                    'password'=>['required','min:8']
                ]);
                $usuario = new User;
                $usuario -> idTipo_usuario=$request->input('idTipo_usuario');
                $usuario -> username=$request->input('username');
                $usuario -> password=Hash::make($request->input('password'));
                $usuario -> nombres=strtoupper($request->input('nombres'));
                $usuario -> apellidos=strtoupper($request->input('apellidos'));
                $usuario -> tipo_documento=$request->input('tipo_documento');
                $usuario -> nro_documento=$request->input('nro_documento');
                $usuario -> correo=$request->input('correo');
                $usuario -> celular=$request->input('celular');
                $usuario -> sexo=$request->input('sexo');
                $usuario -> confirmation_code=Str::random(25);
                $usuario -> save();
                DB::commit();
                // return $usuario;

                // \Mail::to($usuario->correo)->send(new \App\Mail\NewMail($usuario));

                // PRUEBAS JOB---------------------------------
                dispatch(new ConfirmacionCorreoJob($usuario));

                //---------------------------------------------

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
            return response()->json(['status' => 400,'message' => 'Correo o contraseña equivocada'], 400);
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
        $user=JWTAuth::user();
        $response['idUsuario']=$user->idUsuario;
        $response['username']=$user->username;
        $response['estado']=$user->estado;
        // $response['nro_matricula']=$user->nro_matricula;
        $response['nombres']=$user->nombres;
        $response['apellidos']=$user->apellidos;
        $response['tipo_documento']=$user->tipo_documento;
        $response['nro_documento']=$user->nro_documento;
        $response['correo']=$user->correo;
        $response['celular']=$user->celular;
        $response['sexo']=$user->sexo;
        $response['idTipoUsuario']=$user->idTipoUsuario;
        $tipo_usuario=User::select('tipo_usuario.nombre')
        ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
        ->where('usuario.idUsuario',$user->idUsuario)
        ->first();
        $response['rol']=$tipo_usuario->nombre;
        return response()->json([
            'accessToken' => JWTAuth::refresh(),
            'token_type' => 'bearer',
            'user'=>$response,
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);


        // return response()->json([
        //     'access_token' => JWTAuth::refresh(),
        //     'token_type' => 'bearer',
        //     'expires_in' => JWTAuth::factory()->getTTL() * 60
        // ]);
    }

    public function me()
    {
        return response()->json(
            JWTAuth::user()
        );
    }

    protected function respondWithToken($token)
    {
        $user=JWTAuth::user();
        $response['idUsuario']=$user->idUsuario;
        $response['username']=$user->username;
        $response['estado']=$user->estado;
        // $response['nro_matricula']=$user->nro_matricula;
        $response['nombres']=$user->nombres;
        $response['apellidos']=$user->apellidos;
        $response['tipo_documento']=$user->tipo_documento;
        $response['nro_documento']=$user->nro_documento;
        $response['correo']=$user->correo;
        $response['celular']=$user->celular;
        $response['sexo']=$user->sexo;
        $response['idTipoUsuario']=$user->idTipo_usuario;
        $tipo_usuario=User::select('tipo_usuario.nombre')
        ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
        ->where('usuario.idUsuario',$user->idUsuario)
        ->first();
        $response['rol']=$tipo_usuario->nombre;
        if ($user->confirmed==true) {
            return response()->json([
                'accessToken' => $token,
                'token_type' => 'bearer',
                'user'=>$response,
                'expires_in' => auth()->factory()->getTTL() * 60
            ]);
        }else{
            return response()->json(['status' => '400', 'message' => 'Confirme su correo electrónico para poder iniciar sesión'], 400);
        }

    }

    public function forgotPassword(Request $request){
        DB::beginTransaction();
        try {
            $usuario = User::where('correo', $request->input('correo'))->first();
            if($usuario){
                $usuario->reset_password=Str::random(25);
                $usuario->update();
                DB::commit();
                //Enviamos un msj al correo con el link de resetear password
                // \Mail::to($usuario->correo)->send(new \App\Mail\ResetPasswordMail($usuario));
                // PRUEBAS JOB---------------------------------
                dispatch(new ResetPasswordJob($usuario));
                return response()->json(['status' => '200', 'message' => 'Se envió un mensaje al correo electrónico proporcionado para continuar con la recuperación de la contraseña.'], 200);
            }else{
                return response()->json(['status' => '400', 'message' => 'El correo no se encuentra registrado para ningún usuario'], 400);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => 'Error!!'], 400);
        }
    }


    public function verifyCodePassword(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::where('reset_password', $request->input('code'))->first();
            if (! $user)
                return redirect('/');
            $user->reset_password = null;
            $user->save();
            DB::commit();
            return response()->json(['status' => '200', 'message' => 'Has confirmado correctamente tu correo!','data'=>$user], 200);
            // return redirect('/home')->with('notification', 'Has confirmado correctamente tu correo!');
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => 'Error!!'], 400);
        }
    }


    public function ResetPassword(Request $request){
        DB::beginTransaction();
        try {
            $user = User::where('username', $request->input('username'))->first();
            if ($user){
                $user->password=Hash::make($request->input('password'));
                $user->update();
                DB::commit();
                //loguearlo

                // return $user;
                $credentials = request(['username', 'password']);
                // return $credentials;
                if (! $token = auth()->attempt($credentials)) {
                    return response()->json(['error' => 'Usuario inválido'], 401);
                }

                return $this->respondWithToken($token);

                // return response()->json(['status' => '200', 'message' => 'Cambio de contraseña con éxito!'], 200);
            }else{
                return response()->json(['status' => '400', 'message' => 'El nombre de usuario no se encuentra registrado'], 400);
            }

            // return redirect('/home')->with('notification', 'Has confirmado correctamente tu correo!');
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => 'Error!!'], 400);
        }
    }

}
