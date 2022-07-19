<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\User;
use Illuminate\Support\Facades\Hash;


use Illuminate\Mail\Mailable;
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::All();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
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
                $usuario -> password=md5(md5($request->input('password')));
                $usuario -> nombres=strtoupper($request->input('nombres'));
                $usuario -> apellidos=strtoupper($request->input('apellidos'));
                $usuario -> nro_matricula=$request->input('nro_matricula');
                $usuario -> tipo_doc=$request->input('tipo_doc');
                $usuario -> nro_doc=$request->input('nro_doc');
                $usuario -> correo=$request->input('correo');
                $usuario -> celular=$request->input('celular');
                $usuario -> sexo=$request->input('sexo');
                $usuario -> idTipoUsuario=$request->input('idTipoUsuario');
                $usuario -> confirmation_code=Str::random(25);
                $usuario -> save();
                DB::commit();
                \Mail::to($usuario->correo)->send(new \App\Mail\NewMail($usuario));
                // \Mail::to('kevinkjjuarez@gmail.com')->send(new \App\Mail\NewMail($usuario));
                
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function verify($code)
    {
        $user = User::where('confirmation_code', $code)->first();
    
        if (! $user)
            return redirect('/');
    
        $user->confirmed = true;
        // $user->confirmation_code = null;
        $user->save();
        return response()->json(['status' => '200', 'message' => 'Has confirmado correctamente tu correo!'], 200);
        // return redirect('/home')->with('notification', 'Has confirmado correctamente tu correo!');
    }

    // public function forgotPassword(Request $request){
    //     DB::beginTransaction();
    //     try {
    //         $usuario = User::where('correo', $request->input('correo'))->first();
    //         if($usuario){
    //             $usuario->reset_password=Str::random(25);
    //             $usuario->update();
    //             DB::commit();
    //             //Enviamos un msj al correo con el link de resetear password
    //             \Mail::to($usuario->correo)->send(new \App\Mail\ResetPasswordMail($usuario));
    //             return response()->json(['status' => '200', 'message' => 'Se envió un mensaje al correo electrónico proporcionado para continuar con la recuperación de la contraseña.'], 200);
    //         }else{
    //             return response()->json(['status' => '400', 'message' => 'El correo no se encuentra registrado para ningún usuario'], 400);
    //         }
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return response()->json(['status' => '400', 'message' => 'Error!!'], 400);
    //     }
    // }


    // public function verifyCodePassword(Request $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $user = User::where('reset_password', $request->input('code'))->first();
    //         if (! $user)
    //             return redirect('/');
    //         $user->reset_password = null;
    //         $user->save();
    //         DB::commit();
    //         return response()->json(['status' => '200', 'message' => 'Has confirmado correctamente tu correo!','data'=>$user], 200);
    //         // return redirect('/home')->with('notification', 'Has confirmado correctamente tu correo!');
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return response()->json(['status' => '400', 'message' => 'Error!!'], 400);
    //     }
    // }


    // public function ResetPassword(Request $request){
    //     DB::beginTransaction();
    //     try {
    //         $user = User::where('username', $request->input('username'))->first();
    //         if ($user){
    //             $user->password=Hash::make($request->input('password'));
    //             $user->update();
    //             DB::commit();
    //             //loguearlo
    //             $credentials = request([$user->username, $user->password]);
    //             if (! $token = auth()->attempt($credentials)) {
    //                 return response()->json(['error' => 'Usuario inválido'], 401);
    //             }
                
    //             return $this->respondWithToken($token);

    //             // return response()->json(['status' => '200', 'message' => 'Cambio de contraseña con éxito!'], 200);
    //         }else{
    //             return response()->json(['status' => '400', 'message' => 'El nombre de usuario no se encuentra registrado'], 400);
    //         }
            
    //         // return redirect('/home')->with('notification', 'Has confirmado correctamente tu correo!');
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return response()->json(['status' => '400', 'message' => 'Error!!'], 400);
    //     }
    // }
}
