<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Escuela;
use App\User;
use App\Tramite;
use App\Tramite_Detalle;
use App\Resolucion;
use App\Cronograma;
use App\Diploma_Carpeta;
use App\Tramite_Requisito;
use App\Historial_Estado;
use App\Graduado;
use App\Libro;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Spipu\Html2Pdf\Html2Pdf;
use App\Historial_Codigo_Diploma;

class CarpetaController extends Controller
{

    public function GetGradosResolucion(Request $request,$idResolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $resolucion=Resolucion::find($idResolucion);

        if ($resolucion->tipo_emision=='O') {
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.sede','tramite.idEstado_tramite', 
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo', 'tramite_detalle.*', 'tramite.idTipo_tramite_unidad',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa','tipo_tramite_unidad.descripcion as tramite',
            'tipo_tramite_unidad.costo',DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo','resolucion.idResolucion', 
            'cronograma_carpeta.fecha_cierre_alumno','cronograma_carpeta.fecha_cierre_secretaria','cronograma_carpeta.fecha_cierre_decanato','cronograma_carpeta.fecha_colacion')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idEstado_tramite',42)
            ->where('tipo_tramite_unidad.idTipo_tramite',2)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->get();
        }elseif ($resolucion->tipo_emision=='D') {
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.sede','tramite.idEstado_tramite', 
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula',
            'tramite.exonerado_archivo', 'tramite_detalle.*', 'tramite.idTipo_tramite_unidad',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'voucher.archivo as voucher',
            'resolucion.idResolucion')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('resolucion', 'resolucion.idResolucion', 'tramite_detalle.idResolucion_rectoral')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idEstado_tramite',42)
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->get();
        }

        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
        $begin = ($pagination->currentPage()-1)*$pagination->perPage();
        $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
        return response()->json(['status' => '200','resolucion'=>$resolucion, 'data' =>array_values($pagination->items()),"pagination"=>[
            'length'    => $pagination->total(),
            'size'      => $pagination->perPage(),
            'page'      => $pagination->currentPage()-1,
            'lastPage'  => $pagination->lastPage()-1,
            'startIndex'=> $begin,
            'endIndex'  => $end - 1
        ]], 200);
    }

    public function registrarEnLibro(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            
            $resolucion=Resolucion::find($request->idResolucion);

            if ($resolucion->tipo_emision=='O') { // Trámites de carpetas regulares
                // Recorremos todos los trámites y le añadimos su numeracion a cada uno
                $tramites=Tramite::join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
                ->where('tramite.idEstado_tramite','!=',42)
                ->where('tramite.idEstado_tramite','!=',29)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->count();  
                
                if ($tramites>0) {
                    DB::rollback();
                    return response()->json(['status' => '400', 'message' =>"Hay ".$tramites." trámites en estados pendientes"], 400);
                }

                // Recorremos todos los trámites y le añadimos su numeracion a cada uno
                $tramites=Tramite::select('tramite.*','dependencia.idDependencia2')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
                ->join('programa','programa.idPrograma','tramite.idPrograma')
                ->where('tramite.idEstado_tramite',42)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->orderBy('tramite.idTipo_tramite_unidad','asc')
                ->orderBy('dependencia.nombre','asc')
                ->orderBy('programa.nombre','asc')
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->get();  
            }elseif ($resolucion->tipo_emision=='D') { // Trámites de carpetas duplicados
                // Recorremos todos los trámites y le añadimos su numeracion a cada uno
                $tramites=Tramite::select('tramite.*','dependencia.idDependencia2')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
                ->join('programa','programa.idPrograma','tramite.idPrograma')
                ->where('tramite.idEstado_tramite',42)
                ->where(function($query)
                {
                    $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                    ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
                })
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->orderBy('tramite.idTipo_tramite_unidad','asc')
                ->orderBy('dependencia.nombre','asc')
                ->orderBy('programa.nombre','asc')
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->get();
            }

            foreach ($tramites as $key => $tramite) {
                // obtenemos datos del último registro del libro
                if ($resolucion->tipo_emision=='O') {
                    $ultimoRegistro=Libro::where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)->orderBy('nro_registro','desc')
                    ->limit(1)
                    ->first();
                }elseif ($resolucion->tipo_emision=='D') {
                    $ultimoRegistro=Libro::whereIn('idTipo_tramite_unidad',[42,43,44,47,48,49])
                    ->orderBy('nro_registro','desc')
                    ->limit(1)
                    ->first();
                }
                
                // GUARDAMOS EL REGISTRO EN EL LIBRO
                $newRegistro=new Libro();
                if ($ultimoRegistro->folio==200 && $ultimoRegistro->contador==20) {
                    $newRegistro->nro_libro=$ultimoRegistro->nro_libro+1;
                    $newRegistro->folio=1;
                    $newRegistro->nro_registro=$ultimoRegistro->nro_registro+1;
                    $newRegistro->contador=1;
                }elseif ($ultimoRegistro->contador==20) {
                    $newRegistro->nro_libro=$ultimoRegistro->nro_libro;
                    $newRegistro->folio=$ultimoRegistro->folio+1;
                    $newRegistro->nro_registro=$ultimoRegistro->nro_registro+1;
                    $newRegistro->contador=1;
                }else {
                    $newRegistro->nro_libro=$ultimoRegistro->nro_libro;
                    $newRegistro->folio=$ultimoRegistro->folio;
                    $newRegistro->nro_registro=$ultimoRegistro->nro_registro+1;
                    $newRegistro->contador=$ultimoRegistro->contador+1;
                }
                $newRegistro->idTipo_tramite_unidad=$tramite->idTipo_tramite_unidad;
                $newRegistro->save();

                
                

                //Obtenemos el detalle de cada uno de los trámites Y ACTUALIZAMOS LOS DATOS QUE VAN EN EL LIBRO
                $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                $tramite_detalle->nro_libro=$newRegistro->nro_libro;
                $tramite_detalle->folio=$newRegistro->folio;
                $tramite_detalle->nro_registro=$newRegistro->nro_registro;
                $tramite_detalle->idTipo_tramite_unidad=$newRegistro->idTipo_tramite_unidad;
                // Agregando la fecha de emisión de duplicados
                if ($resolucion->tipo_emision=='D') {
                    $tramite_detalle->fecha_emision_duplicado=(Carbon::parse(Carbon::now()))->format('Y-m-d');
                }

                // Registramos el código de diploma
                $inicio=null;
                $fin=null;
                if($tramite->idTipo_tramite_unidad==15){
                    $inicio="B";
                    $fin="O";
                }elseif($tramite->idTipo_tramite_unidad==16){
                    $inicio="T";
                    $fin="O";
                }elseif ($tramite->idTipo_tramite_unidad==34){
                    $inicio="S";
                    $fin="O";
                }elseif($tramite->idTipo_tramite_unidad==42||$tramite->idTipo_tramite_unidad==47){
                    $inicio="B";
                    $fin="D";
                }elseif($tramite->idTipo_tramite_unidad==43||$tramite->idTipo_tramite_unidad==48){
                    $inicio="T";
                    $fin="D";
                }elseif ($tramite->idTipo_tramite_unidad==44||$tramite->idTipo_tramite_unidad==49){
                    $inicio="S";
                    $fin="D";
                }


                $tramite_detalle->codigo_diploma=$inicio.$newRegistro->nro_registro.$fin;
                $tramite_detalle->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 43, $idUsuario);
                $historial_estado->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 43, 13, $idUsuario);
                $historial_estado->save();

                $tramite->idEstado_tramite=13;
                $tramite->save();
            }

            DB::commit();
            return response()->json($tramites, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function GetCarpetasFirmaDecano(Request $request,$idResolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];
        
        $resolucion=Resolucion::find($idResolucion);
        
        if ($resolucion->tipo_emision=='O') {
            $tramites=Tramite::select( 'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad3', 'unidad.descripcion as unidad','programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'))
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                // SI DECANO VUELVE A INGRESAR DESPUÉS DE FIRMAR, DEBE APARECERLE TODOS LOS QUE ÉL HA FIRMADO (AUTORIDAD3=$IDUSUARIO) 
                $query->where('tramite.idEstado_tramite',13)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();
    
            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                $query->where('tramite.idEstado_tramite',13)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->count();
        }elseif ($resolucion->tipo_emision=='D') {
            $tramites=Tramite::select( 'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad3', 'unidad.descripcion as unidad','programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'))
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                // SI DECANO VUELVE A INGRESAR DESPUÉS DE FIRMAR, DEBE APARECERLE TODOS LOS QUE ÉL HA FIRMADO (AUTORIDAD3=$IDUSUARIO) 
                $query->where('tramite.idEstado_tramite',13)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();
    
            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                $query->where('tramite.idEstado_tramite',13)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->count();
        }

        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200','resolucion'=>$resolucion, 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }
    
    public function GetCarpetasFirmaRector(Request $request,$idResolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];
        
        $resolucion=Resolucion::find($idResolucion);

        if ($resolucion->tipo_emision=='O') {
            $tramites=Tramite::select( 'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad1', 'unidad.descripcion as unidad','programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'))
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                // SI DECANO VUELVE A INGRESAR DESPUÉS DE FIRMAR, DEBE APARECERLE TODOS LOS QUE ÉL HA FIRMADO (AUTORIDAD3=$IDUSUARIO) 
                $query->where('tramite.idEstado_tramite',48)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();
    
            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                $query->where('tramite.idEstado_tramite',48)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->count();
        }elseif ($resolucion->tipo_emision=='D') {
            $tramites=Tramite::select( 'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad1', 'unidad.descripcion as unidad','programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'))
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                // SI DECANO VUELVE A INGRESAR DESPUÉS DE FIRMAR, DEBE APARECERLE TODOS LOS QUE ÉL HA FIRMADO (AUTORIDAD3=$IDUSUARIO) 
                $query->where('tramite.idEstado_tramite',48)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();
    
            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                $query->where('tramite.idEstado_tramite',48)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->count();
        }
        
        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200','resolucion'=>$resolucion, 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }

    public function GetCarpetasFirmaSecretaria(Request $request,$idResolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];
        
        $resolucion=Resolucion::find($idResolucion);

        if ($resolucion->tipo_emision=='O') {
            $tramites=Tramite::select( 'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad2', 'unidad.descripcion as unidad','programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'tramite.idEstado_tramite')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                // SI DECANO VUELVE A INGRESAR DESPUÉS DE FIRMAR, DEBE APARECERLE TODOS LOS QUE ÉL HA FIRMADO (AUTORIDAD3=$IDUSUARIO) 
                $query->where('tramite.idEstado_tramite',46)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();
    
            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                $query->where('tramite.idEstado_tramite',46)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->count();
        }elseif ($resolucion->tipo_emision=='D') {
            $tramites=Tramite::select( 'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad2', 'unidad.descripcion as unidad','programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'tramite.idEstado_tramite')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                // SI DECANO VUELVE A INGRESAR DESPUÉS DE FIRMAR, DEBE APARECERLE TODOS LOS QUE ÉL HA FIRMADO (AUTORIDAD3=$IDUSUARIO) 
                $query->where('tramite.idEstado_tramite',46)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->orderBy($request->query('sort'), $request->query('order'))
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();
    
            $total=Tramite::join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
            })
            ->where(function($query) use ($idDependencia)
            {
                if ($idDependencia!=null) {
                    $query->where('tramite.idDependencia',$idDependencia)
                    ->orWhere('dependencia.idDependencia2',$idDependencia);
                }
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->where(function($query) use($idUsuario)
            {
                $query->where('tramite.idEstado_tramite',46)
                ->orWhere('tramite_detalle.autoridad3',$idUsuario);
            })
            ->count();
        }
        
        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200','resolucion'=>$resolucion, 'data' =>$tramites,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }


    // Firmas   
    public function firmaDecano(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $idDependencia=$apy['idDependencia'];

            $resolucion=Resolucion::find($request->idResolucion);
        
            // Recorremos todos los trámite
            if ($resolucion->tipo_emision=='O') {
                $tramites=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite.idEstado_tramite')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
                ->where('tramite.idEstado_tramite',13)
                ->where('resolucion.idResolucion',$resolucion->idResolucion)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                ->where(function($query) use ($idDependencia)
                {
                    if ($idDependencia!=null) {
                        $query->where('tramite.idDependencia',$idDependencia)
                        ->orWhere('dependencia.idDependencia2',$idDependencia);
                    }
                })
                ->get();
            }elseif ($resolucion->tipo_emision=='D') {
                $tramites=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite.idEstado_tramite')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
                ->where('tramite.idEstado_tramite',13)
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->where(function($query)
                {
                    $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                    ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
                })
                ->where(function($query) use ($idDependencia)
                {
                    if ($idDependencia!=null) {
                        $query->where('tramite.idDependencia',$idDependencia)
                        ->orWhere('dependencia.idDependencia2',$idDependencia);
                    }
                })
                ->get();
            }
            

            foreach ($tramites as $key => $tramite) {
                // Cambiando el estado a firma de Rector
                $tramite_detalle=Tramite_detalle::where('idTramite_detalle',$tramite->idTramite_detalle)->first();
                $tramite_detalle->autoridad3=$idUsuario;
                $tramite_detalle->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE FIRMADO POR DECANO
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 14, $idUsuario);
                $historial_estado->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE PENDIENTE DE FIRMA DEL RECTOR
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 14, 48, $idUsuario);
                $historial_estado->save();

                $tramite->idEstado_tramite=48;
                $tramite->save();
            }

            if ($resolucion->tipo_emision=='O') {
                $tramites=Tramite::select( 'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad3', 'unidad.descripcion as unidad','programa.nombre as programa',
                'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'))
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
                ->where('tramite_detalle.autoridad3',$idUsuario)
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                ->where(function($query) use ($idDependencia)
                {
                    if ($idDependencia!=null) {
                        $query->where('tramite.idDependencia',$idDependencia)
                        ->orWhere('dependencia.idDependencia2',$idDependencia);
                    }
                })
                ->orderBy('tramite.idTipo_tramite_unidad','asc')
                ->orderBy('dependencia.nombre','asc')
                ->orderBy('programa.nombre','asc')
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->get(); 
            }elseif ($resolucion->tipo_emision=='D') {
                $tramites=Tramite::select( 'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad3', 'unidad.descripcion as unidad','programa.nombre as programa',
                'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'))
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
                ->where('tramite_detalle.autoridad3',$idUsuario)
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->where(function($query)
                {
                    $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                    ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
                })
                ->where(function($query) use ($idDependencia)
                {
                    if ($idDependencia!=null) {
                        $query->where('tramite.idDependencia',$idDependencia)
                        ->orWhere('dependencia.idDependencia2',$idDependencia);
                    }
                })
                ->orderBy('tramite.idTipo_tramite_unidad','asc')
                ->orderBy('dependencia.nombre','asc')
                ->orderBy('programa.nombre','asc')
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->get(); 
            }

            DB::commit();

            return response()->json($tramites,200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function firmaRector(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            $resolucion=Resolucion::find($request->idResolucion);

            // Recorremos todos los trámites
            if ($resolucion->tipo_emision=='O') {
                $tramites=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite.idEstado_tramite')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
                ->where('tramite.idEstado_tramite',48)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->get();  
            }elseif ($resolucion->tipo_emision=='D') {
                $tramites=Tramite::select('tramite.idTramite','tramite.idTramite_detalle','tramite.idEstado_tramite')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
                ->where('tramite.idEstado_tramite',48)
                ->where(function($query)
                {
                    $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                    ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
                })
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->get();  
            }

            foreach ($tramites as $key => $tramite) {
                // Cambiando el estado a firma de Rector
                $tramite_detalle=Tramite_detalle::where('idTramite_detalle',$tramite->idTramite_detalle)->first();
                $tramite_detalle->autoridad1=$idUsuario;
                $tramite_detalle->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE FIRMADO POR RECTOR
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 49, $idUsuario);
                $historial_estado->save();

                //REGISTRAMOS EL ESTADO DEL TRÁMITE PENDIENTE DE FIRMA DE SECRETARÍA GENERAL
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 49, 46, $idUsuario);
                $historial_estado->save();

                $tramite->idEstado_tramite=46;
                $tramite->save();
            }

            if ($resolucion->tipo_emision=='O') {
                $tramites=Tramite::select( 'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad1', 'unidad.descripcion as unidad','programa.nombre as programa',
                'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'))
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->where('tramite_detalle.autoridad1',$idUsuario)
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                ->orderBy('tramite.idTipo_tramite_unidad','asc')
                ->orderBy('dependencia.nombre','asc')
                ->orderBy('programa.nombre','asc')
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->get();  

            }elseif ($resolucion->tipo_emision=='D') {
                $tramites=Tramite::select( 'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad1', 'unidad.descripcion as unidad','programa.nombre as programa',
                'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'))
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->where('tramite_detalle.autoridad1',$idUsuario)
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->where(function($query)
                {
                    $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                    ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
                })
                ->orderBy('tramite.idTipo_tramite_unidad','asc')
                ->orderBy('dependencia.nombre','asc')
                ->orderBy('programa.nombre','asc')
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->get();  

            }

            DB::commit();

            return response()->json($tramites,200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function firmaSecretaria(Request $request){
        set_time_limit(0);
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            
            $resolucion=Resolucion::find($request->idResolucion);

            if ($resolucion->tipo_emision=='O') {
                // Recorremos todos los trámites y le añadimos su numeracion a cada uno
                $tramites=Tramite::select('tramite.idTramite', 'tramite.idUnidad','tramite.idEstado_tramite','tramite.idTramite_detalle', 
                'programa.denominacion as programa', 
                DB::raw("(case 
                            when tramite.idUnidad = 1 then dependencia.denominacion  
                            when tramite.idUnidad = 4 then  (select denominacion from dependencia d where d.idDependencia=dependencia.idDependencia2)
                        end) as facultad"),
                DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombreComp'),'usuario.tipo_documento','usuario.nro_documento',
                'tramite_detalle.codigo_diploma','tramite_detalle.nro_libro','tramite_detalle.folio','tramite_detalle.nro_registro',
                'tramite_detalle.autoridad1','tramite_detalle.autoridad2','tramite_detalle.autoridad3',
                DB::raw('CONCAT(rector.nombres," ",rector.apellidos) as nombre_rector'),
                'rector.cargo as cargo_rector', 'rector.sexo as sexo_rector','rector.grado as grado_rector',
                DB::raw('CONCAT(decano.nombres," ",decano.apellidos) as nombre_decano'),
                'decano.cargo as cargo_decano', 'decano.sexo as sexo_decano','decano.grado as grado_decano',
                'diploma_carpeta.descripcion as denominacion', 'tipo_tramite_unidad.diploma_obtenido',
                'cronograma_carpeta.fecha_colacion', 'resolucion.nro_resolucion', 'resolucion.fecha as fecha_resolucion',
                'modalidad_carpeta.acto_academico')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('usuario as rector','rector.idUsuario','tramite_detalle.autoridad1')
                ->join('usuario as decano','decano.idUsuario','tramite_detalle.autoridad3')
                ->join('diploma_carpeta','tramite_detalle.idDiploma_carpeta','diploma_carpeta.idDiploma_carpeta')
                ->join('modalidad_carpeta','tramite_detalle.idModalidad_carpeta','modalidad_carpeta.idModalidad_carpeta')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('programa','programa.idPrograma','tramite.idPrograma')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
                ->where('tramite.idEstado_tramite',46)
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                ->get();
            }elseif ($resolucion->tipo_emision=='D') {
                // Recorremos todos los trámites y le añadimos su numeracion a cada uno
                $tramites=Tramite::select('tramite.idTramite', 'tramite.idUnidad','tramite.idEstado_tramite','tramite.idTramite_detalle', 
                'programa.denominacion as programa', 
                DB::raw("(case 
                            when tramite.idUnidad = 1 then dependencia.denominacion  
                            when tramite.idUnidad = 4 then  (select denominacion from dependencia d where d.idDependencia=dependencia.idDependencia2)
                        end) as facultad"),
                DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombreComp'),'usuario.tipo_documento','usuario.nro_documento',
                'tramite_detalle.codigo_diploma','tramite_detalle.nro_libro','tramite_detalle.folio','tramite_detalle.nro_registro',
                'tramite_detalle.autoridad1','tramite_detalle.autoridad2','tramite_detalle.autoridad3',
                DB::raw('CONCAT(rector.nombres," ",rector.apellidos) as nombre_rector'),
                'rector.cargo as cargo_rector', 'rector.sexo as sexo_rector','rector.grado as grado_rector',
                DB::raw('CONCAT(decano.nombres," ",decano.apellidos) as nombre_decano'),
                'decano.cargo as cargo_decano', 'decano.sexo as sexo_decano','decano.grado as grado_decano',
                'diploma_carpeta.descripcion as denominacion', 'tipo_tramite_unidad.diploma_obtenido',
                'cronograma_carpeta.fecha_colacion', 'resolucion.nro_resolucion', 'resolucion.fecha as fecha_resolucion',
                'modalidad_carpeta.acto_academico')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('usuario as rector','rector.idUsuario','tramite_detalle.autoridad1')
                ->join('usuario as decano','decano.idUsuario','tramite_detalle.autoridad3')
                ->join('diploma_carpeta','tramite_detalle.idDiploma_carpeta','diploma_carpeta.idDiploma_carpeta')
                ->join('modalidad_carpeta','tramite_detalle.idModalidad_carpeta','modalidad_carpeta.idModalidad_carpeta')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('programa','programa.idPrograma','tramite.idPrograma')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
                ->where('tramite.idEstado_tramite',46)
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->where(function($query)
                {
                    $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                    ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
                })
                ->get();
            }
            
            // Obteniendo los datos de las autoridades
            $secretariaGeneral=User::select(DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as nombres'),'usuario.cargo','usuario.sexo','usuario.grado')
            ->where('idUsuario',$idUsuario)->first();
            
            foreach ($tramites as $key => $tramite) {                
                // Obteniendo datos de rector(a)
                $rector = (object)array(
                    "nombres" =>$tramite->nombre_rector,
                    "sexo" =>$tramite->sexo_rector,
                    "grado" =>$tramite->grado_rector,
                    "cargo" =>$tramite->cargo_rector,
                );

                // Obteniendo datos de decano(a)
                $decano = (object)array(
                    "nombres" =>$tramite->nombre_decano,
                    "sexo" =>$tramite->sexo_decano,
                    "grado" =>$tramite->grado_decano,
                    "cargo" =>$tramite->cargo_decano,
                );

                $tramite_detalle=Tramite_detalle::where('idTramite_detalle',$tramite->idTramite_detalle)->first();
                $tramite_detalle->autoridad2=$idUsuario;
                $tramite_detalle->nombre_descarga_sunedu='D004_'.$tramite->nro_documento.'_'.substr($tramite->diploma_obtenido, 0,1);

                if ($resolucion->tipo_emision=='O') {
                    $originalidad='O - ORIGINAL';
                    $tramite_detalle->diploma_final = "/storage/diplomas/".'D004_'.$tramite->nro_documento.'_'.substr($tramite->diploma_obtenido, 0,1).'.pdf';
                }elseif ($resolucion->tipo_emision=='D') {
                    $tramite_detalle->diploma_final = "/storage/diplomas_duplicados/".'D004_'.$tramite->nro_documento.'_'.substr($tramite->diploma_obtenido, 0,1).'.pdf';
                    $originalidad='D - DUPLICADO';
                }

                
                $tramite_detalle->save();
                
                // Colocando a cada uno de los trámites la firma de secretaria general
                $tramite->autoridad2=$idUsuario;

                // Obteniendo la foto para la diploma
                $requisito_foto=Tramite_Requisito::where('idTramite',$tramite->idTramite)
                ->where(function($query)
                {
                    // ORIGINALES
                    $query->where('idRequisito',15)
                    ->orWhere('idRequisito',23)
                    ->orWhere('idRequisito',61)
                    // DUPLICADO DE DIPLOMA POR PÉRDIDA 
                    ->orWhere('idRequisito',77)
                    ->orWhere('idRequisito',79)
                    ->orWhere('idRequisito',81)
                    // DUPLICADO DE DIPLOMA POR DETERIORO O MUTILACIÓN
                    ->orWhere('idRequisito',98)
                    ->orWhere('idRequisito',104)
                    ->orWhere('idRequisito',110);
                })->first();

                // Creando el diploma con los datos obtenidos
                $html2pdf = new Html2Pdf('L', 'A4', 'es', true, 'UTF-8');
                $html2pdf->AddFont('algerian', '', 'algerian.php');
                $html2pdf->writeHTML(view('diploma.diploma', [
                    'foto_interesado'=>$requisito_foto->archivo,
                    'decano'=>$decano,'secretaria'=>$secretariaGeneral,'rector'=>$rector,
                    'emision_diploma'=>$originalidad,
                    'tramite'=>$tramite
                ]));

                // Guardar el pdf generado en la ruta especificada
                if ($resolucion->tipo_emision=='O') {
                    $html2pdf->output(storage_path('app/public').'/diplomas/D004_'.$tramite->nro_documento.'_'.substr($tramite->diploma_obtenido, 0,1).'.pdf','F');
                }elseif ($resolucion->tipo_emision=='D') {
                    $html2pdf->output(storage_path('app/public').'/diplomas_duplicados/D004_'.$tramite->nro_documento.'_'.substr($tramite->diploma_obtenido, 0,1).'.pdf','F');
                }
                // $html2pdf->output(storage_path('app/public').'/diplomas/T004_'.$tramite->nro_documento.'_'.substr($tramite->diploma_obtenido, 0,1).'.pdf','F');
            }

            if ($resolucion->tipo_emision=='O') {
                $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
                'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad2', 'tramite.idTipo_tramite_unidad', 
                'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa', 'tipo_tramite_unidad.descripcion as tramite',
                DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'resolucion.idResolucion','tramite.idEstado_tramite')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->where('tramite_detalle.autoridad2',$idUsuario)
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                ->orderBy('tramite.idTipo_tramite_unidad','asc')
                ->orderBy('dependencia.nombre','asc')
                ->orderBy('programa.nombre','asc')
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->get(); 
            }elseif ($resolucion->tipo_emision=='D') {
                $tramites=Tramite::select( 'tramite.nro_tramite','tramite.nro_matricula', 'tramite_detalle.autoridad2', 'unidad.descripcion as unidad','programa.nombre as programa',
                'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'tramite.idEstado_tramite')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('resolucion','resolucion.idResolucion','tramite_detalle.idResolucion_rectoral')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('unidad','unidad.idUnidad','tramite.idUnidad')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
                ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
                ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
                ->where('tramite_detalle.autoridad1',$idUsuario)
                ->where('resolucion.idResolucion',$request->idResolucion)
                ->where(function($query)
                {
                    $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                    ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
                })
                ->orderBy('tramite.idTipo_tramite_unidad','asc')
                ->orderBy('dependencia.nombre','asc')
                ->orderBy('programa.nombre','asc')
                ->orderBy('usuario.apellidos','asc')
                ->orderBy('usuario.nombres','asc')
                ->get();  
            }
            
            DB::commit();
            set_time_limit(60);

            return response()->json($tramites,200);
        } catch (\Exception $e) {
            DB::rollback();
            set_time_limit(60);
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function GetCarpetasPendientesImpresion(Request $request,$idResolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        $resolucion=Resolucion::find($idResolucion);

        if ($resolucion->tipo_emision=='O') {
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.idEstado_tramite', 'tramite.nro_tramite','tramite.nro_matricula', 'tramite.exonerado_archivo', 
            'tramite.idTipo_tramite_unidad', 'tramite_detalle.codigo_diploma', 'tramite_detalle.diploma_final',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 
            'resolucion.idResolucion','tramite_detalle.observacion_diploma')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->where('tramite.idEstado_tramite',44)
            ->where('tipo_tramite_unidad.idTipo_tramite',2)
            ->where(function($query) use ($request)
            {
                if ($request->query('tramite') != 0) {
                    $query->Where('tipo_tramite_unidad.idTipo_tramite_unidad',$request->query('tramite'));
                }
                else{
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                }
    
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->get();
        }elseif($resolucion->tipo_emision=='D') {
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.idEstado_tramite', 'tramite.nro_tramite','tramite.nro_matricula', 'tramite.exonerado_archivo', 
            'tramite.idTipo_tramite_unidad', 'tramite_detalle.codigo_diploma', 'tramite_detalle.diploma_final',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 
            'resolucion.idResolucion','tramite_detalle.observacion_diploma')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('resolucion', 'resolucion.idResolucion', 'tramite_detalle.idResolucion_rectoral')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->where('tramite.idEstado_tramite',44)
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
            })
            ->where(function($query) use ($request)
            {
                // if ($request->query('tramite') != 0) {
                //     $query->Where('tipo_tramite_unidad.idTipo_tramite_unidad',$request->query('tramite'));
                // }
                if ($request->query('tramite')!=0) {
                    if($request->query('tramite')==15){
                        $query ->whereIn('tramite.idTipo_tramite_unidad',[42,47]);
                    }elseif ($request->query('tramite')==16) {
                        $query ->whereIn('tramite.idTipo_tramite_unidad',[43,48]);
                    }elseif ($request->query('tramite')==34) {
                        $query ->whereIn('tramite.idTipo_tramite_unidad',[44,49]);
                    }
                }
                // else{
                //     $query->where('tramite.idTipo_tramite_unidad',42)
                //     ->orWhere('tramite.idTipo_tramite_unidad',43)
                //     ->orWhere('tramite.idTipo_tramite_unidad',44)
                //     ->orWhere('tramite.idTipo_tramite_unidad',47)
                //     ->orWhere('tramite.idTipo_tramite_unidad',48)
                //     ->orWhere('tramite.idTipo_tramite_unidad',49);
                // }
    
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->get();
        }


        foreach ($tramites as $key => $tramite) {
            $tramite->historial = Historial_Codigo_Diploma::where('idTramite',$tramite->idTramite)->where('estado',1)->get();
        }
        
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
        $begin = ($pagination->currentPage()-1)*$pagination->perPage();
        $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
        return response()->json(['status' => '200','resolucion'=>$resolucion, 'data' =>array_values($pagination->items()),"pagination"=>[
            'length'    => $pagination->total(),
            'size'      => $pagination->perPage(),
            'page'      => $pagination->currentPage()-1,
            'lastPage'  => $pagination->lastPage()-1,
            'startIndex'=> $begin,
            'endIndex'  => $end - 1
        ]], 200);
    }

    public function Paginacion($items, $size, $page = null, $options = [])
    {
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return $response=new LengthAwarePaginator($items->forPage($page, $size), $items->count(), $size, $page, $options);
    }

    public function finalizarCarpetas(Request $request){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            // Obteniendo la resolución que se va a finalizar
            $resolucion=Resolucion::find($request->idResolucion);
            
            //Obteniendo los cronogramas que se van a finalizar
            $cronogramas=Cronograma::where('idResolucion',$resolucion->idResolucion)->get();
            foreach ($cronogramas as $cronograma) {
                if ($cronograma->visible) {
                    $cronograma->visible=false;
                    $cronograma->save();
                    // buscando el cronograma más cercano para activarlo
                    $cronogramasSig=Cronograma::where('idDependencia',$cronograma->idDependencia)
                    ->where('idTipo_tramite_unidad',$cronograma->idTipo_tramite_unidad)
                    ->where('fecha_colacion','>',$cronograma->fecha_colacion)
                    ->orderBy('fecha_colacion') //Si no se pone el order, traerá todos las colaciones mayores, pero la más próxima será por id y no por fecha de colación.   
                    ->first();
                    if ($cronogramasSig) {
                        $cronogramasSig->visible=true;
                        $cronogramasSig->save();
                    }
                }
            }

            if ($resolucion->tipo_emision=='O') {
                // Obteniendo todas las carpetas que se van a finalizar
                $tramites=Tramite::select('tramite.idTramite','tramite.idEstado_tramite')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
                ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
                ->where('tramite.idEstado_tramite',44)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',15)
                    ->orWhere('tramite.idTipo_tramite_unidad',16)
                    ->orWhere('tramite.idTipo_tramite_unidad',34);
                })
                ->where('resolucion.idResolucion',$resolucion->idResolucion)
                ->get();
            }else {
                // Obteniendo todas las carpetas que se van a finalizar
                $tramites=Tramite::select('tramite.idTramite','tramite.idEstado_tramite')
                ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
                ->join('resolucion', 'resolucion.idResolucion', 'tramite_detalle.idResolucion_rectoral')
                ->where('tramite.idEstado_tramite',44)
                ->where(function($query)
                {
                    $query->where('tramite.idTipo_tramite_unidad',42)
                    ->orWhere('tramite.idTipo_tramite_unidad',43)
                    ->orWhere('tramite.idTipo_tramite_unidad',44)
                    ->orWhere('tramite.idTipo_tramite_unidad',47)
                    ->orWhere('tramite.idTipo_tramite_unidad',48)
                    ->orWhere('tramite.idTipo_tramite_unidad',49);
                })
                ->where('resolucion.idResolucion',$resolucion->idResolucion)
                ->get();
            }

            foreach ($tramites as $tramite) {
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 15, $idUsuario);
                $historial_estado->save();
                $tramite->idEstado_tramite=15;
                $tramite->save();
            }

            DB::commit();
            return response()->json($tramites, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function getFinalizados(Request $request,$idResolucion){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia=$apy['idDependencia'];

        $resolucion=Resolucion::find($idResolucion);

        if ($resolucion->tipo_emision=='O') {
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.idEstado_tramite', 'tramite.nro_tramite','tramite.nro_matricula', 'tramite.exonerado_archivo', 
            'tramite.idTipo_tramite_unidad', 'tramite_detalle.codigo_diploma', 'tramite_detalle.diploma_final',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 
            'resolucion.idResolucion','tramite_detalle.*','tramite.sede','tramite.created_at as fecha','usuario.nro_documento','tramite.uuid',
            'voucher.archivo as voucher','tipo_tramite_unidad.idTipo_tramite')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idEstado_tramite',15)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();

            $total=Tramite::join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
            ->join('resolucion', 'resolucion.idResolucion', 'cronograma_carpeta.idResolucion')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->where('tramite.idEstado_tramite',15)
            ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->count();
        }else {
            $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.idEstado_tramite', 'tramite.nro_tramite','tramite.nro_matricula', 'tramite.exonerado_archivo', 
            'tramite.idTipo_tramite_unidad', 'tramite_detalle.codigo_diploma', 'tramite_detalle.diploma_final',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite', DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 
            'resolucion.idResolucion','tramite_detalle.*','tramite.sede','tramite.created_at as fecha','usuario.nro_documento','tramite.uuid',
            'voucher.archivo as voucher','tipo_tramite_unidad.idTipo_tramite')
            ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('resolucion', 'resolucion.idResolucion', 'tramite_detalle.idResolucion_rectoral')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idEstado_tramite',15)
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->orderBy('tramite.idTipo_tramite_unidad','asc')
            ->orderBy('dependencia.nombre','asc')
            ->orderBy('programa.nombre','asc')
            ->orderBy('usuario.apellidos','asc')
            ->orderBy('usuario.nombres','asc')
            ->take($request->query('size'))
            ->skip($request->query('page')*$request->query('size'))
            ->get();

            $total=Tramite::join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
            ->join('resolucion', 'resolucion.idResolucion', 'tramite_detalle.idResolucion_rectoral')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->where('tramite.idEstado_tramite',15)
            ->where(function($query)
            {
                $query->where('tipo_tramite_unidad.idTipo_tramite',6)
                ->orWhere('tipo_tramite_unidad.idTipo_tramite',9);
            })
            ->where('resolucion.idResolucion',$resolucion->idResolucion)
            ->where(function($query) use ($request)
            {
                $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
                ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
                ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
                ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
                ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
                ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
            })
            ->count();
        }
        
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.idRequisito','requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();

            $tramite->fut="fut/".$tramite->uuid;
        }

        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200', 'data' =>$tramites,'resolucion' =>$resolucion,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);
    }

    public function getDataPersona(Request $request){
        $tramite=Tramite::select('tramite.idTramite','usuario.nombres','usuario.apellidos','usuario.nro_documento','tramite.sede','tipo_tramite_unidad.diploma_obtenido',
        'modalidad_carpeta.acto_academico as modalidadSustentancion','tramite_detalle.nro_libro','tramite_detalle.folio','tramite_detalle.nro_registro','resolucion.nro_resolucion',
        DB::raw("(case 
                    when tramite.idUnidad = 1 then dependencia.denominacion  
                    when tramite.idUnidad = 4 then  (select denominacion from dependencia d where d.idDependencia=dependencia.idDependencia2)
                end) as facultad"),
        'programa.nombre as programa', 'tramite.nro_matricula', 'diploma_carpeta.descripcion as denominacion', 'tramite_detalle.codigo_diploma','cronograma_carpeta.fecha_colacion')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa','programa.idPrograma','tramite.idPrograma')
        ->join('tramite_detalle', 'tramite_detalle.idTramite_detalle', 'tramite.idTramite_detalle')
        ->join('diploma_carpeta', 'tramite_detalle.idDiploma_carpeta', 'diploma_carpeta.idDiploma_carpeta')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite_detalle.idTipo_tramite_unidad')
        ->join('modalidad_carpeta','modalidad_carpeta.idModalidad_carpeta','tramite_detalle.idModalidad_carpeta')
        ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
        ->where('tramite.idTramite', $request->id)
        ->first();

        if (!$tramite) return response()->json(['status' => '400', 'message' => "No se encuentra carpeta con ese código de diploma"], 400);

        $requisito=Tramite_Requisito::select('tramite_requisito.archivo')
        ->where(function($query){
            $query->where('tramite_requisito.idRequisito',15)
            ->orWhere('tramite_requisito.idRequisito',23)
            ->orWhere('tramite_requisito.idRequisito',44)
            ->orWhere('tramite_requisito.idRequisito',52)
            ->orWhere('tramite_requisito.idRequisito',61);
        })
        ->where('idTramite',$tramite->idTramite)
        ->first();

        $tramite->foto=$requisito->archivo;
        return $tramite;
    }

    public function getCarpetaBySearch(Request $request){
        $diplomas = [];
        $tramites=Tramite::select('tramite.idTramite','usuario.nombres','usuario.apellidos','usuario.nro_documento','tramite.sede',
        'tipo_tramite_unidad.descripcion as tipo_tramite',
        'modalidad_carpeta.acto_academico as modalidadSustentancion','tramite_detalle.nro_libro','tramite_detalle.folio','tramite_detalle.nro_registro','resolucion.nro_resolucion',
        DB::raw("(case 
                    when tramite.idUnidad = 1 then dependencia.denominacion  
                    when tramite.idUnidad = 4 then  (select denominacion from dependencia d where d.idDependencia=dependencia.idDependencia2)
                end) as facultad"),
        'programa.nombre as programa', 'tramite.nro_matricula', 'diploma_carpeta.descripcion as denominacion', 'tramite_detalle.codigo_diploma','cronograma_carpeta.fecha_colacion')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa','programa.idPrograma','tramite.idPrograma')
        ->join('tramite_detalle', 'tramite_detalle.idTramite_detalle', 'tramite.idTramite_detalle')
        ->join('diploma_carpeta', 'tramite_detalle.idDiploma_carpeta', 'diploma_carpeta.idDiploma_carpeta')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('modalidad_carpeta','modalidad_carpeta.idModalidad_carpeta','tramite_detalle.idModalidad_carpeta')
        ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
        ->where('tipo_tramite_unidad.idTipo_tramite', 2)
        ->where(function($query) use ($request)
        {
            if ($request->query('tipo')=="codigo_diploma") {
                $query->where('tramite_detalle.codigo_diploma', 'LIKE', $request->query('search'));
            } else if ($request->query('tipo')=="nro_documento") {
                $query->where('usuario.nro_documento', 'LIKE', '%'.$request->query('search').'%');
            } else if ($request->query('tipo')=="apellidos") {
                $query->where('usuario.apellidos', 'LIKE', '%'.$request->query('search').'%');
            } else if ($request->query('tipo')=="nombres") {
                $query->where('usuario.nombres', 'LIKE', '%'.$request->query('search').'%');
            } else {
                return response()->json(['status' => '400', 'message' => "Búsqueda incorrecta"], 400);
            }
        })
        ->get();
        if (count($tramites)) {
            foreach ($tramites as $key => $tramite) {
                $requisito=Tramite_Requisito::select('tramite_requisito.archivo')
                ->where(function($query) {
                    $query->where('tramite_requisito.idRequisito',15)
                    ->orWhere('tramite_requisito.idRequisito',23)
                    ->orWhere('tramite_requisito.idRequisito',44)
                    ->orWhere('tramite_requisito.idRequisito',52)
                    ->orWhere('tramite_requisito.idRequisito',61);
                })
                ->where('idTramite',$tramite->idTramite)
                ->first();
                $tramite->foto=$requisito->archivo;
                array_push($diplomas, $tramite);
            }
        }

        $tramites_diploma = Graduado::select('graduado.idgraduado as idTramite', 'alumno.Nom_alumno as nombres', DB::raw("CONCAT(alumno.Pat_alumno,' ',alumno.Mat_alumno) AS apellidos"), 
        'alumno.Nro_documento as nro_documento', 'sedes.Des_sede as sede', 'tipoficha.Nom_ficha as tipo_tramite', 'actoacad.Nom_acto as modalidadSustentancion',
        'graduado.num_libro as nro_libro', 'graduado.num_folio as folio', 'graduado.num_registro as nro_registro', 'graduado.num_reso_r as nro_resolucion',
        'facultad.Nom_facultad as facultad', 'escuela.Nom_escuela as programa', 'graduado.cod_alumno as nro_matricula', 
        'diplomas.Des_diploma_h as denominacion', // Corregir para diplomas masculino y femenino
        'graduado.cod_ficha as codigo_diploma',
        'graduado.fec_expe_d as fecha_colacion', //Validar si es fecha de colación
        //Detectar cuando es original y duplicado
        'graduado.grad_foto as foto' //Configurar para leer fotos
        )
        
        ->join('alumno', 'alumno.Cod_alumno', 'graduado.cod_alumno')
        ->join('sedes', 'sedes.Cod_general', 'alumno.Cod_sede')
        ->join('tipoficha','tipoficha.Tip_ficha','graduado.tipo_ficha')
        ->join('actoacad','actoacad.Cod_acto','graduado.cod_acto')
        ->join('escuela','escuela.Cod_escuela','alumno.Cod_escuela')
        ->join('facultad','facultad.Cod_facultad','escuela.Cod_facultad')
        ->join('diplomas', 'diplomas.Cod_diploma', 'graduado.Cod_diploma')
        ->where(function($query) use ($request)
        {
            if ($request->query('tipo')=="codigo_diploma") {
                $query->where('graduado.cod_ficha', 'LIKE', $request->query('search'));
            } else if ($request->query('tipo')=="nro_documento") {
                $query->where('alumno.Nro_documento', 'LIKE', '%'.$request->query('search').'%');
            } else if ($request->query('tipo')=="apellidos") {
                $query->where('alumno.Pat_alumno', 'LIKE', '%'.$request->query('search').'%')
                ->orWhere('alumno.Mat_alumno', 'LIKE', '%'.$request->query('search').'%');
            } else if ($request->query('tipo')=="nombres") {
                $query->where('alumno.Nom_alumno', 'LIKE', '%'.$request->query('search').'%');
            } else {
                return response()->json(['status' => '400', 'message' => "Búsqueda incorrecta"], 400);
            }
        })
        ->get();
        if (count($tramites_diploma)) {
            foreach ($tramites_diploma as $key => $tramite) {
                array_push($diplomas, $tramite);
            }
        }

        if (!$diplomas) return response()->json(['status' => '400', 'message' => "No se encuentra carpeta con esa búsqueda"], 400);

        return $diplomas;
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
}
