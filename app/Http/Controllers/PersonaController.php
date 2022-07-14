<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\PersonaSga;
use App\PersonaSuv;
use App\PersonaSE;
use App\User;
use App\Dependencia;
use App\DependenciaURAA;
use App\Estructura;
use App\Escuela;

class PersonaController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['DatosAlumno']]);
    }
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

    public function DatosAlumno(Request $request)
    {

        DB::beginTransaction();
        try {
            $personaSga=PersonaSga::select('per_nombres','per_apellidos','per_dni','per_mail','per_celular','per_sexo'
            ,'per_login','sga_sede.sed_nombre','dependencia.dep_nombre','dependencia.sdep_id')
            ->join('perfil','persona.per_id','perfil.per_id')
            ->join('sga_sede','sga_sede.sed_id','perfil.sed_id')
            ->join('dependencia','dependencia.dep_id','perfil.dep_id')
            ->Where('per_dni',$request->input('dni'))->first();
            if(isset($personaSga)){
                $facultad=Dependencia::select('dep_nombre')
                ->Where('dep_id',$personaSga->sdep_id)->first();
                $usuario=new User;
                $usuario->nro_matricula=$personaSga->per_login;
                $usuario->nombres=$personaSga->per_nombres;
                $usuario->apellidos=$personaSga->per_apellidos;
                $usuario->tipo_doc=1;
                $usuario->nro_doc=$personaSga->per_dni;
                $usuario->correo=$personaSga->per_mail;
                $usuario->celular=$personaSga->per_celular;
                $usuario->sexo=$personaSga->per_sexo;
                $usuario->facultad=$facultad->dep_nombre;
                $usuario->escuela=$personaSga->dep_nombre;
                $usuario->sede=$personaSga->sed_nombre;
                return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
                //return response()->json(['status' => '200', 'message' => 'Sesión iniciada correctamente.', 'datos_alumno' => $personaSga], 200);
            }else{
                $personaSuv=PersonaSuv::select('persona.per_nombres','persona.per_apepaterno','persona.per_apematerno','per_tipo_documento','persona.per_dni','persona.per_carneextranjeria',
                'persona.per_email','persona.per_celular','persona.per_sexo','alumno.idalumno','patrimonio.sede.sed_descripcion','patrimonio.estructura.estr_descripcion'
                ,'patrimonio.estructura.iddependencia')
                ->join('alumno','persona.idpersona','alumno.idpersona')
                ->join('patrimonio.area','alumno.idarea','patrimonio.area.idarea')
                ->join('patrimonio.estructura','patrimonio.area.idestructura','patrimonio.estructura.idestructura')
                ->join('patrimonio.sede','alumno.idsede','patrimonio.sede.idsede')
                ->Where('persona.per_dni',$request->input('dni'))->first();
                if(isset($personaSuv)){
                    $facultad=Estructura::select('estr_descripcion')
                    ->Where('idestructura',$personaSuv->iddependencia)->first();
                    $usuario=new User;
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
                    $usuario->facultad=$facultad->estr_descripcion;
                    $usuario->escuela=$personaSuv->estr_descripcion;
                    $usuario->sede=$personaSuv->sed_descripcion;
                    return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
                    // return response()->json(['status' => '200', 'message' => 'Sesión iniciada correctamente.', 'datos_alumno' => $personaSuv], 200);
                }else{
                    // return $personaSE=PersonaSE::select('*')
                    // ->join('mencion','alumno.idMencion','mencion.idMencion')
                    // ->Where('persona.per_dni',$request->input('dni'))->first();
                    return response()->json([ 'message' => 'Alumno no encontrado.']);
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => 'Error!!!'], 400);
            //return redirect()->route('alumno.show', $resolucion->idResolucion) -> with('error', 'Error al registrar alumno');
        }
        
    }



    public function DatosAlumno2($idUnidad)
    {
        // return $idUnidad."-".$dni;
        try{
            if($idUnidad==1){ //pregrado
                // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
                $token = JWTAuth::getToken();
                $apy = JWTAuth::getPayload($token);
                $dni=$apy['nro_doc'];
                // return $user=JWTAuth::user();

                // verificar facultad en el sga o suv
                $personaSga=PersonaSga::select('per_nombres','per_apellidos','per_dni','per_mail','per_celular','per_sexo'
                ,'per_login','sga_sede.sed_nombre','dependencia.dep_nombre','dependencia.sdep_id')
                ->join('perfil','persona.per_id','perfil.per_id')
                ->join('sga_sede','sga_sede.sed_id','perfil.sed_id')
                ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                    ->Where('per_dni',$dni)->first();
                if(isset($personaSga)){
                    $facultad=Dependencia::select('dep_nombre')
                    ->Where('dep_id',$personaSga->sdep_id)->first();
                    // Seleccionamos la facultad del alumno en la bd del sistema
                    $dependenciaSGA= DependenciaURAA::where('nombre',strtoupper($facultad->dep_nombre))->first();
                    $dependenciaSGA->escuela=Escuela::where('nombre',$personaSga->dep_nombre)->get();
                    return response()->json(['status' => '200', 'facultad' => $dependenciaSGA], 200);
                }else{
                    // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
                    $token = JWTAuth::getToken();
                    $apy = JWTAuth::getPayload($token);
                    $dni=$apy['nro_doc'];
                    $personaSuv=PersonaSuv::select('persona.per_nombres','persona.per_apepaterno','persona.per_apematerno','per_tipo_documento','persona.per_dni','persona.per_carneextranjeria',
                    'persona.per_email','persona.per_celular','persona.per_sexo','alumno.idalumno','patrimonio.sede.sed_descripcion','patrimonio.estructura.estr_descripcion'
                    ,'patrimonio.estructura.iddependencia')
                    ->join('alumno','persona.idpersona','alumno.idpersona')
                    ->join('patrimonio.area','alumno.idarea','patrimonio.area.idarea')
                    ->join('patrimonio.estructura','patrimonio.area.idestructura','patrimonio.estructura.idestructura')
                    ->join('patrimonio.sede','alumno.idsede','patrimonio.sede.idsede')
                        ->Where('persona.per_dni',$dni)->first();
                    if(isset($personaSuv)){
                        $facultad=Estructura::select('estr_descripcion')
                        ->Where('idestructura',$personaSuv->iddependencia)->first();
                        $dependenciaSUV= DependenciaURAA::where('nombre',strtoupper($facultad->estr_descripcion))->first();
                        $dependenciaSUV->escuela=Escuela::where('nombre',$personaSuv->estr_descripcion)->get();
                        return response()->json(['status' => '200', 'facultad' => $dependenciaSUV], 200);
                    }else{
                        return response()->json(['status' => '400', 'message' => 'Alumno no encontrado'], 400);
                    }
                }
            }else if($idUnidad==2){ //doctorado
                // dónde?
            }else if($idUnidad==3){ //maestría
                // donde
            }else{
                // verifico facultad en segunda especialidad
                // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
                $token = JWTAuth::getToken();
                $apy = JWTAuth::getPayload($token);
                $dni=$apy['nro_doc'];
                return $personaSE=PersonaSE::select('*')
                    ->join('mencion','alumno.idMencion','mencion.idMencion')
                    ->join('segunda_especialidad','segunda_especialidad.idSegunda_Especialidad','mencion.idSegunda_Especialidad')
                    ->Where('alumno.nro_documento',$dni)->first();
            } 
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => 'Error!!!'], 400);
            //return redirect()->route('alumno.show', $resolucion->idResolucion) -> with('error', 'Error al registrar alumno');
        }
    }
}
