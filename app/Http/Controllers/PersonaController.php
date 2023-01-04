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
use Illuminate\Support\Facades\Http;

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
            // $personaPos= Http::get('http://www.epgnew.unitru.edu.pe/epg_admin/api/matricula.php', [
            //     'dni' => $request->input('dni')
            //   ]);
            // // return $personaPos[0]["status"];
            // if ($personaPos[0]["status"]=="200"){
            //         $usuario=new User;
            //         $usuario->nro_matricula=$personaPos[0]["codigo"];
            //         $usuario->nombres=$personaPos[0]["nombres"];
            //         $usuario->apellidos=$personaPos[0]["ape_paterno"]." ".$personaPos[0]["ape_materno"];
            //         $usuario->tipo_documento=1;
            //         $usuario->nro_documento=$personaPos[0]["dni"];
            //         $usuario->correo=$personaPos[0]["alu_email"];
            //         // $usuario->celular=$personaSE->celular;
            //         // $usuario->sexo=$personaSE->sexo;
            //         return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
            //     return $personaPos[0];
            // }else{
                // verificamos en la bd de SE
                $personaSE=PersonaSE::select('alumno.codigo','alumno.nombre','alumno.paterno','alumno.materno','alumno.idTipo_documento'
                ,'alumno.nro_documento','alumno.correo_personal','alumno.celular','alumno.sexo','alumno.direccion','alumno.nacimiento')
                        ->Where('alumno.nro_documento',$request->input('dni'))->first();
                if($personaSE){
                    $usuario=new User;
                    $usuario->nro_matricula=$personaSE->codigo;
                    $usuario->nombres=$personaSE->nombre;
                    $usuario->apellidos=$personaSE->paterno." ".$personaSE->materno;
                    $usuario->tipo_documento=$personaSE->idTipo_documento;
                    $usuario->nro_documento=$personaSE->nro_documento;
                    $usuario->correo=$personaSE->correo_personal;
                    $usuario->direccion=$personaSE->direccion;
                    $usuario->fecha_nacimiento=$personaSE->nacimiento;
                    $usuario->celular=$personaSE->celular;
                    $usuario->sexo=$personaSE->sexo;
                    return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
                }else{
                    // verificamos en la bd del suv
                    $personaSuv=PersonaSuv::select('persona.per_nombres','persona.per_apepaterno','persona.per_apematerno','per_tipo_documento','persona.per_dni','persona.per_carneextranjeria',
                    'persona.per_email','persona.per_celular','persona.per_sexo','persona.per_direccionlocal','persona.per_fechanacimiento','alumno.idalumno')
                    ->join('alumno','persona.idpersona','alumno.idpersona')
                    ->Where('persona.per_dni',$request->input('dni'))->first();
                    if($personaSuv){
                        $usuario=new User;
                        $usuario->nro_matricula=$personaSuv->idalumno;
                        $usuario->nombres=$personaSuv->per_nombres;
                        $usuario->apellidos=$personaSuv->per_apepaterno." ".$personaSuv->per_apematerno;
                        $usuario->tipo_documento=$personaSuv->per_tipo_documento;
                        $usuario->nro_documento=$personaSuv->per_dni;
                        $usuario->correo=$personaSuv->per_email;
                        $usuario->direccion=$personaSuv->per_direccionlocal;
                        $usuario->fecha_nacimiento=$personaSuv->per_fechanacimiento;
                        $usuario->celular=$personaSuv->per_celular;
                        if ($personaSuv->per_sexo==0) {
                            $usuario->sexo="F";
                        }else{
                            $usuario->sexo="M";
                        }
                        return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
                    }else{
                        // verificamos en la bd del sga
                        $personaSga=PersonaSga::select('per_nombres','per_apellidos','per_dni','per_mail','per_celular','per_sexo'
                        ,'per_login','per_direccion','per_fnaci')
                        ->Where('per_dni',$request->input('dni'))->first();
                        if($personaSga){
                            $usuario=new User;
                            $usuario->nro_matricula=$personaSga->per_login;
                            $usuario->nombres=$personaSga->per_nombres;
                            $usuario->apellidos=$personaSga->per_apellidos;
                            $usuario->tipo_documento=1;
                            $usuario->nro_documento=$personaSga->per_dni;
                            $usuario->correo=$personaSga->per_mail;
                            $usuario->direccion=$personaSga->per_direccion;
                            $usuario->fecha_nacimiento=$personaSga->per_fnaci;
                            $usuario->celular=$personaSga->per_celular;
                            $usuario->sexo=$personaSga->per_sexo;
                            return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
                        }else{
                            // return response()->json([ 'message' => 'Alumno no encontrado.']);
                            return response()->json(['status' => '400', 'message' => 'Alumno no encontrado. Favor de enviar nombres completos, facultad, escuela, nro. matricula, 
                            dni, dirección, celular, fecha de nacimiento y unidad (Pregrado o segunda especialidad) al correo uraa@unitru.edu.pe'], 400);
                        }
                    }
                }  
            // }      
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
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
            // $dni='74660603';
            // return $user=JWTAuth::user();
            if($idUnidad==1){ //pregrado
                $facultadesTotales=[];
                //Obtenemos escuela(s) de la persona que inicia sesión
                $alumnoEscuelasSGA=PersonaSga::select('per_nombres','per_apellidos','per_dni','per_mail','per_celular','per_sexo'
                ,'per_login','sga_sede.sed_nombre','dependencia.dep_id','dependencia.dep_nombre','dependencia.sdep_id')
                ->join('perfil','persona.per_id','perfil.per_id')
                ->join('sga_sede','sga_sede.sed_id','perfil.sed_id')
                ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                ->Where('perfil.pfl_cond','AL')
                ->Where('per_dni',$dni)->get();
                if (count($alumnoEscuelasSGA)>0) {
                    //Guardamos la(s) facultad(es) a la que pertenece dicho alumno
                    $facultades=[];
                    foreach ($alumnoEscuelasSGA as $key => $escuela) {
                        $facultad=Dependencia::select('dep_nombre')->Where('dep_id',$escuela->sdep_id)->first();
                        $existe= false;
                        foreach ($facultades as $value) {
                            if (strtoupper($value->nombre)==strtoupper($facultad->dep_nombre)) {
                                $existe = true;
                                break;
                            } 
                        }
                        if(!$existe) {
                            // redirect(base_url()."bienvenidos");
                            array_push($facultades, DependenciaURAA::where('nombre',strtoupper($facultad->dep_nombre))->first());
                        }
                    }
                    // return $facultades;
                    //Recorremos la(s) facultad(es) y escuela(s) para ir añadiendo cada escuela a la facultad que pertenece y no se repitan las facultades
                    foreach ($facultades as $key => $facultad) {
                        $escuelas=[];
                        foreach ($alumnoEscuelasSGA as $key => $escuela) {
                            $facultadEscuela=Dependencia::select('dep_nombre')->Where('dep_id',$escuela->sdep_id)->first();
                            // echo $facultad['nombre'];
                            // echo strtoupper($facultadEscuela['dep_nombre']);
                            // echo '----------------------------------------------------------------';
                            if ($facultad['nombre']===strtoupper($facultadEscuela['dep_nombre'])) {
                                $escuelaSede=Escuela::where('idSGA_PREG',$escuela->dep_id)->first();
                                $escuelaSede->nro_matricula=$escuela->per_login;
                                if ($escuela->sed_nombre == 'Trujillo') $escuelaSede->sede='SEDE TRUJILLO';
                                else $escuelaSede->sede=$escuela->sed_nombre;
                                array_push($escuelas,$escuelaSede);
                            }
                            // echo $escuela;
                        }
                        $facultad->subdependencias=$escuelas;
                        array_push($facultadesTotales,$facultad);
                    }
                }
                // return $facultadesTotales;
                //Obtenemos datos de la persona que inicia sesión
                $personaSuv=PersonaSuv::select('persona.idpersona')
                ->join('sistema.roles_usuario','sistema.roles_usuario.idpersona','persona.idpersona')
                ->where('sistema.roles_usuario.rol_id',25)
                ->Where('persona.per_dni',$dni)->first();
                if ($personaSuv) {
                   //Obtenemos las escuela(s) a la(s) que pertenece dicha persona
                    $alumnoEscuelasSUV=Alumno::select('alumno.idalumno','patrimonio.sede.sed_descripcion','patrimonio.estructura.idestructura','patrimonio.estructura.estr_descripcion'
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
                    foreach ($alumnoEscuelasSUV as $key => $escuela) {
                        $facultad=Estructura::select('estr_descripcion')->Where('idestructura',$escuela->iddependencia)->first();
                        $existe= false;
                        foreach ($facultades as $value) {
                            if (strtoupper($value->nombre)==strtoupper($facultad->estr_descripcion)) {
                                $existe = true;
                                break;
                            } 
                        }
                        if(!$existe) {
                            // redirect(base_url()."bienvenidos");
                            array_push($facultades, DependenciaURAA::where('nombre',strtoupper($facultad->estr_descripcion))->first());
                        }
                        // array_push($facultades, DependenciaURAA::where('nombre',strtoupper($facultad->estr_descripcion))->first());
                    }
                    
                    //Recorremos la(s) facultad(es) y escuela(s) para ir añadiendo cada escuela a la facultad que pertenece y no se repitan las facultades
                    foreach ($facultades as $key => $facultad) {
                        $escuelas=[];
                        foreach ($alumnoEscuelasSUV as $key => $escuela) {
                            $facultadEscuela=Estructura::select('estr_descripcion')->Where('idestructura',$escuela->iddependencia)->first();
                            if ($facultad['nombre']===strtoupper($facultadEscuela['estr_descripcion'])) {
                                $escuelaSede=Escuela::where('idSUV_PREG',$escuela->idestructura)->first();
                                $escuelaSede->nro_matricula=$escuela->idalumno;
                                $escuelaSede->sede=$escuela->sed_descripcion;
                                array_push($escuelas, $escuelaSede);
                            }
                        }
                        $facultad->subdependencias=$escuelas;
                        
                        if ($facultadesTotales) {
                            foreach ($facultadesTotales as $key => $value) {
                                if ($value->idDependencia!=$facultad->idDependencia) {
                                    array_push($facultadesTotales,$facultad);
                                }else {
                                    foreach ($value->subdependencias as $key => $subdependencia) {
                                        array_push($escuelas, $subdependencia);
                                        
                                    }
                                    $value->subdependencias=$escuelas;
                                }
                            }
                        } else {
                            array_push($facultadesTotales,$facultad);
                        }
                        // return $facultadesTotales;
                    }
                }
                if (count($facultadesTotales)>0) {
                    return response()->json(['status' => '200', 'dependencias' => $facultadesTotales], 200); 
                }else {
                    return response()->json(['status' => '400', 'mesagge' => 'Alumno no encontrado.'], 400); 
                }
                
            }else if($idUnidad==2){ //postgrado
                // dónde?
                $facultadesTotales=array(
                    array(
                        'idDependencia'=>13,
                        'idUnidad'=>2,
                        'nombre'=>'MAESTRÍA',
                        'idDependencia2'=>null,
                        'estado'=>1,
                        'subdependencias'=>array(
                            array(
                                'idPrograma'=>29,
                                'idDependencia'=>13,
                                'idUnidad'=>2,
                                // 'idSGA_PREG'=>73,
                                // 'idSUV_PREG'=>21,
                                'nombre'=>'MAESTRIA EN CIENCIAS, MENCIÓN: NUTRICIÓN Y ALIMENTACIÓN ANIMAL',
                                // 'denominacion'=>'MAESTRIA EN CIENCIAS, MENCIÓN: NUTRICIÓN Y ALIMENTACIÓN ANIMAL',
                                // 'descripcion_grado'=>'MAESTRIA EN CIENCIAS, MENCIÓN: NUTRICIÓN Y ALIMENTACIÓN ANIMAL',
                                // 'descripcion_titulo'=>'MAESTRIA EN CIENCIAS, MENCIÓN: NUTRICIÓN Y ALIMENTACIÓN ANIMAL',
                                'estado'=>1,
                                'nro_matricula'=>'1023300217',
                                'sede'=>'SEDE TRUJILLO'
                            )
                        )
                    ),
                    array(
                        'idDependencia'=>14,
                        'idUnidad'=>2,
                        'nombre'=>'DOCTORADO',
                        'idDependencia2'=>null,
                        'estado'=>1,
                        'subdependencias'=>array(
                            array(
                                'idPrograma'=>29,
                                'idDependencia'=>14,
                                'idUnidad'=>2,
                                // 'idSGA_PREG'=>73,
                                // 'idSUV_PREG'=>21,
                                'nombre'=>'DOCTORADO EN ECONOMÍA Y DESARROLLO INDUSTRIAL',
                                // 'denominacion'=>'DOCTORADO EN ECONOMÍA Y DESARROLLO INDUSTRIAL',
                                // 'descripcion_grado'=>'DOCTORADO EN ECONOMÍA Y DESARROLLO INDUSTRIAL',
                                // 'descripcion_titulo'=>'DOCTORADO EN ECONOMÍA Y DESARROLLO INDUSTRIAL',
                                'estado'=>1,
                                'nro_matricula'=>'1023300217',
                                'sede'=>'SEDE TRUJILLO'
                            )
                        )
                    )
                );
                return response()->json(['status' => '200', 'dependencias' => $facultadesTotales], 200); 
            }else if($idUnidad==3){ //maestría
                // donde
                return Http::get('http://www.epgnew.unitru.edu.pe/epg_admin/api/matricula.php', [
                    'dni' => $dni
                  ]);
            }else{
                // Obtenemos las menciones a las que pertenece el alumno
                $alumnoMenciones=PersonaSE::select('alumno.codigo','mencion.idMencion','mencion.nombre','idSegunda_Especialidad')
                ->join('mencion','alumno.idMencion','mencion.idMencion')
                ->Where('alumno.nro_documento',$dni)
                ->get();
                // obtenemos la sede de la última matricula a la que pertenece el alumno
                $sede=PersonaSE::select('matricula.fecha_hora','sede.nombre')
                ->join('matricula','alumno.idAlumno','matricula.idAlumno')
                ->join('sede','matricula.idSede','sede.idSede')
                ->where('alumno.nro_documento',$dni)
                ->orderBy('matricula.fecha_hora','desc')
                ->limit(1)
                ->first()
                ;
                //Guardamos la(s) segunda especialidad(es) a la que pertenece dicho alumno
                $facultades=[];
                foreach ($alumnoMenciones as $key => $mencion) {
                    $flag=false;
                    // obtenemos la facultad a la que pertenece cada mencion
                    $facultad_mencion=Segunda_Especialidad::select('nombre')->Where('idSegunda_Especialidad',$mencion->idSegunda_Especialidad)->first();
                    //Recorremos el aray de facultades para que no se repitan al agregar la facultad de una mencion nueva
                    foreach ($facultades as $key => $facultad) {
                        if ($facultad->nombre==strtoupper($facultad_mencion->nombre)) {
                            $flag=true;
                            break;
                        }
                    }
                    if (!$flag) {
                        array_push($facultades, DependenciaURAA::where('nombre',strtoupper($facultad_mencion->nombre))->first());                    
                    }
                    // if (!in_array($facultad, $facultades)) {
                    //     // return "existe";
                    // }
                }
                //Recorremos la(s) segunda especialidad(es) y mencion(s) para ir añadiendo cada mencion a su respectiva segunda especialidad
                foreach ($facultades as $key => $facultad) {
                    $menciones=[];
                    foreach ($alumnoMenciones as $key => $mencion) {
                        $facultadMencion=Segunda_Especialidad::select('nombre')->Where('idSegunda_Especialidad',$mencion->idSegunda_Especialidad)->first();
                        if ($facultad['nombre']===strtoupper($facultadMencion['nombre'])) {
                            $mencionSede=Mencion::where('idSGA_SE',$mencion->idMencion)->first();
                            $mencionSede->nro_matricula=$mencion->codigo;
                            $mencionSede->sede=$sede->nombre;
                            array_push($menciones, $mencionSede);
                        }
                    }
                    $facultad->subdependencias=$menciones;
                }
                return response()->json(['status' => '200', 'dependencias' => $facultades], 200);
            } 
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
            //return redirect()->route('alumno.show', $resolucion->idResolucion) -> with('error', 'Error al registrar alumno');
        }
    }
}
