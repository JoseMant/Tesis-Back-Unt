<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Usuario;



use Illuminate\Mail\Mailable;
class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Usuario::All();
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
            $usuarioValidate=Usuario::Where('nro_doc',$request->input('nro_doc'))->first();
            if(isset($usuarioValidate)){
              return response()->json(['status' => '400', 'message' => 'El usuario ya se encuentra registrado!!'], 400);
            }else{
                
                $request->validate([
                    'password'=>['required','min:8']
                ]);
                $usuario = new Usuario;
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
                $usuario -> confirmation_code="1234444444444444444444444";
                $usuario -> save();
                
                // \Mail::to('kevinkjjuarez@gmail.com')->send(new \App\Mail\NewMail($usuario));
                DB::commit();
                return response()->json(['status' => '200', 'message' => 'Usuario registrado correctamente'], 200);
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
        // $user = Usuario::where('confirmation_code', $code)->first();
    
        // if (! $user)
        //     return redirect('/');
    
        // $user->confirmed = true;
        // $user->confirmation_code = null;
        // $user->save();
        return response()->json(['status' => '200', 'message' => 'Has confirmado correctamente tu correo!'], 200);
        // return redirect('/home')->with('notification', 'Has confirmado correctamente tu correo!');
    }
}
