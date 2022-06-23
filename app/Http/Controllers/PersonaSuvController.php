<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PersonaSuv;
use App\Usuario;

class PersonaSuvController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return PersonaSuv::All();
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
    public function show($dni)
    {
        return PersonaSuv::select('usuario.per_pass')
        ->join('usuario','persona.idpersona','usuario.idpersona')
        ->Where('persona.per_dni',$dni)
        ->get();

        // $datosGenerales = Curso::select('curso.codigo as codigoCurso', 'curso.orden as ordenCurso', 'curso.nombre as nombreCurso', 'curso.creditos as creditosCurso', 'curso.tipo as tipoCurso',
        //                 'curso.idMencion', 'curso_docente_sede_seccion.idSede', 'curso_docente_sede_seccion.idAnio_Semestre', 'curso.idCiclo', 'curso_docente_sede_seccion.idDocente',
        //                 'curso_docente_sede_seccion.idSeccion')
        //                 ->join('curso_docente_sede_seccion', 'curso_docente_sede_seccion.idCurso', 'curso.idCurso')
        //                 ->where('curso_docente_sede_seccion.idCurso_Docente_Sede_Seccion', $idCurso_Docente_Sede_Seccion)
        //                 ->where('curso.estado', 1)
        //                 ->where('curso_docente_sede_seccion.estado', 1)
        //                 ->get();
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

    // public function Login($dni,$password)
    // {
    //     $pass=md5(md5($password));
    //     $persona=Persona::select('usuario.per_pass')
    //     ->join('usuario','persona.idpersona','usuario.idpersona')
    //     ->Where('persona.per_dni',$dni)->first();

    //     if($pass===$persona->per_pass){
    //         return true;
    //     }else{
    //         return false;
    //     }
    // }

    public function LoginRequest(Request $request)
    {
        $pass=md5(md5($request->password));
        $persona=PersonaSuv::select('usuario.per_login','usuario.per_pass','persona.per_nombres','persona.per_apepaterno','persona.per_apematerno','persona.per_dni',
        'persona.per_celular','persona.per_email','persona.per_sexo',)
        ->join('usuario','persona.idpersona','usuario.idpersona')
        ->Where('persona.per_dni',$request->dni)->first();
        


        
        if(isset($persona)){
            if($pass===$persona->per_pass){
                //return true;
                return response()->json(['status' => '200', 'message' => 'Sesión iniciada correctamente.', 'datos_alumno' => $persona], 200);
            }else{
                return "contraseña incorrecta";
            }
        }else{
            return "dni incorrecto";
        }
    }
}

















