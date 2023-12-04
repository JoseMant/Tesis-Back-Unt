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
use App\Usuario_Programa;

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
            $personaSE=PersonaSE::select('alumno.codigo','alumno.nombre','alumno.paterno','alumno.materno','alumno.idTipo_documento',
            'alumno.nro_documento','alumno.correo_unitru','alumno.correo_personal','alumno.celular','alumno.sexo','alumno.direccion','alumno.nacimiento')
            ->Where('alumno.nro_documento',$request->input('dni'))->first();
            if($personaSE){
                $usuario=new User;
                $usuario->nombres=$personaSE->nombre;
                $usuario->apellido_paterno=$personaSE->paterno;
                $usuario->apellido_materno=$personaSE->materno;
                $usuario->tipo_documento=$personaSE->idTipo_documento;
                $usuario->nro_documento=$personaSE->nro_documento;
                if($personaSE->correo_unitru!=null) $usuario->correo=$personaSE->correo_unitru;
                else $usuario->correo=$personaSE->correo_personal;
                $usuario->direccion=$personaSE->direccion;
                $usuario->fecha_nacimiento=$personaSE->nacimiento;
                $usuario->celular=$personaSE->celular;
                $usuario->sexo=$personaSE->sexo;
                return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
            }else{
                // verificamos en la bd del suv
                $personaSuv=PersonaSuv::select('persona.per_nombres','persona.per_apepaterno','persona.per_apematerno','per_tipo_documento',
                'persona.per_dni','persona.per_carneextranjeria','persona.per_email_institucional','persona.per_email','persona.per_celular','persona.per_sexo',
                'persona.per_direccionlocal','persona.per_fechanacimiento','alumno.idalumno')
                ->join('alumno','persona.idpersona','alumno.idpersona')
                ->Where('persona.per_dni',$request->input('dni'))->first();
                if($personaSuv){
                    $usuario=new User;
                    $usuario->nombres=$personaSuv->per_nombres;
                    $usuario->apellido_paterno=$personaSuv->per_apepaterno;
                    $usuario->apellido_materno=$personaSuv->per_apematerno;
                    $usuario->tipo_documento=$personaSuv->per_tipo_documento;
                    $usuario->nro_documento=$personaSuv->per_dni;
                    if($personaSuv->per_email_institucional) $usuario->correo=$personaSuv->per_email_institucional;
                    else $usuario->correo=$personaSuv->per_email;
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
                        $usuario->nombres=$personaSga->per_nombres;
                        $apellidos=explode(" ", $personaSga->per_apellidos, 2);
                        $usuario->apellido_paterno= $apellidos[0];
                        $usuario->apellido_materno=$apellidos[1];
                        $usuario->tipo_documento=1;
                        if($personaSga->per_email_institucional!=null) $usuario->correo=$personaSga->per_email_institucional;
                        else $usuario->correo=$personaSga->per_mail;
                        $usuario->nro_documento=$personaSga->per_dni;
                        $usuario->direccion=$personaSga->per_direccion;
                        $usuario->fecha_nacimiento=$personaSga->per_fnaci;
                        $usuario->celular=$personaSga->per_celular;
                        $usuario->sexo=$personaSga->per_sexo;
                        return response()->json(['status' => '200', 'datos_alumno' => $usuario], 200);
                    }else{
                        // $authEPG= Http::post('http://epgnew.unitru.edu.pe:81/auth', [
                        //     'usuario' => "rtecnico",
                        //     "password" => "rt@2023*"
                        // ]);
                        // if ($authEPG && isset($authEPG['token'])){
                        //     $response = Http::withToken($authEPG['token'])->get('http://epgnew.unitru.edu.pe:81/alumnos', [
                        //         'dni' => $request->input('dni')
                        //         // 'dni' => "70271421"
                        //     ]);
                        //     if ($response['success'] && $response['alumno'][0]) {
                        //         $personaEPG = $response['alumno'][0];
                        //         $usuario=new User;
                        //         $usuario->nombres = strtoupper(trim($personaEPG['alu_nombres']));
                        //         $usuario->apellido_paterno = strtoupper(trim($personaEPG['alu_ape_paterno']));
                        //         $usuario->apellido_materno = strtoupper(trim($personaEPG['alu_ape_materno']));
                        //         // $usuario->tipo_documento = $personaEPG['idTipo_documento'];
                        //         $usuario->nro_documento = $request->input('dni');
                        //         if ($personaEPG['alu_correo_institucional']!=null) $usuario->correo = $personaEPG['alu_correo_institucional'];
                        //         else $usuario->correo = $personaEPG['alu_email'];
                        //         $usuario->direccion = strtoupper(trim($personaEPG['alu_domicilio']));
                        //         $usuario->fecha_nacimiento = date('Y-m-d', strtotime($personaEPG['alu_fecha_nacimiento']));
                        //         $usuario->celular = $personaEPG['alu_celular'];
                        //         if ($personaEPG['alu_sexo'] == "Femenino") $usuario->sexo = "F";
                        //         else if ($personaEPG['alu_sexo'] == "Masculino") $usuario->sexo = "M";
                        //         return response()->json(['status' => '200', 'datos_alumno' => $usuario, 'sistema' => "EPG"], 200);
                        //     } else {
                        //         return response()->json(['status' => '400', 'message' => 'Alumno no encontrado. Favor de enviar nombres completos, facultad, escuela, nro. matricula, 
                        //                 dni, dirección, celular, fecha de nacimiento y unidad (Pregrado, Posgrado o Segunda Especialidad) al correo uraa@unitru.edu.pe'], 400);
                        //     }
                        // } else {
                            return response()->json(['status' => '400', 'message' => 'Alumno no encontrado. Favor de enviar nombres completos, facultad, escuela, nro. matricula, 
                                dni, dirección, celular, fecha de nacimiento y unidad (Pregrado o segunda especialidad) al correo uraa@unitru.edu.pe'], 400);
                        // }
                    }
                }
            }    
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }



    public function DatosAlumno2($idUnidad)
    {
        try{
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $dni=$apy['nro_documento'];
            // $dni="70271421";
            // $dni="19021556";
            $idUsuario=$apy['idUsuario'];
            $idTipo_usuario=$apy['idTipo_usuario'];
            if ($idTipo_usuario==4||$idTipo_usuario==1) {
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
                        return response()->json(['status' => '400', 'message' => 'Alumno no encontrado.'], 400); 
                    }
                    
                }else if($idUnidad==2){ //postgrado
                    // $authEPG = Http::post('http://epgnew.unitru.edu.pe:81/auth', [
                    //     'usuario' => "rtecnico",
                    //     "password" => "rt@2023*"
                    // ]);
                    // if ($authEPG && isset($authEPG['token'])){
                    //     $response = Http::withToken($authEPG['token'])->get('http://epgnew.unitru.edu.pe:81/estudios', [
                    //         'dni' => $dni
                    //     ]);
                    //     if ($response['success'] && $response['estudios'][0]) {
                    //         // Obtenemos las programas a las que pertenece el alumno
                    //         $alumnoProgramas = $response['estudios'];
                    //         //Guardamos la(s) unidad(es) a la que pertenece dicho alumno
                    //         $facultades=[];
                    //         foreach ($alumnoProgramas as $key => $programa) {
                    //             $flag=false;
                    //             $programa['idPrograma'] = 1;
                    //             // obtenemos la idDependencia a la que pertenece cada programa
                    //             $idDependencia=ProgramaURAA::where('idSGA_EPG',$programa['idPrograma'])->pluck('idDependencia')->first();
                    //             //Recorremos el aray de facultades para que no se repitan al agregar la facultad de un programa nuevo
                    //             foreach ($facultades as $key => $facultad) {
                    //                 if ($facultad->idDependencia == $idDependencia) {
                    //                     $flag=true;
                    //                     break;
                    //                 }
                    //             }
                    //             if (!$flag) {
                    //                 array_push($facultades, DependenciaURAA::where('idDependencia',$idDependencia)->first());                    
                    //             }
                    //         }
                            
                    //         //Recorremos la(s) unidad(es) y programa(s) para ir añadiendo cada programa a su respectiva unidad
                    //         foreach ($facultades as $key => $facultad) {
                    //             $programas=[];
                    //             foreach ($alumnoProgramas as $key => $programa) {
                    //                 $programa['idPrograma'] = 1;
                    //                 $programaSede=ProgramaURAA::where('idSGA_EPG',$programa['idPrograma'])->first();
                    //                 if ($facultad->idDependencia==$programaSede->idDependencia) {
                    //                     $programaSede->nro_matricula=$programa['alu_codigo_matricula'];
                    //                     if (!isset($programa['alu_sede'])) $programaSede->sede="TRUJILLO";
                    //                     else $programaSede->sede=$programa['alu_sede'];
                    //                     array_push($programas, $programaSede);
                    //                 }
                    //             }
                    //             $facultad->subdependencias=$programas;
                    //         }
                    //         return response()->json(['status' => '200', 'dependencias' => $facultades], 200);
                    //     } else {    
                    //         return response()->json(['status' => '400', 'message' => 'Alumno no encontrado para la unidad seleccionada'], 400); 
                    //     }
                    // } else {
                        return response()->json(['status' => '400', 'message' => 'La base de datos de la EPG no está disponible.'], 400);
                    // }
                }else if($idUnidad==3){ //maestría
                    // return Http::get('http://www.epgnew.unitru.edu.pe/epg_admin/api/matricula.php', [
                    //     'dni' => $dni
                    //   ]);
                    return response()->json(['status' => '400', 'message' => 'Alumno no encontrado.'], 400); 
                }else{
                    // Obtenemos las menciones a las que pertenece el alumno
                    $alumnoProgramas=PersonaSE::select('alumno.codigo','mencion.idMencion','mencion.nombre','mencion.idSegunda_Especialidad', 'sede.nombre as sede')
                    ->join('mencion','alumno.idMencion','mencion.idMencion')
                    ->join('resolucion','resolucion.idResolucion','alumno.idResolucion')
                    ->join('sede','resolucion.idSede','sede.idSede')
                    ->Where('alumno.nro_documento',$dni)
                    ->get();
                    //Guardamos la(s) segunda(s) especialidad(es) a la que pertenece dicho alumno
                    $facultades=[];
                    foreach ($alumnoProgramas as $key => $programa) {
                        $flag=false;
                        // obtenemos la idDependencia a la que pertenece cada programa
                        $idDependencia=ProgramaURAA::where('idSGA_SE',$programa->idMencion)->pluck('idDependencia')->first();
                        //Recorremos el aray de facultades para que no se repitan al agregar la facultad de una mencion nueva
                        foreach ($facultades as $key => $facultad) {
                            if ($facultad->idDependencia == $idDependencia) {
                                $flag=true;
                                break;
                            }
                        }
                        if (!$flag) {
                            array_push($facultades, DependenciaURAA::where('idDependencia',$idDependencia)->first());                    
                        }
                    }

                    //Recorremos la(s) segunda especialidad(es) y mencion(s) para ir añadiendo cada mencion a su respectiva segunda especialidad
                    foreach ($facultades as $key => $facultad) {
                        $programas=[];
                        foreach ($alumnoProgramas as $key => $programa) {
                            $programaSede=ProgramaURAA::where('idSGA_SE',$programa->idMencion)->first();
                            if ($facultad->idDependencia==$programaSede->idDependencia) {
                                $programaSede->nro_matricula=$programa->codigo;
                                if (!isset($programa->sede)) $programaSede->sede="TRUJILLO";
                                else $programaSede->sede=$programa->sede;
                                array_push($programas, $programaSede);
                            }
                        }
                        $facultad->subdependencias=$programas;
                    }
                    return response()->json(['status' => '200', 'dependencias' => $facultades], 200);
                } 
            }else if($idTipo_usuario==5){
                
                $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)
                ->join('programa', 'programa.idPrograma', 'usuario_programa.idPrograma')
                ->get();
                if (count($usuario_programas) > 0) {
                    $facultades=[];
                    foreach ($usuario_programas as $key => $escuela) {
                        $dependencia = DependenciaURAA::find($escuela->idDependencia);
                        $existe= false;
                        $facultadIndex = null;

                        foreach ($facultades as $value) {
                            if ($value->idDependencia==$dependencia->idDependencia) {
                                $existe = true;
                                break;
                            } 
                        }
                        if(!$existe) {
                            array_push($facultades,$dependencia);
                        }
                      
                    }

                    foreach ($facultades as $key => $facultad) {
                        $programas=[];
                        foreach ($usuario_programas as $key => $programa) {

                            $facultadPrograma=ProgramaURAA::Where('idPrograma',$programa->idPrograma)->first();
                            if ($facultad->idDependencia===$facultadPrograma->idDependencia) {
                                array_push($programas,$facultadPrograma);
                            }
                        }
                        $facultad->subdependencias=$programas;
                    }

                    if (count($facultades)>0) {
                        return response()->json(['status' => '200', 'dependencias' => $facultades], 200); 
                    }else {
                        return response()->json(['status' => '400', 'message' => 'Usuario no encontrado.'], 400); 
                    }
                }
            }else if ($idTipo_usuario==17) {
                
                $idDependencia=$apy['idDependencia'];
                
                if ($idDependencia) {
                    $facultades=[];
                    $dependencia = DependenciaURAA::findOrFail($idDependencia);
                    $programas=ProgramaURAA::where('idDependencia',$idDependencia)->get();
                
                    $dependencia->subdependencias=$programas;
                    array_push($facultades,$dependencia);

                    if (count($facultades)>0) {
                        return response()->json(['status' => '200', 'dependencias' => $facultades], 200); 
                    }else {
                        return response()->json(['status' => '400', 'message' => 'Usuario no encontrado.'], 400); 
                    }
                }
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
