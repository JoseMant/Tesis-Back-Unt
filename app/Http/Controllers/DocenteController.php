<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Manager;
use App\Docente;
use App\CargaLectiva;
use App\PersonaSuv;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Parser\Parser;
use Tymon\JWTAuth\Support\CustomClaims;
use Illuminate\Support\Str;

class DocenteController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwtStatic');
    }

    public function GetDocente(Request $request){
        // return $docente=Docente::all();
        DB::beginTransaction();
        try {
            // OBTENEMOS LOS DATOS DEL TOKEN
            $token = JWTAuth::getToken();
            $tokenParts = explode(".", (string)$token);  
            $tokenHeader = base64_decode($tokenParts[0]);
            $tokenPayload = base64_decode($tokenParts[1]);
            $jwtHeader = json_decode($tokenHeader, true);
            $jwtPayload = json_decode($tokenPayload, true);
            // return $jwtPayload;
            if ($jwtPayload['userName']==="dpa_unt" && $jwtPayload['aud']==="https://dpaunt.edu.pe") {
                $docente=Docente::select('trabajador.idtrabajador', 'trabajador.trab_codigo as cod_docente',  
                DB::raw("CONCAT(per.per_nombres,' ',per.per_apepaterno,' ',per.per_apematerno) as docente"), 
                'per.per_dni as dni', 'per.per_celular as celular', 'per.per_email_institucional as email', 
                'est.estr_descripcion as departamento', 'facu.estr_descripcion as facultad',
                'c.cargo_descripcion as cargo')
                ->join('patrimonio.area as ar','ar.idarea','trabajador.idarea')
                ->join('patrimonio.estructura as est','ar.idestructura','est.idestructura')
                ->join('patrimonio.estructura as facu','est.iddependencia','facu.idestructura')
                ->join('sistema.persona as per','trabajador.idpersona','per.idpersona')
                ->leftjoin('escalafon.cargo as c','c.idcargo','trabajador.idcargo')
                ->where('trabajador.trab_codigo',$request->cod_docente)
                ->wherein('trabajador.tipo',array(1,4))
                ->where('trabajador.trab_estado',TRUE)
                ->first();
                if ($docente) {
                    return response()->json($docente, 200);
                }else {
                    return response()->json(['status' => '400', 'message' => 'Docente no encontrado'], 400);
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e], 400);
        }
    }
    public function getCursosDocentePrincipal(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS LOS DATOS DEL TOKEN
            $token = JWTAuth::getToken();
            $tokenParts = explode(".", (string)$token);  
            $tokenHeader = base64_decode($tokenParts[0]);
            $tokenPayload = base64_decode($tokenParts[1]);
            $jwtHeader = json_decode($tokenHeader, true);
            $jwtPayload = json_decode($tokenPayload, true);
            // return $jwtPayload;
            if ($jwtPayload['userName']==="dpa_unt" && $jwtPayload['aud']==="https://dpaunt.edu.pe") {
                $cursos=CargaLectiva::select('sd.sed_descripcion as sede', 'cur.cur_descripcion as curso',
                DB::raw("CASE
                    WHEN curri.curr_descripcion IS NULL THEN CASE WHEN cp.idcurricula = 509 THEN 'EG - CIENCIAS BASICAS Y TECNOLOGICAS'
                    WHEN cp.idcurricula = 510 THEN 'EG - VIDA Y SALUD'
                    WHEN cp.idcurricula = 511 THEN 'EG - CIENCIAS DE LA PERSONA'
                    WHEN cp.idcurricula = 512 THEN 'EG - CIENCIAS ECONOMICAS'
                END
                ELSE curri.curr_descripcion
                END as curricula"),
                'cargalectiva.al_numerogrupos as grupo',
                DB::raw("CASE
                    WHEN curri.idcurricula = 52 THEN CASE WHEN cur.cur_ciclo=1 OR cur.cur_ciclo=2 THEN 1
                    WHEN cur.cur_ciclo=3 OR cur.cur_ciclo=4 THEN 2
                    WHEN cur.cur_ciclo=5 OR cur.cur_ciclo=6 THEN 3
                    WHEN cur.cur_ciclo=7 OR cur.cur_ciclo=8 THEN 4
                    WHEN cur.cur_ciclo=9 OR cur.cur_ciclo=10 THEN 5
                    WHEN cur.cur_ciclo=11 OR cur.cur_ciclo=12 THEN 6
                    WHEN cur.cur_ciclo=13 OR cur.cur_ciclo=14 THEN 7
                END
                ELSE cur.cur_ciclo 
                END as ciclo"),
                'cur.cur_hrsteoria as hrs_teoria', 'cur.cur_hrspractica as hrs_practica','cur.cur_hrslab as hrs_lab', 'cur.cur_numcreditos as num_creditos' 
                )
                ->join('escalafon.trabajador as tr','cargalectiva.idtrab','tr.idtrabajador')
                ->join('asignacion.grupolectivo as gl',function($join){
                    $join->on('cargalectiva.idcursoprogramado', '=', 'gl.idcursoprogramado');
                    $join->on('cargalectiva.al_numerogrupos', '=', 'gl.gul_numgrupo');
                })
                ->join('asignacion.cursoprogramado as cp','cargalectiva.idcursoprogramado','cp.idcursoprogramado')
                ->join('matriculas.curso as cur','cp.idcurso','cur.idcurso')
                ->join('asignacion.curso_dictar as cd','cp.idcursodictar','cd.idcursodictar')
                ->join('patrimonio.sede as sd','cd.idsede','sd.idsede')
                ->leftjoin('matriculas.curricula as curri','cp.idcurricula','curri.idcurricula')
                ->where('cargalectiva.estado','1')
                ->where('tr.trab_codigo',$request->cod_docente)
                ->where('cargalectiva.cal_esprincipal','SI')
                ->where('gl.gl_estado',TRUE)
                ->where('cp.idperiodo',$request->periodo)
                ->orderBy('sd.idsede')
                ->orderBy('curricula')
                ->orderBy('ciclo')
                ->orderBy('cur.idcurso')
                ->orderBy('grupo')
                ->get();
                if (count($cursos)>0) {
                    return response()->json($cursos, 200);
                }else {
                    return response()->json(['status' => '400', 'message' => 'Docente no encontrado'], 400);
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e], 400);
        }
    }

}
