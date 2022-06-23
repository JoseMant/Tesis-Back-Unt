<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\PersonaSga;
use App\PersonaSuv;
use App\Usuario;

class PersonaSgaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        //
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

    public function DatosAlumno($dni)
    {

        DB::beginTransaction();
        try {
            //$pass=md5(md5($request->password));
            $personaSga=PersonaSga::select('per_nombres','per_apellidos','per_dni','per_mail','per_celular','per_sexo','per_login')
            // ->join('usuario','persona.idpersona','usuario.idpersona')
            ->Where('per_dni',$dni)->first();

            if(isset($personaSga)){
                $usuario=new Usuario;
                $usuario->nro_matricula=$personaSga->per_login;
                $usuario->nombres=$personaSga->per_nombres;
                $usuario->apellidos=$personaSga->per_apellidos;
                $usuario->tipo_doc=1;
                $usuario->nro_doc=$personaSga->per_dni;
                $usuario->correo=$personaSga->per_mail;
                $usuario->celular=$personaSga->per_celular;
                $usuario->sexo=$personaSga->per_sexo;;
                return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
                //return response()->json(['status' => '200', 'message' => 'Sesión iniciada correctamente.', 'datos_alumno' => $personaSga], 200);
            }else{
                //$pass=md5(md5($request->password));
                $personaSuv=PersonaSuv::select('persona.per_nombres','persona.per_apepaterno','persona.per_apematerno','per_tipo_documento','persona.per_dni','persona.per_carneextranjeria',
                'persona.per_email','persona.per_celular','persona.per_sexo','alumno.idalumno')
                ->join('alumno','persona.idpersona','alumno.idpersona')
                ->Where('persona.per_dni',$dni)->first();
                if(isset($personaSuv)){
                    $usuario=new Usuario;
                    $usuario->nro_matricula=$personaSuv->idalumno;
                    $usuario->nombres=$personaSuv->per_nombres;
                    $usuario->apellidos=$personaSuv->per_apepaterno." ".$personaSuv->per_apematerno;
                    $usuario->tipo_doc=$personaSuv->per_tipo_documento;
                    $usuario->nro_doc=$personaSuv->per_dni;
                    $usuario->correo=$personaSuv->per_email;
                    $usuario->celular=$personaSuv->per_celular;
                    if ($personaSuv->per_sexo==0) {
                        $usuario->sexo="F";
                    }else{
                        $usuario->sexo="M";
                    }
                    
                    return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
                    // return response()->json(['status' => '200', 'message' => 'Sesión iniciada correctamente.', 'datos_alumno' => $personaSuv], 200);
                }else{
                    return response()->json([ 'message' => 'Alumno no encontrado.']);
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => 'Error!!!'], 400);
            //return redirect()->route('alumno.show', $resolucion->idResolucion) -> with('error', 'Error al registrar alumno');
        }
        
    }
}
