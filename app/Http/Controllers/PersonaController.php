<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Arr;
use App\PersonaSga;
use App\PersonaSuv;
use App\PersonaSE;
use App\User;
use App\Dependencia;
use App\DependenciaURAA;
use App\Estructura;
use App\Escuela;
use App\Alumno;
use App\Segunda_Especialidad;
use App\Mencion;

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
            // verificamos en la bd de SE
            $personaSE=PersonaSE::select('alumno.codigo','alumno.nombre','alumno.paterno','alumno.materno','alumno.idTipo_documento'
            ,'alumno.nro_documento','alumno.correo_personal','alumno.celular','alumno.sexo')
                    // ->join('mencion','alumno.idMencion','mencion.idMencion')
                    // ->join('segunda_especialidad','segunda_especialidad.idSegunda_Especialidad','mencion.idSegunda_Especialidad')
                    // ->join('matricula','alumno.idAlumno','matricula.idAlumno')
                    // ->join('sede','matricula.idSede','sede.idSede')
                    ->Where('alumno.nro_documento',$request->input('dni'))->first();
            if($personaSE){
                $usuario=new User;
                $usuario->nro_matricula=$personaSE->codigo;
                $usuario->nombres=$personaSE->nombre;
                $usuario->apellidos=$personaSE->paterno." ".$personaSE->materno;
                $usuario->tipo_documento=$personaSE->idTipo_documento;
                $usuario->nro_documento=$personaSE->nro_documento;
                $usuario->correo=$personaSE->correo_personal;
                $usuario->celular=$personaSE->celular;
                $usuario->sexo=$personaSE->sexo;
                // $usuario->dependencia=$personaSE->dependencia;
                // $usuario->mencion=$personaSE->mencion;
                // $usuario->sede=$personaSE->sede;
                return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
            }else{
                // verificamos en la bd del suv
                $personaSuv=PersonaSuv::select('persona.per_nombres','persona.per_apepaterno','persona.per_apematerno','per_tipo_documento','persona.per_dni','persona.per_carneextranjeria',
                'persona.per_email','persona.per_celular','persona.per_sexo','alumno.idalumno')
                ->join('alumno','persona.idpersona','alumno.idpersona')
                // ->join('patrimonio.area','alumno.idarea','patrimonio.area.idarea')
                // ->join('patrimonio.estructura','patrimonio.area.idestructura','patrimonio.estructura.idestructura')
                // ->join('patrimonio.sede','alumno.idsede','patrimonio.sede.idsede')
                ->Where('persona.per_dni',$request->input('dni'))->first();
                if($personaSuv){
                    $facultad=Estructura::select('estr_descripcion')
                    ->Where('idestructura',$personaSuv->iddependencia)->first();
                    $usuario=new User;
                    $usuario->nro_matricula=$personaSuv->idalumno;
                    $usuario->nombres=$personaSuv->per_nombres;
                    $usuario->apellidos=$personaSuv->per_apepaterno." ".$personaSuv->per_apematerno;
                    $usuario->tipo_documento=$personaSuv->per_tipo_documento;
                    $usuario->nro_documento=$personaSuv->per_dni;
                    $usuario->correo=$personaSuv->per_email;
                    $usuario->celular=$personaSuv->per_celular;
                    if ($personaSuv->per_sexo==0) {
                        $usuario->sexo="F";
                    }else{
                        $usuario->sexo="M";
                    }
                    // $usuario->facultad=$facultad->estr_descripcion;
                    // $usuario->escuela=$personaSuv->estr_descripcion;
                    // $usuario->sede=$personaSuv->sed_descripcion;
                    return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
                }else{
                    // verificamos en la bd del sga
                    $personaSga=PersonaSga::select('per_nombres','per_apellidos','per_dni','per_mail','per_celular','per_sexo'
                    ,'per_login')
                    // ->join('perfil','persona.per_id','perfil.per_id')
                    // ->join('sga_sede','sga_sede.sed_id','perfil.sed_id')
                    // ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                    ->Where('per_dni',$request->input('dni'))->first();
                    if($personaSga){
                        $facultad=Dependencia::select('dep_nombre')
                        ->Where('dep_id',$personaSga->sdep_id)->first();
                        $usuario=new User;
                        $usuario->nro_matricula=$personaSga->per_login;
                        $usuario->nombres=$personaSga->per_nombres;
                        $usuario->apellidos=$personaSga->per_apellidos;
                        $usuario->tipo_documento=1;
                        $usuario->nro_documento=$personaSga->per_dni;
                        $usuario->correo=$personaSga->per_mail;
                        $usuario->celular=$personaSga->per_celular;
                        $usuario->sexo=$personaSga->per_sexo;
                        // $usuario->dependencia=$facultad->dep_nombre;
                        // $usuario->escuela=$personaSga->dep_nombre;
                        // $usuario->sede=$personaSga->sed_nombre;
                        return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
                    }else{
                        return response()->json([ 'message' => 'Alumno no encontrado.']);
                    }
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
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $dni=$apy['nro_documento'];
            // return $user=JWTAuth::user();
            if($idUnidad==1){ //pregrado
                //Obtenemos escuela(s) de la persona que inicia sesión
                $alumnoEscuelas=PersonaSga::select('per_nombres','per_apellidos','per_dni','per_mail','per_celular','per_sexo'
                ,'per_login','sga_sede.sed_nombre','dependencia.dep_id','dependencia.dep_nombre','dependencia.sdep_id')
                ->join('perfil','persona.per_id','perfil.per_id')
                ->join('sga_sede','sga_sede.sed_id','perfil.sed_id')
                ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                ->Where('per_dni',$dni)->get();
                if (count($alumnoEscuelas)>0) {
                    //Guardamos la(s) facultad(es) a la que pertenece dicho alumno
                    $facultades=[];
                    foreach ($alumnoEscuelas as $key => $escuela) {
                        $facultad=Dependencia::select('dep_nombre')->Where('dep_id',$escuela->sdep_id)->first();
                        array_push($facultades, DependenciaURAA::where('nombre',strtoupper($facultad->dep_nombre))->first());
                    }
                    //Recorremos la(s) facultad(es) y escuela(s) para ir añadiendo cada escuela a la facultad que pertenece y no se repitan las facultades
                    foreach ($facultades as $key => $facultad) {
                        $escuelas=[];
                        foreach ($alumnoEscuelas as $key => $escuela) {
                            $facultadEscuela=Dependencia::select('dep_nombre')->Where('dep_id',$escuela->sdep_id)->first();
                            if ($facultad['nombre']===strtoupper($facultadEscuela['dep_nombre'])) {
                                $escuelaSede=Escuela::where('idSGA_PREG',$escuela->dep_id)->first();
                                $escuelaSede->nro_matricula=$escuela->per_login;
                                $escuelaSede->sede=$escuela->sed_nombre;
                                array_push($escuelas,$escuelaSede);
                            }
                        }
                        $facultad->escuelas=$escuelas;
                    }
                    return response()->json(['status' => '200', 'facultades' => $facultades], 200); 
                }
                else{
                    //Obtenemos datos de la persona que inicia sesión
                    $personaSuv=PersonaSuv::select('persona.idpersona')->Where('persona.per_dni',$dni)->first();
                    if ($personaSuv) {
                       //Obtenemos las escuela(s) a la(s) que pertenece dicha persona
                        $alumnoEscuelas=Alumno::select('alumno.idalumno','patrimonio.sede.sed_descripcion','patrimonio.estructura.idestructura','patrimonio.estructura.estr_descripcion'
                        ,'patrimonio.estructura.iddependencia')
                        ->join('patrimonio.area','alumno.idarea','patrimonio.area.idarea')
                        ->join('patrimonio.estructura','patrimonio.area.idestructura','patrimonio.estructura.idestructura')
                        ->join('patrimonio.sede','alumno.idsede','patrimonio.sede.idsede')
                        ->Where('alumno.idpersona',$personaSuv['idpersona'])
                        // Obtener la escuela activa para el trámite de carné
                        // ->Where('alumno.alu_estado',1)
                        ->get();
                        //Guardamos la(s) facultad(es) a la que pertenece dicho alumno
                        $facultades=[];
                        foreach ($alumnoEscuelas as $key => $escuela) {
                            $facultad=Estructura::select('estr_descripcion')->Where('idestructura',$escuela->iddependencia)->first();
                            array_push($facultades, DependenciaURAA::where('nombre',strtoupper($facultad->estr_descripcion))->first());
                        }

                        //Recorremos la(s) facultad(es) y escuela(s) para ir añadiendo cada escuela a la facultad que pertenece y no se repitan las facultades
                        foreach ($facultades as $key => $facultad) {
                            $escuelas=[];
                            foreach ($alumnoEscuelas as $key => $escuela) {
                                $facultadEscuela=Estructura::select('estr_descripcion')->Where('idestructura',$escuela->iddependencia)->first();
                                if ($facultad['nombre']===strtoupper($facultadEscuela['estr_descripcion'])) {
                                    $escuelaSede=Escuela::where('idSUV_PREG',$escuela->idestructura)->first();
                                    $escuelaSede->nro_matricula=$escuela->idalumno;
                                    $escuelaSede->sede=$escuela->sed_descripcion;
                                    array_push($escuelas, $escuelaSede);
                                }
                            }
                            $facultad->escuelas=$escuelas;
                        }
                        return response()->json(['status' => '200', 'facultades' => $facultades], 200); 
                    }else {
                        return response()->json(['status' => '400', 'mesagge' => 'Alumno no encontrado'], 400); 
                    }
                }
            }else if($idUnidad==2){ //doctorado
                // dónde?
            }else if($idUnidad==3){ //maestría
                // donde
            }else{
                $alumnoMenciones=PersonaSE::select('mencion.idMencion','mencion.nombre','idSegunda_Especialidad')
                ->join('mencion','alumno.idMencion','mencion.idMencion')
                ->Where('alumno.nro_documento',$dni)
                ->get();
                //Guardamos la(s) segunda especialidad(es) a la que pertenece dicho alumno
                $facultades=[];
                foreach ($alumnoMenciones as $key => $mencion) {
                    $facultad=Segunda_Especialidad::select('nombre')->Where('idSegunda_Especialidad',$mencion->idSegunda_Especialidad)->first();
                    array_push($facultades, DependenciaURAA::where('nombre',strtoupper($facultad->nombre))->first());
                }
                //Recorremos la(s) segunda especialidad(es) y mencion(s) para ir añadiendo cada escuela a la facultad que pertenece y no se repitan las facultades
                foreach ($facultades as $key => $facultad) {
                    $menciones=[];
                    foreach ($alumnoMenciones as $key => $mencion) {
                        $facultadMencion=Segunda_Especialidad::select('nombre')->Where('idSegunda_Especialidad',$mencion->idSegunda_Especialidad)->first();
                        if ($facultad['nombre']===strtoupper($facultadMencion['nombre'])) {
                            $mencionSede=Mencion::where('idSGA_SE',$mencion->idMencion)->first();
                            // $mencionSede->nro_matricula=$mencion->idalumno;
                            // $mencionSede->sede=$mencion->sed_descripcion;
                            array_push($menciones, $mencionSede);
                        }
                    }
                    $facultad->menciones=$menciones;
                }
                return response()->json(['status' => '200', 'facultades' => $facultades], 200); 
                //-----------------------------------
                // //obtenemos uno(cualquiera) para sacar la dependencia
                // $personaSE=PersonaSE::select('segunda_especialidad.nombre')
                //     ->join('mencion','alumno.idMencion','mencion.idMencion')
                //     ->join('segunda_especialidad','segunda_especialidad.idSegunda_Especialidad','mencion.idSegunda_Especialidad')
                //     ->join('matricula','alumno.idAlumno','matricula.idAlumno')
                //     ->join('sede','matricula.idSede','sede.idSede')
                //     ->Where('alumno.nro_documento',$dni)->first();
                // $dependencias = DependenciaURAA::where('nombre',strtoupper($personaSE->nombre))->get();
                // foreach($dependencias as $dependencia){
                //     $dependencia->menciones=PersonaSE::select('alumno.idAlumno','alumno.codigo as nro_matricula','mencion.*')
                //     ->join('mencion','alumno.idMencion','mencion.idMencion')
                //     ->Where('alumno.nro_documento',$dni)->get();
                //     foreach($dependencia->menciones as $mencion){
                //         $sede=PersonaSE::select('sede.nombre')
                //         ->join('matricula','alumno.idAlumno','matricula.idAlumno')
                //         ->join('sede','matricula.idSede','sede.idSede')
                //         ->Where('alumno.idAlumno',$mencion->idAlumno)->first();
                //         $mencion->sede=$sede->nombre;
                //     }
                // }
                //------------------------------------
                // $dependencia->menciones=PersonaSE::select('alumno.idAlumno','alumno.codigo as nro_matricula','mencion.*')
                // ->join('mencion','alumno.idMencion','mencion.idMencion')
                // ->Where('alumno.nro_documento',$dni)->get();
                // foreach($dependencia->menciones as $mencion){
                //     $sede=PersonaSE::select('sede.nombre')
                //     ->join('matricula','alumno.idAlumno','matricula.idAlumno')
                //     ->join('sede','matricula.idSede','sede.idSede')
                //     ->Where('alumno.idAlumno',$mencion->idAlumno)->first();
                //     $mencion->sede=$sede->nombre;
                // }
                return $dependencias;
            } 
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => 'Error!!!'], 400);
            //return redirect()->route('alumno.show', $resolucion->idResolucion) -> with('error', 'Error al registrar alumno');
        }
    }
}
