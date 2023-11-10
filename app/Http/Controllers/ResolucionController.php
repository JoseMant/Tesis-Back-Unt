<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Resolucion;
use App\Cronograma;
use App\Tipo_Resolucion;
use App\Tramite;
use App\Tramite_Detalle;

class ResolucionController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt');
    }

    public function index(Request $request){
        $resoluciones=Resolucion::select('*', DB::raw('YEAR(fecha) as anio'))
        ->join('tipo_resolucion','resolucion.idTipo_resolucion','tipo_resolucion.idTipo_resolucion')
        ->where('resolucion.tipo_emision',$request->tipo_emision)
        ->where('resolucion.estado',1)
        ->where(function($query) use ($request)
        {
            $query->where('fecha','LIKE', '%'.$request->query('search').'%')
            ->orWhere('nro_resolucion','LIKE', '%'.$request->query('search').'%');
        })
        ->orderBy('fecha','desc')
        ->get();
        foreach ($resoluciones as $key => $resolucion) {
            $resolucion->cronogramas=Cronograma::select('cronograma_carpeta.*','dependencia.nombre as dependencia',
            DB::raw("(case 
                    when cronograma_carpeta.idTipo_tramite_unidad = 15 then CONCAT(unidad.descripcion,'-','BACHILLER') 
                    when cronograma_carpeta.idTipo_tramite_unidad = 16 then CONCAT(unidad.descripcion,'-','TITULO PROFESIONAL') 
                    when cronograma_carpeta.idTipo_tramite_unidad = 34 then CONCAT(unidad.descripcion,'-','TITULO DE SEGUNDA ESPECIALIDAD PROFESIONAL') 
                end) as unidad"))
            ->join('dependencia','dependencia.idDependencia','cronograma_carpeta.idDependencia')
            ->join('unidad','unidad.idUnidad','cronograma_carpeta.idUnidad')
            ->where('idResolucion',$resolucion->idResolucion)->where('cronograma_carpeta.estado',1)->get();

            $resolucion->tramites=Tramite::select('tramite.idTramite',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'tipo_tramite_unidad.descripcion as tramite'
            ,'dependencia.nombre as dependencia','programa.nombre as programa')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa','programa.idPrograma','tramite.idPrograma')
            ->where('tramite.estado',1)
            ->where('tipo_tramite_unidad.idTipo_tramite',5)
            ->where('tramite.idEstado_tramite',42)
            ->where('tramite_detalle.idResolucion_rectoral',$resolucion->idResolucion)
            ->get();
        }
        
        return response()->json($resoluciones, 200);
    }

    public function store(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
           
            $resolucionValidate=Resolucion::where('nro_resolucion',$request->nro_resolucion)->where('idTipo_resolucion',$request->idTipo_resolucion)->first();
            if ($resolucionValidate) {
                return response()->json( ['status'=>400,'message'=>'La resolución ya se encuentra registrada'],400);
            }

            $resolucion=new Resolucion;
            $resolucion->nro_resolucion=trim($request->nro_resolucion);
            $resolucion->idTipo_resolucion=$request->idTipo_resolucion;
            $resolucion->tipo_emision=$request->tipo_emision;
            if(substr($resolucion->nro_resolucion,-4)!="/UNT"){
                return response()->json( ['status'=>400,'message'=>'El número de resolucion debe terminar en /UNT'],400);
            }
            $resolucion->fecha=trim($request->fecha);
            $resolucion->estado =true;
            $resolucion->save();


            if ($request->tipo_emision=='O') {
                if ($request->cronogramas) {
                    foreach ($request->cronogramas as $key => $value) {
                        $cronograma=Cronograma::find($value['idCronograma_carpeta']);
                        $cronograma->idResolucion=$resolucion->idResolucion;
                        $cronograma->update();
                    }
                }else {
                    return response()->json(['status' => '400', 'message' => "Asignar algún cronograma para la resolución"], 400);
                }
            }elseif ($request->tipo_emision=='D') {
                if ($request->tramites) {
                    foreach ($request->tramites as $key => $value) {
                        $tramite=Tramite::find($value['idTramite']);
                        $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                        $tramite_detalle->idResolucion_rectoral=$resolucion->idResolucion;
                        $tramite_detalle->update();
                    }
                }else {
                    return response()->json(['status' => '400', 'message' => "Asignar algún trámite de duplicado para la resolución"], 400);
                }
            }
            
            $resolucion->anio = substr($resolucion->fecha, 0, 4);
            $resolucion->cronogramas=$request->cronogramas;
            $resolucion->tramites=$request->tramites;
            DB::commit();
            return response()->json($resolucion, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request,$id){
        DB::beginTransaction();
        try {
            // return $request->all();
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $dni=$apy['nro_documento'];
           
            $resolucion=Resolucion::find($id);
            $resolucion->nro_resolucion=trim($request->nro_resolucion);
            $resolucion->idTipo_resolucion=$request->idTipo_resolucion;
            $resolucion->tipo_emision=$request->tipo_emision;
            $resolucion->fecha=trim($request->fecha);
            $resolucion->estado =trim($request->estado);
            $resolucion->save();

            if ($request->tipo_emision=='O') {
                if ($request->cronogramas) {
                    //Eliminamos todas las relaciones de los cronogramas que pertenecen a esa resolucion
                    $cronogramas=Cronograma::where('idResolucion',$id)->get();
                    foreach ($cronogramas as $key => $value) {
                        $value->idResolucion=null;
                        $value->save();
                    }
                    //agregamos las nuevas relaciones de cronogramas
                    foreach ($request->cronogramas as $key => $value) {
                        $cronograma=Cronograma::find($value['idCronograma_carpeta']);
                        $cronograma->idResolucion=$resolucion->idResolucion;
                        $cronograma->save();
                    }
                } else {
                    return response()->json(['status' => '400', 'message' => "Asignar algún cronograma para la resolución"], 400);
                }
            }elseif ($request->tipo_emision=='D') {
                if ($request->tramites) {
                    //Eliminamos todas las relaciones de los trámites que pertenecen a esa resolucion
                    $tramites=Tramite::select('tramite_detalle.idTramite_detalle')->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                    ->where('tramite_detalle.idResolucion_rectoral',$id)->get();
                    foreach ($tramites as $key => $value) {
                        $tramite_detalle=Tramite_Detalle::find($value['idTramite_detalle']);
                        $tramite_detalle->idResolucion_rectoral=null;
                        $tramite_detalle->save();
                    }
                    //agregamos las nuevas relaciones de trámites
                    foreach ($request->tramites as $key => $value) {
                        $tramite=Tramite::find($value['idTramite']);
                        $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                        $tramite_detalle->idResolucion_rectoral=$resolucion->idResolucion;
                        $tramite_detalle->update();
                    }
                }
            }
            
            $resolucion->anio = substr($resolucion->fecha, 0, 4);
            $resolucion->cronogramas=$request->cronogramas;
            $resolucion->tramites=$request->tramites;
            DB::commit();
            return response()->json($resolucion, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function getResolucionesLibres($idOficio){
        $resoluciones=Resolucion::join('tipo_resolucion','tipo_resolucion.idTipo_resolucion','resolucion.idTipo_resolucion')
        ->where(function($query) use ($idOficio)
        {
            if ($idOficio!=0) {
                $query->where('idOficio',null)
                ->orWhere('idOficio',$idOficio);
            }else {
                $query->where('idOficio',null);
            }
            
        })
        ->where('resolucion.estado',1)
        ->orderBy('resolucion.nro_resolucion')
        ->get();
        return response()->json($resoluciones, 200);

    }

    public function getTipoResoluciones(){
        return Tipo_Resolucion::where('estado',1)->get();
    }
    
    public function getTramitesDuplicadosLibres($idResolucion){
        $tramites=Tramite::select('tramite.idTramite',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'tipo_tramite_unidad.descripcion as tramite'
        ,'dependencia.nombre as dependencia','programa.nombre as programa')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa','programa.idPrograma','tramite.idPrograma')
        ->where(function($query) use ($idResolucion)
        {
            if ($idResolucion!=0) {
                $query->where('tramite_detalle.idResolucion_rectoral',null)
                ->orWhere('tramite_detalle.idResolucion_rectoral',$idResolucion);
            }else {
                $query->where('tramite_detalle.idResolucion_rectoral',null);
            }
        })
        ->where('tramite.estado',1)
        ->where(function($query)
        {
            $query->where('tipo_tramite_unidad.idTipo_tramite',6)
            ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
        })
        ->where('tramite.idEstado_tramite',42)
        ->orderBy('tramite.created_at')
        ->get();
        ;

        return response()->json($tramites, 200);
    }
}
