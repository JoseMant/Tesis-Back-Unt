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
    public function __construct()
    {
        $this->middleware('jwt');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $usuarios=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos','usuario.tipo_documento','usuario.nro_documento',
        'usuario.correo','usuario.celular','usuario.sexo','tipo_usuario.nombre as rol')
        ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
        ->where('usuario.idTipo_usuario','!=',1)
        ->get();
        return response()->json([$usuarios], 200);
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

        if (!$user)
            return response()->json(['status' => '400', 'message' => 'Código de verificación inválido o expirado'], 200);
        else if ($user->confirmed)
            return response()->json(['status' => '400', 'message' => 'El correo ha sido validado anteriormente'], 200);
        else {
          $user->confirmed = 1;
          // $user->confirmation_code = null;
          $user->save();
          return response()->json(['status' => '200', 'message' => 'Has confirmado correctamente tu correo'], 200);
        }
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
