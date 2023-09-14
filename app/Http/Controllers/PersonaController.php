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
use App\ProgramaURAA;
use Illuminate\Support\Facades\Http;
use App\DependenciaSGA;

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
                    $usuario->nro_matricula=$personaSE->nro_tramite;
                    $usuario->nombres=$personaSE->nombre;
                    $usuario->apellido_paterno=$personaSE->paterno;
                    $usuario->apellido_materno=$personaSE->materno;
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
                        $usuario->apellido_paterno=$personaSuv->per_apepaterno;
                        $usuario->apellido_materno=$personaSuv->per_apematerno;
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
                        $personaSga=PersonaSga::select('per_nombres','per_apellidos','per_dni','per_mail','per_email_institucional','per_celular','per_sexo'
                        ,'per_login','per_direccion','per_fnaci')
                        ->Where('per_dni',$request->input('dni'))->first();
                        if($personaSga){
                            $usuario=new User;
                            $usuario->nro_matricula=$personaSga->per_login;
                            $usuario->nombres=$personaSga->per_nombres;
                            $apellidos=explode(" ", $personaSga->per_apellidos, 2);
                            $usuario->apellido_paterno= $apellidos[0];
                            $usuario->apellido_materno=$apellidos[1];
                            $usuario->tipo_documento=1;
                            if($personaSga->per_email_institucional!=null)
                                $usuario->correo=$personaSga->per_email_institucional;
                            else
                                $usuario->correo=$personaSga->per_mail;
                            $usuario->nro_documento=$personaSga->per_dni;
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
        try{
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $dni=$apy['nro_documento'];
            if($idUnidad==1){ //pregrado
                $facultadesTotales=[];
                //Obtenemos escuela(s) de la persona que inicia sesión
                $alumnoEscuelasSGA=PersonaSga::select('per_nombres','per_apellidos','per_dni','per_mail','per_celular','per_sexo'
                ,'per_login','sga_sede.sed_nombre','dependencia.dep_id','dependencia.dep_nombre','dependencia.sdep_id')
                ->join('perfil','persona.per_id','perfil.per_id')
                ->join('sga_sede','sga_sede.sed_id','perfil.sed_id')
                ->join('dependencia','dependencia.dep_id','perfil.dep_id')
                ->where('perfil.pfl_cond','AL')
                ->where('per_dni',$dni)->get();
                if (count($alumnoEscuelasSGA)>0) {
                    //Guardamos la(s) facultad(es) a la que pertenece dicho alumno
                    $facultades=[];
                    foreach ($alumnoEscuelasSGA as $key => $escuela) {
                        $facultad=DependenciaSGA::select('dep_nombre')->Where('dep_id',$escuela->sdep_id)->first();
                        $existe= false;
                        foreach ($facultades as $value) {
                            if (strtoupper($value->nombre)==strtoupper($facultad->dep_nombre)) {
                                $existe = true;
                                break;
                            } 
                        }
                        if(!$existe) {
                            array_push($facultades, DependenciaURAA::where('nombre',strtoupper($facultad->dep_nombre))->first());
                        }
                    }
                    //Recorremos la(s) facultad(es) y escuela(s) para ir añadiendo cada escuela a la facultad que pertenece y no se repitan las facultades
                    foreach ($facultades as $key => $facultad) {
                        $escuelas=[];
                        foreach ($alumnoEscuelasSGA as $key => $escuela) {
                            $facultadEscuela=DependenciaSGA::select('dep_nombre')->Where('dep_id',$escuela->sdep_id)->first();
                            if ($facultad['nombre']===strtoupper($facultadEscuela['dep_nombre'])) {
                                if ($escuela->dep_id == 221) {
                                    $escuelasSede=ProgramaURAA::where('idSGA_PREG',$escuela->dep_id)->get();
                                    foreach ($escuelasSede as $key => $escuelaSede) {
                                        $escuelaSede->nro_matricula=$escuela->per_login;
                                        $escuelaSede->sede = $this->GetSedeClean($escuela->sed_nombre);
                                        array_push($escuelas,$escuelaSede);
                                    }
                                } else {
                                    $escuelaSede=ProgramaURAA::where('idSGA_PREG',$escuela->dep_id)->first();
                                    $escuelaSede->nro_matricula=$escuela->per_login;
                                    $escuelaSede->sede = $this->GetSedeClean($escuela->sed_nombre);
                                    array_push($escuelas,$escuelaSede);
                                }
                            }
                        }
                        $facultad->subdependencias=$escuelas;
                        array_push($facultadesTotales,$facultad);
                    }
                }
                //Obtenemos datos de la persona que inicia sesión
                $idPersonaSuv=PersonaSuv::select('persona.idpersona')
                ->join('sistema.roles_usuario','sistema.roles_usuario.idpersona','persona.idpersona')
                ->where('sistema.roles_usuario.rol_id',25)
                ->Where('persona.per_dni',$dni)
                ->Where('persona.per_estado',true)
                ->pluck('persona.idpersona')
                ->first();
                if ($idPersonaSuv) {
                   //Obtenemos las escuela(s) a la(s) que pertenece dicha persona
                    $alumnoEscuelasSUV=Alumno::select('alumno.idalumno','patrimonio.sede.sed_descripcion','patrimonio.estructura.idestructura','patrimonio.estructura.estr_descripcion'
                    ,'patrimonio.estructura.iddependencia', 'curricula.curr_mencion')
                    ->join('matriculas.curricula','alumno.alu_curricula','curricula.idcurricula')
                    ->join('patrimonio.area','alumno.idarea','patrimonio.area.idarea')
                    ->join('patrimonio.estructura','patrimonio.area.idestructura','patrimonio.estructura.idestructura')
                    ->join('patrimonio.sede','alumno.idsede','patrimonio.sede.idsede')
                    ->Where('alumno.idpersona',$idPersonaSuv)
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
                            array_push($facultades, DependenciaURAA::where('nombre',strtoupper($facultad->estr_descripcion))->first());
                        }
                    }
                    //Recorremos la(s) facultad(es) y escuela(s) para ir añadiendo cada escuela a la facultad que pertenece y no se repitan las facultades
                    foreach ($facultades as $key => $facultad) {
                        $escuelas=[];
                        foreach ($alumnoEscuelasSUV as $key => $escuela) {
                            $facultadEscuela=Estructura::select('estr_descripcion')->Where('idestructura',$escuela->iddependencia)->first();
                            if ($facultad['nombre']===strtoupper($facultadEscuela['estr_descripcion'])) {
                                if ($escuela->curr_mencion) {
                                    if ($escuela->curr_mencion == 1) {
                                        $escuelasSede=ProgramaURAA::where('idMencionSUV_PREG',$escuela->curr_mencion)->get();
                                        foreach ($escuelasSede as $key => $escuelaSede) {
                                            $escuelaSede->nro_matricula=$escuela->idalumno;
                                            $escuelaSede->sede = $this->GetSedeClean($escuela->sed_descripcion);
                                            array_push($escuelas, $escuelaSede);
                                        }
                                    } else {
                                        $escuelaSede=ProgramaURAA::where('idMencionSUV_PREG',$escuela->curr_mencion)->first();
                                        $escuelaSede->nro_matricula=$escuela->idalumno;
                                        $escuelaSede->sede = $this->GetSedeClean($escuela->sed_descripcion);
                                        array_push($escuelas, $escuelaSede);
                                    }
                                }else {
                                    $escuelaSede=ProgramaURAA::where('idSUV_PREG',$escuela->idestructura)->first();
                                    $escuelaSede->nro_matricula=$escuela->idalumno;
                                    $escuelaSede->sede = $this->GetSedeClean($escuela->sed_descripcion);
                                    array_push($escuelas, $escuelaSede);
                                }
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
                    }
                }
                if (count($facultadesTotales)>0) {
                    return response()->json(['status' => '200', 'dependencias' => $facultadesTotales], 200); 
                }else {
                    return response()->json(['status' => '400', 'mesagge' => 'Alumno no encontrado.'], 400); 
                }
                
            }else if($idUnidad==2){ //postgrado
                return response()->json(['status' => '400', 'mesagge' => 'Alumno no encontrado.'], 400); 
            }else if($idUnidad==3){ //maestría
                // return Http::get('http://www.epgnew.unitru.edu.pe/epg_admin/api/matricula.php', [
                //     'dni' => $dni
                //   ]);
                return response()->json(['status' => '400', 'mesagge' => 'Alumno no encontrado.'], 400); 
            }else{
                // Obtenemos las menciones a las que pertenece el alumno
                $alumnoMenciones=PersonaSE::select('alumno.codigo','mencion.idMencion','mencion.nombre','mencion.idSegunda_Especialidad', 'sede.nombre as sede')
                ->join('mencion','alumno.idMencion','mencion.idMencion')
                ->join('resolucion','resolucion.idResolucion','alumno.idResolucion')
                ->join('sede','resolucion.idSede','sede.idSede')
                ->Where('alumno.nro_documento',$dni)
                ->get();
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
                }
                //Recorremos la(s) segunda especialidad(es) y mencion(s) para ir añadiendo cada mencion a su respectiva segunda especialidad
                foreach ($facultades as $key => $facultad) {
                    $menciones=[];
                    foreach ($alumnoMenciones as $key => $mencion) {
                        $facultadMencion=Segunda_Especialidad::select('nombre')->Where('idSegunda_Especialidad',$mencion->idSegunda_Especialidad)->first();
                        if ($facultad['nombre']===strtoupper($facultadMencion['nombre'])) {
                            $mencionSede=ProgramaURAA::where('idSGA_SE',$mencion->idMencion)->first();
                            $mencionSede->nro_matricula=$mencion->codigo;
                            if (!$mencion->sede) $mencionSede->sede="TRUJILLO";
                            else $mencionSede->sede=$mencion->sede;
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
        }
    }

    public function GetSedeClean($sede)
    {
        if ($sede == 'Trujillo' || $sede == 'SEDE TRUJILLO') 
            return 'TRUJILLO';
        elseif ($sede == 'Valle Jequetepeque' || $sede == 'SEDE VALLE JEQUETEPEQUE')
            return 'VALLE JEQUETEPEQUE';
        elseif ($sede == 'Huamachuco' || $sede == 'SEDE HUAMACHUCO') 
            return 'HUAMACHUCO';
        elseif ($sede == 'Stgo. de Chuco' || $sede == 'SEDE SANTIAGO DE CHUCO') 
            return 'SANTIAGO DE CHUCO';
        else
            return $sede;
    }
}
