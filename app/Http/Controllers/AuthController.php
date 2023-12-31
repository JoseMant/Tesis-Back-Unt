<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Tramite;
use App\Historial_Estado;
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
        $this->middleware('jwt', ['except' => ['login','register','forgotPassword','verifyCodePassword','ResetPassword','verify']]);
    }

    public function register(Request $request){
        DB::beginTransaction();
        try {
            $dniValidate=User::Where('nro_documento',$request->input('nro_documento'))
            ->where('idTipo_usuario',4)->first();
            $correoValidate=User::Where('correo',$request->input('correo'))
            ->where('idTipo_usuario',4)->first();
            $usernameValidate=User::Where('username',$request->input('username'))
            ->where('idTipo_usuario',4)->first();
            if($dniValidate){
                if ($dniValidate->confirmed==0) {
                    $request->validate([
                        'password'=>['required','min:8']
                    ]);
                    $dniValidate -> password=Hash::make($request->input('password'));
                    $dniValidate -> correo=$request->input('correo');
                    $dniValidate -> direccion=$request->input('direccion');
                    $dniValidate -> fecha_nacimiento=$request->input('fecha_nacimiento');
                    $dniValidate -> celular=$request->input('celular');
                    $dniValidate -> sexo=$request->input('sexo');
                    $dniValidate -> save();
                    // PRUEBAS JOB---------------------------------
                    dispatch(new ConfirmacionCorreoJob($dniValidate,"el registro de tu usuario"));
                    DB::commit();
                    return response()->json(['status' => '200', 'message' => 'Confirmar correo!!'], 200);
                }else {
                    return response()->json(['status' => '400', 'message' => 'El dni ya se encuentra registrado!!'], 400);
                }
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
                $usuario -> apellido_paterno=strtoupper($request->input('apellido_paterno'));
                $usuario -> apellido_materno=strtoupper($request->input('apellido_materno'));
                $usuario -> apellidos=$usuario -> apellido_paterno." ".$usuario -> apellido_materno;
                $usuario -> tipo_documento=$request->input('tipo_documento');
                $usuario -> nro_documento=$request->input('nro_documento');
                $usuario -> correo=$request->input('correo');
                $usuario -> direccion=$request->input('direccion');
                $usuario -> fecha_nacimiento=$request->input('fecha_nacimiento');
                $usuario -> celular=$request->input('celular');
                $usuario -> sexo=$request->input('sexo');
                $usuario -> confirmation_code=Str::random(25);
                $usuario -> save();
                
                
                // PRUEBAS JOB---------------------------------
                dispatch(new ConfirmacionCorreoJob($usuario,true));
                DB::commit();

                //---------------------------------------------

                return response()->json(['status' => '200', 'message' => 'Confirmar correo!!'], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function login()
    {
        $credentials = request(['username', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['status' => 400,'message' => 'Credenciales incorrectas'], 400);
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
        $response['nombres']=$user->nombres;
        $response['apellidos']=$user->apellidos;
        $response['idTipo_documento']=$user->tipo_documento;
        $response['nro_documento']=$user->nro_documento;
        $response['correo']=$user->correo;
        $response['direccion']=$user->direccion;
        $response['fecha_nacimiento']=$user->fecha_nacimiento;
        $response['celular']=$user->celular;
        $response['sexo']=$user->sexo;
        $response['idTipoUsuario']=$user->idTipo_usuario;
        $tipo_usuario=User::select('tipo_usuario.nombre')
        ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
        ->where('usuario.idUsuario',$user->idUsuario)
        ->first();
        $tipo_documento=User::select('tipo_documento.nombre')
        ->join('tipo_documento','tipo_documento.idTipo_documento','usuario.tipo_documento')
        ->where('usuario.idUsuario',$user->idUsuario)
        ->first();
        $response['rol']=$tipo_usuario->nombre;
        $response['documento']=$tipo_documento->nombre;
        return response()->json([
            'accessToken' => JWTAuth::refresh(),
            'token_type' => 'bearer',
            'user'=>$response,
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
        $user=JWTAuth::user();
        $response['idUsuario']=$user->idUsuario;
        $response['username']=$user->username;
        $response['estado']=$user->estado;
        $response['nombres']=$user->nombres;
        $response['apellidos']=$user->apellidos;
        $response['idTipo_documento']=$user->tipo_documento;
        $response['nro_documento']=$user->nro_documento;
        $response['correo']=$user->correo;
        $response['direccion']=$user->direccion;
        $response['fecha_nacimiento']=$user->fecha_nacimiento;
        $response['celular']=$user->celular;
        $response['sexo']=$user->sexo;
        $response['idTipoUsuario']=$user->idTipo_usuario;
        $response['idDependencia']=$user->idDependencia;
        $tipo_usuario=User::select('tipo_usuario.nombre')
        ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
        ->where('usuario.idUsuario',$user->idUsuario)
        ->first();
        $tipo_documento=User::select('tipo_documento.nombre')
        ->join('tipo_documento','tipo_documento.idTipo_documento','usuario.tipo_documento')
        ->where('usuario.idUsuario',$user->idUsuario)
        ->first();
        $response['rol']=$tipo_usuario->nombre;
        $response['documento']=$tipo_documento->nombre;
        if ($user->estado==true) {
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
        }else{
            return response()->json(['status' => '400', 'message' => 'Usuario bloqueado'], 400);
        }
    }

    public function verify($code)
    {
        $user = User::where('confirmation_code', $code)->first();
        if (!$user)
            return response()->json(['status' => '400', 'message' => 'Código de verificación inválido o expirado'], 200);
        else if ($user->confirmed)
            return response()->json(['status' => '400', 'message' => 'El correo ha sido validado anteriormente'], 200);
        else {
            $user->confirmed = 1;
            $user->save();
            // regresar los trámites a su estado original en caso existan
            $tramites=Tramite::where('idEstado_tramite',28)
            ->where('idUsuario',$user->idUsuario)
            ->get();
            foreach ($tramites as $key => $tramite) {
                // Obtenemos el último registro del historial de cada trámite
                $historial_estado=Historial_Estado::where('idTramite',$tramite->idTramite)->orderBy('idHistorial_estado', 'desc')->first();
                //ELIMINANDO EL HISTORIAL DE CAMBIO DE CORREO PARA EVITAR ERRORES EN EL FLUJO
                $tramite->idEstado_tramite = $historial_estado->idEstado_actual;
                $historial_estado->delete();
                $tramite->update();
            }
            // ----------------------------------------------------------
          return response()->json(['status' => '200', 'message' => 'Has confirmado correctamente tu correo'], 200);
        }
    }

    public function forgotPassword(Request $request){
        DB::beginTransaction();
        try {
            $usuario = User::where('correo', $request->input('email'))->first();
            if($usuario){
                $usuario->reset_password=Str::random(25);
                $usuario->update();
                DB::commit();
                //Enviamos un msj al correo con el link de resetear password
                // \Mail::to($usuario->correo)->send(new \App\Mail\ResetPasswordMail($usuario));
                // PRUEBAS JOB---------------------------------
                dispatch(new ResetPasswordJob($usuario));
                return response()->json(['status' => '200', 'message' => utf8_encode('Se envió un mensaje al correo electrónico proporcionado para continuar con la recuperación de la contraseña.')], 200);
            }else{
                return response()->json(['status' => '400', 'message' => utf8_encode('¡El correo no se encuentra! ¿Está seguro que ya eres miembro?')], 400);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }


    public function verifyCodePassword($code)
    {
        DB::beginTransaction();
        try {
            $user = User::where('reset_password', $code)->first();
            if (! $user)
                return response()->json(['status' => '400', 'message' => utf8_encode('Enlace de recuperación de contraseña caducado')], 400);
            DB::commit();
            return response()->json(['status' => '200', 'message' =>utf8_encode( 'Has confirmado correctamente tu correo!'),'code'=>$code], 200);
            // return redirect('/home')->with('notification', 'Has confirmado correctamente tu correo!');
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => 'Error!!'], 400);
        }
    }


    public function ResetPassword(Request $request){
        DB::beginTransaction();
        try {
            $user = User::where('reset_password', $request->input('code'))->first();
            if ($user){
                $user->password=Hash::make($request->input('password'));
                $user->reset_password = null;
                $user->update();
                DB::commit();
                return response()->json(['status' => '200', 'message' => utf8_encode('Cambio de contraseña con éxito!')], 200);
            }else{
                return response()->json(['status' => '400', 'message' =>utf8_encode( 'El nombre de usuario no se encuentra registrado')], 400);
            }

            // return redirect('/home')->with('notification', 'Has confirmado correctamente tu correo!');
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

}
