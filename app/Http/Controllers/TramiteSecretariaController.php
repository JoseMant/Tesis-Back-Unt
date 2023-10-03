<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Mail\Mailable;
use App\Tramite;
use App\Tramite_Secretaria;
use App\Tipo_Tramite;
use App\Tipo_Tramite_Unidad;
use App\Historial_Estado;
use App\Tramite_Requisito;
use App\Voucher;
use App\User;
use App\Tramite_Detalle;
use App\Tramite_Secretaria_Detalle;
use App\Estado_Tramite;
use App\Jobs\RegistroTramiteJob;
use App\Jobs\DocenteTramiteJob;
use App\Jobs\ObservacionDocenteTramiteJob;
use App\Jobs\AnularTramiteJob;
use App\Jobs\ActualizacionTramiteJob;
use App\Jobs\ObservacionTramiteJob;
use App\Jobs\FinalizacionCarnetJob;
use App\Jobs\NotificacionCertificadoJob;
use App\Jobs\NotificacionCarpetaJob;
use App\Jobs\RegistroTramiteDocenteJob;
use App\Jobs\RegistroDatosDocenteJob;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use App\Imports\TramitesImport;
use App\Exports\TramitesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\PersonaSE;
use App\DocenteURA;

use App\DependenciaSGA;
use App\Mencion;
use App\Escuela;
use App\Motivo_Certificado;
use App\PersonaSuv;
use App\PersonaSga;
use App\Perfil;
use App\UsuarioSUNT;
use App\PersonaSUNT;
use App\PermisosDocente;
use App\Cronograma;
use App\Resolucion;

class TramiteSecretariaController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt');
    }

    

    public function prueba(Request $request){
            try {
            return $request->all();
            } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            $usuario = User::findOrFail($idUsuario);
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_tramite_unidad',$request->idTipo_tramite_unidad)->first();
            
            $tramite=new Tramite;
            //AÑADIMOS EL NÚMERO DE TRÁMITE
            $inicio=date('Y-m-d')." 00:00:00";
            $fin=date('Y-m-d')." 23:59:59";
            $last_tramite=Tramite::whereBetween('created_at', [$inicio , $fin])->where('idTipo_tramite_unidad','!=',37)
            ->orderBy("created_at","DESC")->first();
            
            if ($last_tramite) {
                $correlativo=(int)(substr($last_tramite->nro_tramite,0,4));
                $correlativo++;
                if ($correlativo<10) $tramite->nro_tramite = "000".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                else if ($correlativo<100) $tramite->nro_tramite = "00".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                else if ($correlativo<1000) $tramite->nro_tramite = "0".$correlativo.date('d').date('m').substr(date('Y'),2,3);
                else $tramite->nro_tramite = $correlativo.date('d').date('m').substr(date('Y'),2,3);
            }else{
                $tramite -> nro_tramite="0001".date('d').date('m').substr(date('Y'),2,3);
            }
            
            
            $docente = new DocenteURA();
            $docente->save();

            // REGISTRAMOS EL DETALLE DEL TRÁMITE REGISTRADO
            $tramite_detalle = new Tramite_Detalle();
            $tramite_detalle -> idDocente=$docente->idDocente;
            $tramite_detalle->save();
            $tipo_tramite = Tipo_Tramite::select('tipo_tramite.idTipo_tramite','tipo_tramite.descripcion','tipo_tramite.filename')
            ->join('tipo_tramite_unidad', 'tipo_tramite_unidad.idTipo_tramite', 'tipo_tramite.idTipo_tramite')
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad', $request->idTipo_tramite_unidad)->first();
            // REGISTRAMOS EL TRÁMITE
            $tramite -> idTramite_detalle=$tramite_detalle->idTramite_detalle;
            $tramite -> idTipo_tramite_unidad=trim($request->idTipo_tramite_unidad);
            $tramite -> idUsuario=$idUsuario;
            $tramite -> idUnidad=trim($request->idUnidad);
            $tramite -> idDependencia=trim($request->idDependencia);
            $tramite -> idPrograma=trim($request->idPrograma);
            $tramite -> comentario=trim($request->comentario);
            $tramite -> idUsuario_asignado=null;
            $tramite -> idEstado_tramite=51;

            // Creando un uudi para realizar el llamado a los trámites por ruta

                // Verificando que no haya un uuid ya guardado en bd
                $tramiteUUID=true;
                while ($tramiteUUID) {
                    $uuid=Str::orderedUuid();
                    $tramiteUUID=Tramite::where('uuid',$uuid)->first();
                }
                $tramite -> uuid=$uuid;    
            $tramite -> save();

            // REGISTRAMOS LOS REQUISITOS DEL TRÁMITE REGISTRADO
            if($request->hasFile("files")){
                foreach ($request->file("files") as $key => $file) {
                    $requisito=json_decode($request->requisitos[$key],true);
                    $tramite_requisito=new Tramite_Requisito;
                    $tramite_requisito->idTramite=$tramite->idTramite;
                    $tramite_requisito->idRequisito=$requisito["idRequisito"];
                    $nombre = $tramite->nro_tramite.".".$file->guessExtension();
                    if ($tipo_tramite_unidad->idTipo_tramite==5) {
                        $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"]."/".$nombre;
                    }else {
                        $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$requisito["nombre"]."/".$nombre;
                    }
                    if ($file->getClientOriginalName()!=="vacio.kj") {
                        if($file->guessExtension()==$requisito["extension"]){
                            if ($tipo_tramite->idTipo_tramite==5) {
                                $file->storeAs("/public"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"], $nombre);
                            }else {
                                $file->storeAs("/public"."/".$tipo_tramite->filename."/".$requisito["nombre"], $nombre);
                            }
                            $tramite_requisito->archivo = $nombreBD;
                        }else {
                            DB::rollback();
                            return response()->json(['status' => '400', 'message' => "Subir ".$requisito["nombre"]." en ".$requisito["extension"]], 400);
                        }
                    }
                    $tramite_requisito -> save();
                }
            }

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estado=$this->setHistorialEstado($tramite->idTramite, null, 1, $idUsuario);
            $historial_estado->save();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estado=$this->setHistorialEstado($tramite->idTramite, 1, 51, $idUsuario);
            $historial_estado->save();

            dispatch(new RegistroTramiteDocenteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
            DB::commit();
            return response()->json(['status' => '200', 'usuario' => 'Trámite registrado correctamente'], 200);
        
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function setHistorialEstado($idTramite, $idEstado_actual, $idEstado_nuevo, $idUsuario)
    {
        $historial_estados = new Historial_Estado;
        $historial_estados->idTramite = $idTramite;
        $historial_estados->idEstado_actual = $idEstado_actual;
        $historial_estados->idEstado_nuevo = $idEstado_nuevo;
        $historial_estados->idUsuario = $idUsuario;
        $historial_estados->fecha = date('Y-m-d h:i:s');
        return $historial_estados;
    }

    public function getDocenteByCodigo(Request $request){

        $docente=PersonaSga::select('persona.per_apellidos as apellidos','persona.per_nombres as nombres','persona.per_celular as celular','persona.per_telefono as telefono'
        ,'persona.per_login as codigo','persona.per_login as per_login',
        'persona.per_email_institucional as correounitru','persona.per_mail as correo','persona.per_direccion as direccion','persona.per_dni as dni','persona.per_sexo as sexo'
        ,'persona.per_fnaci as fecha_nacimiento','persona.pon_id as idProfesion','perfil.cia_id as idCategoria','perfil.pfl_cond as idCondicion',
        'perfil.uni_id as idDependencia','perfil.ded_id as idDedicacion','perfil.dep_id as idDepartamento','perfil.pfl_boss as jefe','persona.per_cod_pais as idPais','perfil.sed_id as idSede')
        ->join('perfil','perfil.per_id','persona.per_id')
        ->where('persona.per_login',$request->codigo)->first();
        
        $tramiteDocente=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite.nro_tramite',
            'tramite_detalle.idDocente',DB::raw("CONCAT(usuario.apellidos,' ',usuario.nombres) as solicitante"),'tramite.idEstado_tramite',
            'tramite.created_at as fecha','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->where('tramite.nro_tramite',$request->nro_tramite)->first();

        $tramiteDocente->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
        'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
        ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
        ->where('idTramite',$tramiteDocente->idTramite)
        ->get();        

        if ($docente) {
            $docente->idTramite=$tramiteDocente->idTramite;
            $docente->nro_tramite=$request->nro_tramite;
            $docente->solicitante=$tramiteDocente->solicitante;
            $docente->tramite=$tramiteDocente->tramite;
            $docente->fecha=$tramiteDocente->fecha;
            $docente->idTipo_tramite_unidad=$tramiteDocente->idTipo_tramite_unidad;
            $docente->requisitos=$tramiteDocente->requisitos;
            return $docente;
        }else {
            return $tramiteDocente;
        }

    }    

    public function GetTramitesDocente(Request $request){

        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $usuario = User::findOrFail($idUsuario);
        $idTipo_usuario=$apy['idTipo_usuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite.nro_tramite',
        'tramite_detalle.idDocente',DB::raw("CONCAT(usuario.apellidos,' ',usuario.nombres) as solicitante"),'tramite.idEstado_tramite',
        'tramite.created_at as fecha','tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.idTipo_tramite_unidad','docentes.*')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('docentes','docentes.idDocente','tramite_detalle.idDocente')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->where(function($query) 
        {            
            $query->where('tramite.idTipo_tramite_unidad',38)
            ->orWhere('tramite.idTipo_tramite_unidad',39)
            ->orWhere('tramite.idTipo_tramite_unidad',40)
            ->orWhere('tramite.idTipo_tramite_unidad',41);
        })
        ->where('tramite.idEstado_tramite',51)
        ->where(function($query) use ($idTipo_usuario,$idUsuario)
        {   
            if($idTipo_usuario==21){
                $query->where('tramite.idUsuario',$idUsuario);
            }
        
        })
        ->where(function($query) use ($request)
        {   
    
            $query->where('tramite.nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.nombres','LIKE', '%'.$request->query('search').'%');
        
        })
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))->get();
        
        $total=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite.nro_tramite',
        'tramite_detalle.idDocente',DB::raw("CONCAT(usuario.apellidos,' ',usuario.nombres) as solicitante"),'tramite.idEstado_tramite',
        'tramite.created_at as fecha','tipo_tramite_unidad.descripcion as tramite','docentes.*')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('docentes','docentes.idDocente','tramite_detalle.idDocente')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->where(function($query) 
        {            
            $query->where('tramite.idTipo_tramite_unidad',38)
            ->orWhere('tramite.idTipo_tramite_unidad',39)
            ->orWhere('tramite.idTipo_tramite_unidad',40)
            ->orWhere('tramite.idTipo_tramite_unidad',41);
        })
        ->where('tramite.idEstado_tramite',51)
        ->where(function($query) use ($idTipo_usuario,$usuario)
        {   
            if($idTipo_usuario==21){
                $query->where('tramite.idUsuario',$idUsuario);
            }
        
        })
        ->where(function($query) use ($request)
        {
            $query->where('tramite.nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.nombres','LIKE', '%'.$request->query('search').'%');
        })
        ->count();
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }
        
        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200', 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);

    }
    public function registroDocente(Request $request){
        DB::beginTransaction();
        try{
            
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            $usuario = User::findOrFail($idUsuario);

            $tramite=Tramite::find($request->idTramite);
            $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);

            $tipo_tramite = Tipo_Tramite::select('tipo_tramite.idTipo_tramite','tipo_tramite.descripcion','tipo_tramite.filename')
            ->join('tipo_tramite_unidad', 'tipo_tramite_unidad.idTipo_tramite', 'tipo_tramite.idTipo_tramite')
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad', $tramite->idTipo_tramite_unidad)->first();

            $tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)->first();

            $persona=PersonaSga::select('persona.per_nombres','persona.per_apellidos','perfil.dep_id','persona.per_login')
            ->join('perfil','perfil.per_id','persona.per_id')
            ->where('persona.per_nombres',$request->nombres)
            ->where('persona.per_apellidos',$request->apellidos)
            ->where('perfil.dep_id',$request->idDepartamento)->first();
            
            if($persona){
                if ($tipo_tramite_unidad->idTipo_tramite_unidad==40 || $tipo_tramite_unidad->idtipo_tramite_unidad==41) {
                    $docente=DocenteURA::find($tramite_detalle->idDocente);
                    $docente->apellidos=$request->apellidos;
                    $docente->nombres=$request->nombres;
                    $docente->idProfesion=$request->idProfesion;
                    $docente->sexo=$request->sexo;
                    $docente->fecha_nacimiento=$request->fecha_nacimiento;
                    $docente->direccion=$request->direccion;
                    $docente->idPais=$request->idPais;
                    $docente->dni=$request->dni;
                    $docente->per_login=$request->per_login;
                    $docente->telefono=$request->telefono;
                    $docente->celular=$request->celular;
                    $docente->correo=$request->correo;
                    $docente->correounitru=$request->correounitru;
                    $docente->correounitru=$request->correounitru;
                    $docente->jefe=$request->jefe;
                    $docente->idDependencia=$request->idDependencia;
                    $docente->idDepartamento=$request->idDepartamento;
                    $docente->idSede=$request->idSede;
                    $docente->idCondicion=$request->idCondicion;
                    $docente->idCategoria=$request->idCategoria;
                    $docente->idDedicacion=$request->idDedicacion;
                    $docente->save();
                }else{
                    DB::rollback();
                     return response()->json(['status' => '400', 'message' => 'Docente ya esta registrado'], 400);
                }
            }else{
                $docente=DocenteURA::find($tramite_detalle->idDocente);
                $docente->apellidos=$request->apellidos;
                $docente->nombres=$request->nombres;
                $docente->idProfesion=$request->idProfesion;
                $docente->sexo=$request->sexo;
                $docente->fecha_nacimiento=$request->fecha_nacimiento;
                $docente->direccion=$request->direccion;
                $docente->idPais=$request->idPais;
                $docente->dni=$request->dni;
                $docente->telefono=$request->telefono;
                $docente->celular=$request->celular;
                $docente->correo=$request->correo;
                $docente->correounitru=$request->correounitru;
                $docente->correounitru=$request->correounitru;
                $docente->jefe=$request->jefe;
                $docente->idDependencia=$request->idDependencia;
                $docente->idDepartamento=$request->idDepartamento;
                $docente->idSede=$request->idSede;
                $docente->idCondicion=$request->idCondicion;
                $docente->idCategoria=$request->idCategoria;
                $docente->idDedicacion=$request->idDedicacion;
                $docente->save();
            }
                

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estado=$this->setHistorialEstado($tramite->idTramite, 51, 52, $idUsuario);
            $historial_estado->save();
            $historial_estado=$this->setHistorialEstado($tramite->idTramite, 52,7, $idUsuario);
            $historial_estado->save();
            $tramite -> idEstado_tramite=7;
            $tramite->save();
            
            
            $tramite_requisito = Tramite_Requisito::where('tramite_requisito.idTramite',$tramite->idTramite)->first();

            if($request->hasFile("files")){
                foreach ($request->file("files") as $key => $file) {
                    $requisito=json_decode($request->requisitos[$key],true);
                    $nombre = $tramite->nro_tramite.".".$file->guessExtension();

                        $nombreBD = "/storage"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"]."/".$nombre;

                    if ($file->getClientOriginalName()!=="vacio.kj") {
                        if($file->guessExtension()==$requisito["extension"]){
                                $file->storeAs("/public"."/".$tipo_tramite->filename."/".$tipo_tramite_unidad->descripcion."/".$requisito["nombre"], $nombre);
                            $tramite_requisito->archivo = $nombreBD;
                        }else {
                            DB::rollback();
                            return response()->json(['status' => '400', 'message' => "Subir ".$requisito["nombre"]." en ".$requisito["extension"]], 400);
                        }
                    }
                }
            }

            $tramite_requisito->des_estado_requisito="PENDIENTE";
            $tramite_requisito->update();
            
            $correojefe="rrodriguezg@unitru.edu.pe";
            dispatch(new RegistroDatosDocenteJob($correojefe,$usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function GetDocenteValidar(Request $request){

        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite_requisito.archivo','tramite.nro_tramite','tramite_detalle.idDocente',
        DB::raw("CONCAT(usuario.apellidos,' ',usuario.nombres) as solicitante"),'tramite.created_at as fecha','tipo_tramite_unidad.descripcion as tramite',
        'docentes.*')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('docentes','docentes.idDocente','tramite_detalle.idDocente')
        ->join('tramite_requisito','tramite_requisito.idTramite','tramite.idTramite')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->where(function($query) 
        {            
            $query->where('tramite.idTipo_tramite_unidad',38)
            ->orWhere('tramite.idTipo_tramite_unidad',39)
            ->orWhere('tramite.idTipo_tramite_unidad',40)
            ->orWhere('tramite.idTipo_tramite_unidad',41);
        })
        ->where('tramite.idEstado_tramite',7)
        ->where(function($query) use ($request)
        {
            $query->where('tramite.nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.nombres','LIKE', '%'.$request->query('search').'%');
        })
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))->get();

        $total=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite_requisito.archivo','tramite.nro_tramite','tramite_detalle.idDocente',
        DB::raw("CONCAT(usuario.apellidos,' ',usuario.nombres) as solicitante"),'tramite.created_at as fecha','tipo_tramite_unidad.descripcion as tramite',
        'docentes.*')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('docentes','docentes.idDocente','tramite_detalle.idDocente')
        ->join('tramite_requisito','tramite_requisito.idTramite','tramite.idTramite')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->where(function($query) 
        {            
            $query->where('tramite.idTipo_tramite_unidad',38)
            ->orWhere('tramite.idTipo_tramite_unidad',39)
            ->orWhere('tramite.idTipo_tramite_unidad',40)
            ->orWhere('tramite.idTipo_tramite_unidad',41);
        })
        ->where('tramite.idEstado_tramite',7)
        ->where(function($query) use ($request)
        {
            $query->where('tramite.nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.nombres','LIKE', '%'.$request->query('search').'%');

        })
        ->count();

        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }

        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200', 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);

    }

    public function validarDocente(Request $request){
        DB::beginTransaction();
        try{
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            $usuario = User::findOrFail($idUsuario);

            $tramite=Tramite::findOrFail($request->idTramite);
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::findOrFail($tramite->idTipo_tramite_unidad);
            $tipo_tramite = Tipo_Tramite::findOrFail($tipo_tramite_unidad->idTipo_tramite);

            $login=Perfil::select('persona.per_login')
            ->join('persona','persona.per_id','perfil.per_id')
            ->where('cgo_id','!=',1)
            ->orderby('persona.per_id', 'DESC')->first();
            $per_login=intval($login->per_login)+1;
            $departamento=DependenciaSGA::where('dependencia.dep_id',$request->idDepartamento)->first();

            if ($tipo_tramite_unidad->idTipo_tramite_unidad==40 || $tipo_tramite_unidad->idTipo_tramite_unidad==41) {
                $docente2=PersonaSga::select('persona.per_id','persona.per_mail as correo','persona.per_email_institucional as correounitru',
                'persona.per_apellidos as apellidos','persona.per_nombres as nombres')
                ->where('persona.per_nombres',$request->nombres)
                ->where('persona.per_apellidos',$request->apellidos)
                ->where('persona.per_dni',$request->dni)->first();

                $docente = (object)[];
                $docente->per_id=$docente2->per_id;
                $docente->correo=$docente2->correo;
                $docente->correounitru=$docente2->correounitru;
                $docente->apellidos=$docente2->apellidos;
                $docente->nombres=$docente2->nombres;

                $perfil=Perfil::where('perfil.per_id',$docente->per_id)->first();
                $perfil->dep_id=$request->idDepartamento;
                $perfil->update();

            }else {
                    $tramite=Tramite::find($request->idTramite);
                    $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                    $docente=DocenteURA::find($tramite_detalle->idDocente);
                    $docente->apellidos=$request->apellidos;
                    $docente->nombres=$request->nombres;
                    $docente->idProfesion=$request->idProfesion;
                    $docente->sexo=$request->sexo;
                    $docente->fecha_nacimiento=$request->fecha_nacimiento;
                    $docente->direccion=$request->direccion;
                    $docente->idPais=$request->idPais;
                    $docente->per_login=$per_login;
                    $docente->dni=$request->dni;
                    $docente->telefono=$request->telefono;
                    $docente->celular=$request->celular;
                    $docente->correo=$request->correo;
                    $docente->correounitru=$request->correounitru;
                    $docente->jefe=$request->jefe;
                    $docente->idDependencia=$request->idDependencia;
                    $docente->idDepartamento=$request->idDepartamento;
                    $docente->idSede=$request->idSede;
                    $docente->idCondicion=$request->idCondicion;
                    $docente->idCategoria=$request->idCategoria;
                    $docente->idDedicacion=$request->idDedicacion;
                    $docente->update();

                    $persona=new PersonaSga();
                    $persona->pon_id=$request->idProfesion;
                    $persona->per_login=(string)$per_login;
                    $persona->per_password=md5($per_login);
                    $persona->per_nombres=$request->nombres;
                    $persona->per_apellidos=$request->apellidos;
                    $persona->per_sexo=$request->sexo;
                    $persona->per_fnaci=$request->fecha_nacimiento;
                    $persona->per_direccion=$request->direccion;
                    $persona->per_cod_pais=(string)$request->idPais;
                    $persona->per_dni=$request->dni;
                    $persona->per_telefono=$request->telefono;
                    $persona->per_celular=$request->celular;
                    $persona->per_mail=$request->correo;
                    $persona->per_estado=1;
                    $persona->per_email_institucional=$request->correounitru;
                    $persona->save();

                    $perfil=new Perfil();
                    $perfil->sed_id=$request->idSede;
                    $perfil->uni_id=$request->idDependencia;
                    $perfil->dep_id=$request->idDepartamento;
                    $perfil->ded_id=$request->idDedicacion;
                    $perfil->cia_id=$request->idCategoria;
                    $perfil->cgo_id=2;
                    $perfil->per_id=$persona->per_id;
                    $perfil->pfl_main=1;
                    $perfil->pfl_boss=$request->jefe;
                    $perfil->pfl_cond=$request->idCondicion;
                    $perfil->pfl_estado=1;
                    $perfil->save();

                    $array = array(8, 9, 10, 73,123,140);
                    foreach ($array as $valor) {
                        $permisos= new PermisosDocente;
                        $permisos->pfl_id=$perfil->pfl_id;
                        $permisos->tar_id=$valor;
                        $permisos->pso_estado=1;
                        $permisos->save();
                    }

                    $personaSUNT=new PersonaSUNT();
                    $personaSUNT->pon_id=$request->idProfesion;
                    $personaSUNT->per_login=(string)$per_login;
                    $personaSUNT->per_password=md5($per_login);
                    $personaSUNT->per_nombres=$request->nombres;
                    $personaSUNT->per_apellidos=$request->apellidos;
                    $personaSUNT->per_sexo=$request->sexo;
                    $personaSUNT->per_fnaci=$request->fecha_nacimiento;
                    $personaSUNT->per_direccion=$request->direccion;
                    $personaSUNT->per_cod_pais=(string)$request->idPais;
                    $personaSUNT->per_dni=$request->dni;
                    $personaSUNT->per_telefono=$request->telefono;
                    $personaSUNT->per_celular=$request->celular;
                    $personaSUNT->per_mail=$request->correo;
                    $personaSUNT->per_estado=1;
                    $personaSUNT->per_email_institucional=$request->correounitru;
                    $personaSUNT->save();

                    $usuarioSUNT=new UsuarioSUNT();
                    $usuarioSUNT->per_id=$personaSUNT->per_id;
                    $usuarioSUNT->sis_id=1;
                    $usuarioSUNT->usu_fecha=date('Y-m-d');
                    $usuarioSUNT->usu_estado=1;
                    $usuarioSUNT->save();
            
                  }
     
            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            $historial_estado=$this->setHistorialEstado($tramite->idTramite, 7, 8, $idUsuario);
            $historial_estado->save();
            $historial_estado=$this->setHistorialEstado($tramite->idTramite, 8,15, $idUsuario);
            $historial_estado->save();
            $tramite -> idEstado_tramite=15;
            $tramite->save();

            $tramite_requisito = Tramite_Requisito::where('tramite_requisito.idTramite',$tramite->idTramite)->first();
            $tramite_requisito->idUsuario_aprobador=$idUsuario;
            $tramite_requisito->des_estado_requisito="APROBADO";
            $tramite_requisito->update();

                $oti="dsc@unitru.edu.pe";
                if ($docente->correounitru) {
                    
                    $copias=[$docente->correo,$docente->correounitru,$oti];
                }else{
                    $copias=[$docente->correo,$oti];
                }
                if($usuario->correo2){
                    array_push($copias,$usuario->correo2);
                }
                dispatch(new DocenteTramiteJob($departamento,$usuario,$docente,$tramite,$tipo_tramite,$tipo_tramite_unidad,$copias));
                DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function rechazarDocente(Request $request,$id){

        DB::beginTransaction();
        try{
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
            $usuario = User::findOrFail($idUsuario);

            $tramite=Tramite::findOrFail($id);
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::findOrFail($tramite->idTipo_tramite_unidad);
            $tipo_tramite = Tipo_Tramite::findOrFail($tipo_tramite_unidad->idTipo_tramite);
        
            $tramite=Tramite::find($id);
            $historial_estado=$this->setHistorialEstado($id, 7, 9, $idUsuario);
            $historial_estado->save();
            
            $historial_estado=$this->setHistorialEstado($id, 9,51, $idUsuario);
            $historial_estado->save();

            $tramite -> idEstado_tramite=51;
            $tramite->update();

            $requisito = Tramite_Requisito::where('tramite_requisito.idTramite',$id)->first();
            $requisito->des_estado_requisito="RECHAZADO";
            $requisito->update();

            dispatch(new ObservacionDocenteTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }

    }
    public function GetDocenteFinalizados(Request $request){

        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $usuario = User::findOrFail($idUsuario);
        $idTipo_usuario=$apy['idTipo_usuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite_requisito.archivo','tramite.nro_tramite','tramite_detalle.idDocente',
        DB::raw("CONCAT(usuario.apellidos,' ',usuario.nombres) as solicitante"),'docentes.per_login','docentes.idDepartamento','tramite.created_at as fecha','tipo_tramite_unidad.descripcion as tramite',
        'docentes.*')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('docentes','docentes.idDocente','tramite_detalle.idDocente')
        ->join('tramite_requisito','tramite_requisito.idTramite','tramite.idTramite')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->where(function($query) 
        {            
            $query->where('tramite.idTipo_tramite_unidad',38)
            ->orWhere('tramite.idTipo_tramite_unidad',39)
            ->orWhere('tramite.idTipo_tramite_unidad',40)
            ->orWhere('tramite.idTipo_tramite_unidad',41);
        })
        ->where('tramite.idEstado_tramite',15)
        ->where(function($query) use ($idTipo_usuario,$idUsuario)
        {   
            if($idTipo_usuario==21){
                $query->where('tramite.idUsuario',$idUsuario);
            }
                
        })
        ->where(function($query) use ($request)
        {
            $query->where('tramite.nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.per_login','LIKE', '%'.$request->query('search').'%');

        })
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))->get();
        
        $total=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite_requisito.archivo','tramite.nro_tramite','tramite_detalle.idDocente',
        DB::raw("CONCAT(usuario.apellidos,' ',usuario.nombres) as solicitante"),'docentes.per_login as codigoDocente','docentes.idDepartamento','tramite.created_at as fecha','tipo_tramite_unidad.descripcion as tramite',
        'docentes.*')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('docentes','docentes.idDocente','tramite_detalle.idDocente')
        ->join('tramite_requisito','tramite_requisito.idTramite','tramite.idTramite')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->where(function($query) 
        {            
            $query->where('tramite.idTipo_tramite_unidad',38)
            ->orWhere('tramite.idTipo_tramite_unidad',39)
            ->orWhere('tramite.idTipo_tramite_unidad',40)
            ->orWhere('tramite.idTipo_tramite_unidad',41);
        })
        ->where('tramite.idEstado_tramite',15)
        ->where(function($query) use ($request)
        {
            $query->where('tramite.nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('docentes.per_login','LIKE', '%'.$request->query('search').'%');
        })
        ->count();

        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable','requisito.extension','tramite_requisito.idTramite')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();

            $tramite->departamentoDocente=DependenciaSGA::select('dependencia.dep_nombre')
            ->where('dependencia.dep_id',$tramite->idDepartamento)->first();

        }

        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200', 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);

    }
}
