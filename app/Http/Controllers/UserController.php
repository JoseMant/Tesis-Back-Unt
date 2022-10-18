<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Jobs\ConfirmacionCorreoJob;
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
        'usuario.correo','usuario.celular','usuario.sexo','tipo_usuario.nombre as rol','usuario.confirmed','usuario.estado')
        ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
        ->where('usuario.idTipo_usuario','!=',1)
        ->get();
        return response()->json($usuarios, 200);
    }
    public function getUsuariosUraa()
    {
        $usuarios=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos','usuario.tipo_documento','usuario.nro_documento',
        'usuario.correo','usuario.celular','usuario.sexo','tipo_usuario.nombre as rol')
        ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
        ->where('usuario.idTipo_usuario',2)
        ->get();
        return response()->json($usuarios, 200);
    }
    
    public function buscar(Request $request){
        if ($request->query('query')!="") {
            $usuarios=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos','usuario.tipo_documento','usuario.nro_documento',
            'usuario.correo','usuario.celular','usuario.sexo','tipo_usuario.nombre as rol')
            ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
            ->where('usuario.idTipo_usuario','!=',1)
            ->where(function($query) use ($request)
                {
                    $query->where('usuario.nombres','LIKE', '%'.$request->query('query').'%')
                    ->orWhere('tipo_usuario.nombre','LIKE', '%'.$request->query('query').'%')
                    ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('query').'%');
                })
            ->get();
        }else{
            $usuarios=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos','usuario.tipo_documento','usuario.nro_documento',
            'usuario.correo','usuario.celular','usuario.sexo','tipo_usuario.nombre as rol')
            ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
            ->where('usuario.idTipo_usuario','!=',1)
            ->get();
        }
        return response()->json($usuarios, 200);
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
            $usuario=new User();
            $usuario->idTipo_usuario=$request->idTipo_usuario;
            $usuario->username=$request->username;
            $usuario->password=$request->nro_documento;
            $usuario->nombres=$request->nombres;
            $usuario->apellidos=$request->apellidos;
            $usuario->tipo_documento=$request->tipo_documento;
            $usuario->nro_documento=$request->nro_documento;
            $usuario->correo=$request->correo;
            $usuario->celular=$request->celular;
            $usuario->sexo=$request->sexo;
            $usuario->confirmed=1;
            $usuario->confirmation_code=null;
            $usuario->reset_password=null;
            $usuario->estado=1;
            $usuario->save();
            $usuario=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos','usuario.tipo_documento','usuario.nro_documento',
            'usuario.correo','usuario.celular','usuario.sexo','tipo_usuario.nombre as rol','usuario.confirmed','usuario.estado')
            ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
            ->where('usuario.idUsuario',$usuario->idUsuario)
            ->first();
            DB::commit();
            return response()->json($usuario, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
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
        DB::beginTransaction();
        try {
            $usuario=User::find($id);
            $usuario->idTipo_usuario=$request->idTipo_usuario;
            $usuario->username=$request->username;
            $usuario->nombres=$request->nombres;
            $usuario->apellidos=$request->apellidos;
            $usuario->celular=$request->celular;
            $usuario->estado=$request->estado;
            $usuario->update();
            $usuario=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos','usuario.tipo_documento','usuario.nro_documento',
            'usuario.correo','usuario.celular','usuario.sexo','tipo_usuario.nombre as rol','usuario.confirmed','usuario.estado')
            ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
            ->where('usuario.idUsuario',$id)
            ->first();
            DB::commit();
            return response()->json($usuario, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
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

    public function settings(Request $request){
        DB::beginTransaction();
        try {
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $usuario=User::find($idUsuario);
            $usuario->celular=trim($request->celular);
            if ($usuario->correo!==$request->correo) {
                $usuario->correo=trim($request->correo);
                dispatch(new ConfirmacionCorreoJob($usuario));
            }
            $usuario->update();

            $response['idUsuario']=$usuario->idUsuario;
            $response['username']=$usuario->username;
            $response['estado']=$usuario->estado;
            $response['nombres']=$usuario->nombres;
            $response['apellidos']=$usuario->apellidos;
            $response['tipo_documento']=$usuario->tipo_documento;
            $response['nro_documento']=$usuario->nro_documento;
            $response['correo']=$usuario->correo;
            $response['celular']=$usuario->celular;
            $response['sexo']=$usuario->sexo;
            $response['idTipoUsuario']=$usuario->idTipo_usuario;
            $tipo_usuario=User::select('tipo_usuario.nombre')
            ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
            ->where('usuario.idUsuario',$usuario->idUsuario)
            ->first();
            $response['rol']=$tipo_usuario->nombre;
            DB::commit();
            return response()->json($response, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
}
