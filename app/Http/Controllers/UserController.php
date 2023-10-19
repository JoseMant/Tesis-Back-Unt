<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Jobs\ConfirmacionCorreoJob;
use App\Jobs\RegistroUsuarioJob;
use App\User;
use App\Tramite;
use App\Historial_Estado;
use App\Escuela;
use App\ProgramaURAA;
use App\Usuario_Programa;
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
        $usuarios=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos','usuario.apellido_paterno','usuario.apellido_materno', 'usuario.tipo_documento','usuario.nro_documento', 'usuario.correo', 'usuario.correo2', 'usuario.celular','usuario.sexo','tipo_usuario.nombre as rol',
        'usuario.confirmed','usuario.estado','usuario.idDependencia')
        ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
        ->where('usuario.idTipo_usuario','!=',1)
        ->where('usuario.idTipo_usuario','!=',4)
        ->orderBy('usuario.apellidos')
        ->get();
        foreach ($usuarios as $key => $usuario) {
            $usuario->programas = Usuario_Programa::where('idUsuario', $usuario->idUsuario)->pluck('idPrograma');
            if (count($usuario->programas) > 0) {
                $programa = ProgramaURAA::find($usuario->programas[0]);
                $usuario->idFacultad = $programa->idDependencia;
            } else {
                $usuario->idFacultad = $usuario->idDependencia;
            }
        }
        return response()->json($usuarios, 200);
    }
    public function getUsuariosUraa()
    {
        $usuarios=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos','usuario.apellido_paterno','usuario.apellido_materno', 'usuario.tipo_documento','usuario.nro_documento', 'usuario.correo', 'usuario.correo2', 'usuario.celular','usuario.sexo','tipo_usuario.nombre as rol',
        'usuario.confirmed','usuario.estado','usuario.idDependencia')
        ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
        ->where('usuario.idTipo_usuario',2)
        ->get();
        return response()->json($usuarios, 200);
    }
    
    public function buscar(Request $request){
        if ($request->query('query')!="") {
            $usuarios=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos', 'usuario.apellido_paterno', 'usuario.apellido_materno', 'usuario.tipo_documento','usuario.nro_documento', 'usuario.correo', 'usuario.correo2', 'usuario.celular','usuario.sexo','tipo_usuario.nombre as rol','usuario.estado','usuario.idDependencia')
            ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
            ->where('usuario.idTipo_usuario','!=',1)
            ->where(function($query) use ($request)
                {
                    $query->where('usuario.nombres','LIKE', '%'.$request->query('query').'%')
                    ->orWhere('tipo_usuario.nombre','LIKE', '%'.$request->query('query').'%')
                    ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('query').'%');
                })
            ->orderBy('usuario.apellidos')
            ->get();
            foreach ($usuarios as $key => $usuario) {
                $usuario->programas = Usuario_Programa::where('idUsuario', $usuario->idUsuario)->pluck('idPrograma');
                if (count($usuario->programas) > 0) {
                    $programa = ProgramaURAA::find($usuario->programas[0]);
                    $usuario->idFacultad = $programa->idDependencia;
                } else {
                    $usuario->idFacultad = $usuario->idDependencia;
                }
            }
        }else{
            $usuarios=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos', 'usuario.apellido_paterno', 'usuario.apellido_materno','usuario.tipo_documento','usuario.nro_documento',
            'usuario.correo', 'usuario.correo2', 'usuario.celular','usuario.sexo','tipo_usuario.nombre as rol','usuario.estado','usuario.idDependencia')
            ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
            ->where('usuario.idTipo_usuario','!=',1)
            ->where('usuario.idTipo_usuario','!=',4)
            ->orderBy('usuario.apellidos')
            ->get();
            foreach ($usuarios as $key => $usuario) {
                $usuario->programas = Usuario_Programa::where('idUsuario', $usuario->idUsuario)->pluck('idPrograma');
                if (count($usuario->programas) > 0) {
                    $programa = ProgramaURAA::find($usuario->programas[0]);
                    $usuario->idFacultad = $programa->idDependencia;
                } else {
                    $usuario->idFacultad = $usuario->idDependencia;
                }
            }
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
            $usernameValidate=User::where('username',$request->username)->first();
            if ($usernameValidate) {
                return response()->json(['status' => '400', 'message' => 'El nombre de usuario ya se encuentra registrado!!'], 400);
            }else{
                $tipoUsuario_dni_validate=User::where('idTipo_usuario',$request->idTipo_usuario)
                ->where('nro_documento',$request->nro_documento)
                ->first();
                if ($tipoUsuario_dni_validate) {
                    return response()->json(['status' => '400', 'message' => 'El usuario ya se encuentra registrado!!'], 400);
                }
            }

            //NUEVO USUARIO
            $usuario=new User();
            $usuario->idTipo_usuario=$request->idTipo_usuario;
            $usuario->username=$request->username;
            $usuario->password=Hash::make($request->nro_documento);
            $usuario->nro_documento=$request->nro_documento;
            $usuario->nombres=$request->nombres;
            $usuario->apellido_paterno=$request->apellido_paterno;
            $usuario->apellido_materno=$request->apellido_materno;
            $usuario->apellidos=$request->apellido_paterno.' '.$request->apellido_materno;
            $usuario->tipo_documento=$request->tipo_documento;

            if ($request->idDependencia) {
                if($usuario->idTipo_usuario!=5){
                    $usuario->idDependencia=$request->idDependencia;
                }
            }
            
            $usuario->correo=$request->correo;

            if($request->correo2){
                $usuario->correo2=$request->correo2;
            }
            
            $usuario->celular=$request->celular;
            $usuario->sexo=$request->sexo;
            $usuario->confirmed=1;
            $usuario->confirmation_code=null;
            $usuario->reset_password=null;
            $usuario->estado=1;
            $usuario->save();
            $usuario=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos','usuario.apellido_paterno', 'usuario.apellido_materno','usuario.tipo_documento','usuario.nro_documento',
            'usuario.correo', 'usuario.correo2', 'usuario.celular','usuario.sexo','tipo_usuario.nombre as rol','usuario.confirmed','usuario.estado','usuario.idDependencia')
            ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
            ->where('usuario.idUsuario',$usuario->idUsuario)
            ->first();
            $rol=$usuario->rol;

            if ($usuario->idTipo_usuario==5) {
                $programas = $request->programas;
                foreach($programas as $progra)
                {
                    $usuario_programa = new Usuario_Programa();
                    $usuario_programa->idUsuario = $usuario->idUsuario;
                    $usuario_programa->idPrograma = $progra;
                    $usuario_programa->status = 1;
                    $usuario_programa->save();
                }

                $programa=ProgramaURAA::find($programas[0]);
                $usuario->idFacultad=$programa->idDependencia;
            }
    
            $usuario->programas=$request->programas;

            //Correo de creación de usuario
            dispatch(new RegistroUsuarioJob($usuario,$rol));
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
            $usuario->nro_documento=$request->nro_documento;
            $usuario->tipo_documento=$request->tipo_documento;
            $usuario->username=$request->username;
            $usuario->nombres=$request->nombres;
            $usuario->apellido_paterno=$request->apellido_paterno;
            $usuario->apellido_materno=$request->apellido_materno;
            $usuario->apellidos=$request->apellido_paterno.' '.$request->apellido_materno;

            if ($request->idDependencia) {
                if($usuario->idTipo_usuario!=5){
                    $usuario->idDependencia=$request->idDependencia;
                }
            }

            $usuario->correo=$request->correo;

            if($request->correo2) $usuario->correo2=$request->correo2;

            $usuario->sexo=$request->sexo;
            $usuario->celular=$request->celular;
            $usuario->estado=$request->estado;
            $usuario->update();
            $usuario=User::select('usuario.idUsuario','usuario.idTipo_usuario','usuario.username','usuario.nombres','usuario.apellidos',
            'usuario.tipo_documento','usuario.nro_documento', 'usuario.correo','usuario.correo2','usuario.celular','usuario.sexo',
            'tipo_usuario.nombre as rol','usuario.confirmed','usuario.estado','usuario.idDependencia')
            ->join('tipo_usuario','tipo_usuario.idTipo_usuario','usuario.idTipo_usuario')
            ->where('usuario.idUsuario',$id)
            ->first();

            if ($usuario->idTipo_usuario==5) {
                 
                //Eliminar todos los programas previos registrados
                $programas_prev = Usuario_Programa::where('idUsuario',$usuario->idUsuario)->get();
                foreach($programas_prev as $progra_prev)
                {
                    $programaEliminado = Usuario_Programa::find($progra_prev->idUsuario_programa);
                    $programaEliminado->delete();
                }

                //Registrar los programas nuevos
                $programas = $request->programas;
                foreach($programas as $progra)
                {
                    $usuario_programa = new Usuario_Programa();
                    $usuario_programa->idUsuario = $usuario->idUsuario;
                    $usuario_programa->idPrograma = $progra;
                    $usuario_programa->status = 1;
                    $usuario_programa->save();
                }

                $programa=ProgramaURAA::find($programas[0]);
                $usuario->idFacultad=$programa->idDependencia;
            }
            
            $usuario->programas=$request->programas;
            
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
                $usuario->confirmed=0;
                $usuario->confirmation_code=Str::random(25);
                // $usuario->update();
                dispatch(new ConfirmacionCorreoJob($usuario,false));
                // Cambiar todos los trámites no finalizados al estado 28
                $tramites=Tramite::where('idEstado_tramite','!=',15)
                ->where('idEstado_tramite','!=',29)
                ->where('idUsuario',$idUsuario)
                ->get();
                foreach ($tramites as $key => $tramite) {
                    //REGISTRAMOS EL ESTADO DEL TRÁMITE
                    $historial_estados=new Historial_Estado;
                    $historial_estados->idTramite=$tramite->idTramite;
                    $historial_estados->idUsuario=$idUsuario;
                    $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                    $historial_estados->idEstado_nuevo=28;
                    $historial_estados->fecha=date('Y-m-d h:i:s');
                    $historial_estados->save();
                    $tramite->idEstado_tramite = $historial_estados->idEstado_nuevo;
                    $tramite->update();
                }
            }
            $usuario->direccion=trim($request->direccion);
            $usuario->fecha_nacimiento=trim($request->fecha_nacimiento);
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

    public function resetPassword(Request $request){
        DB::beginTransaction();
        try {
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $usuario=User::find($idUsuario);
            if (!password_verify($request->currentPassword,$usuario->password)) {
                return response()->json(['status' => '400', 'message' => "Contraseña actual no coincide"], 400);
            }else {
                $usuario->password=Hash::make(trim($request->newPassword));
                $usuario->update();
                DB::commit();
                return response()->json(['status' => '200', 'message' => "Contraseña actualizada correctamente"], 200);
            }            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }
}
