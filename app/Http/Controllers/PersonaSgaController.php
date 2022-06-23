<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        //$pass=md5(md5($request->password));
        $personaSga=PersonaSga::select('per_nombres','per_apellidos','per_dni','per_mail','per_celular','per_sexo')
        // ->join('usuario','persona.idpersona','usuario.idpersona')
        ->Where('per_dni',$dni)->first();

        if(isset($personaSga)){
            return response()->json(['status' => '200', 'datos_alumno' => $personaSga], 200);
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
                $usuario->sexo=$personaSuv->per_sexo;
                return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
                // return response()->json(['status' => '200', 'message' => 'Sesión iniciada correctamente.', 'datos_alumno' => $personaSuv], 200);
            }else{
                
                return null;
            }
        }
    }
}
