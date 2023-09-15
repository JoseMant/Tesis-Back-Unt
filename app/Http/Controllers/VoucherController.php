<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Tymon\JWTAuth\Facades\JWTAuth;
use Maatwebsite\Excel\Facades\Excel;
use App\Voucher;
use App\Tramite;
use App\Tipo_Tramite_Unidad;
use App\Tipo_Tramite;
use App\User;
use App\Historial_Estado;
use App\Tramite_Requisito;
use App\Tramite_Detalle;
use App\Exports\ReporteTesoreriaExport;
use App\Jobs\ActualizacionTramiteJob;
use App\Usuario_Programa;
use App\PersonaSuv;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt',['except' => ['reporteTesoreria']]);
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
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            // Obtenemos el voucher a validar y actualizamos los datos
            $voucher = Voucher::findOrFail($id);
            $tramite=Tramite::Where('idVoucher',$voucher->idVoucher)->first();
            $usuario=User::where('idUsuario',$tramite->idUsuario)->first();
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)->first();
            $tipo_tramite=Tipo_Tramite::where('idTipo_tramite',$tipo_tramite_unidad->idTipo_tramite)->first();
            
            // modificamos el estado del trámite
            $voucher->des_estado_voucher=strtoupper($request->des_estado_voucher);
            if ($voucher->des_estado_voucher=="APROBADO") {
                $voucher->validado=1;
            }
            $voucher->idUsuario_aprobador=$idUsuario;
            $voucher->comentario=trim($request->comentario);
            $voucher->update();

            //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
            if ($voucher->des_estado_voucher=="APROBADO") {
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 3, $idUsuario);
                $historial_estado->save();
                
                if ($tipo_tramite->idTipo_tramite==1 || $tipo_tramite->idTipo_tramite==4) {
                    if ($tipo_tramite->idTipo_tramite==1) {
                        $tramite->idUsuario_asignado=88;
                    }
                    //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                    $historial_estado = $this->setHistorialEstado($tramite->idTramite, 3, 5, $idUsuario);
                    $historial_estado->save();
                } elseif ($tipo_tramite->idTipo_tramite==2) {
                    // SI EL TRÁMITE ES DE GRADO o TITULO, SE ASIGNA AUTOMÁTICAMENTE UN USUARIO
                    if ($tramite->idTipo_tramite_unidad==15 || $tramite->idTipo_tramite_unidad==35 || $tramite->idTipo_tramite_unidad==36) {
                        $tramite->idUsuario_asignado=67;
                    } else {
                        $tramite->idUsuario_asignado=68;
                    }

                    //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                    $historial_estado = $this->setHistorialEstado($tramite->idTramite, 3, 17, $idUsuario);
                    $historial_estado->save();

                    // VERIFICANDO SI ES DE UNA UNIVERSIDAD NO LICENCIADA(10)
                    $noLicenciado=false;
                    $alumnoSUV=PersonaSuv::join('matriculas.alumno','matriculas.alumno.idpersona','persona.idpersona')
                    ->where(function($query) {
                        $query->where('idmodalidadingreso',10);
                    })
                    ->Where('per_dni',$usuario->nro_documento)
                    ->first();
                    if ($alumnoSUV) {
                        $noLicenciado=true;
                    }

                    // REGISTRAMOS EL CERTIFICADO EN PARALELO
                    if ($tramite->idTipo_tramite_unidad==15 || $tramite->idTipo_tramite_unidad==34 
                    || ($tramite->idTipo_tramite_unidad==16 && $noLicenciado==false && ($tramite->idPrograma==11 || $tramite->idPrograma==47))) {
                        $tramiteCertificado=new Tramite;
                        $tramiteCertificado->nro_tramite=$tramite->nro_tramite;
                        // REGISTRAMOS EL TRÁMITE
                        $tramiteCertificado->idTramite_detalle = $tramite->idTramite_detalle;
                        
                        $tramiteCertificado->idTipo_tramite_unidad = 37;
                        $tramiteCertificado->idVoucher = $tramite->idVoucher;
                        $tramiteCertificado->idUsuario = $tramite->idUsuario;
                        $tramiteCertificado->idUnidad = $tramite->idUnidad;
                        $tramiteCertificado->idDependencia = $tramite->idDependencia;
                        $tramiteCertificado->idPrograma = $tramite->idPrograma;
                        $tramiteCertificado->nro_matricula = $tramite->nro_matricula;
                        $tramiteCertificado->comentario = "CERTIFICADO PARA SOLICITUD DE ".$tipo_tramite_unidad->descripcion;
                        $tramiteCertificado->sede = $tramite->sede;
                        $tramiteCertificado->exonerado_archivo = $tramite->exonerado_archivo;
                        $tramiteCertificado->idUsuario_asignado = null;
                        $tramiteCertificado->idEstado_tramite = 5;
                        $tramiteCertificado->firma_tramite = $tramite->firma_tramite;
                        // Creando un uudi para realizar el llamado a los trámites por ruta

                        // Verificando que no haya un uuid ya guardado en bd
                        $tramiteUUID=true;
                        while ($tramiteUUID) {
                            $uuid=Str::orderedUuid();
                            $tramiteUUID=Tramite::where('uuid',$uuid)->first();
                        }
                        $tramiteCertificado -> uuid=$uuid;
                    
                        // ---------------------------------------------------
                        $tramiteCertificado->save();
                        
                        //
                        $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                        $tramite_detalle->idMotivo_certificado=1;
                        $tramite_detalle->update();

                        // obtenemos el requisito de la foto pasaporte para el certificado paralelo
                        $requisito_foto=Tramite_Requisito::where('idTramite',$tramite->idTramite)
                        ->where(function($query) use ($request)
                        {
                            $query->where('idRequisito',15)
                            ->orWhere('idRequisito',23)
                            ->orWhere('idRequisito',61);
                        })->first();

                        //agregamos ese mismo requisito como parte del certificado paralelo
                        $tramite_requisito=new Tramite_Requisito;
                        $tramite_requisito->idTramite = $tramiteCertificado->idTramite;
                        $tramite_requisito->idRequisito = $requisito_foto->idRequisito;
                        $tramite_requisito->archivo = $requisito_foto->archivo;
                        $tramite_requisito->save();

                        //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                        $historial_estado_certificado = $this->setHistorialEstado($tramiteCertificado->idTramite,null, 1, $idUsuario);
                        $historial_estado_certificado->save();
    
                        //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                        $historial_estado_certificado = $this->setHistorialEstado($tramiteCertificado->idTramite,1, 2, $idUsuario);
                        $historial_estado_certificado->save();

                        //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                        $historial_estado_certificado = $this->setHistorialEstado($tramiteCertificado->idTramite,2, 5, $idUsuario);
                        $historial_estado_certificado->save();
                    }
                } elseif ($tipo_tramite->idTipo_tramite==3) {
                    // SI EL TRÁMITE ES DE CARNET, SE ASIGNA AUTOMÁTICAMENTE UN USUARIO
                    $tramite->idUsuario_asignado=2;
                    //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                    if ($tramite->idTipo_tramite_unidad==30||$tramite->idTipo_tramite_unidad==31||$tramite->idTipo_tramite_unidad==32||$tramite->idTipo_tramite_unidad==33) {
                        $historial_estado = $this->setHistorialEstado($tramite->idTramite, 3, 25, $idUsuario);
                    }
                    else {
                        $historial_estado = $this->setHistorialEstado($tramite->idTramite, 3, 7, $idUsuario);
                    }
                    $historial_estado->save();
                    $tramite->idEstado_tramite = $historial_estado->idEstado_nuevo;
                    $tramite->update();
                } elseif ($tipo_tramite->idTipo_tramite==5){
                    //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                    $historial_estado = $this->setHistorialEstado($tramite->idTramite, 3, 42, $idUsuario);
                    $historial_estado->save();
                }
            } elseif ($voucher->des_estado_voucher=="RECHAZADO") {
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, $tramite->idEstado_tramite, 4, $idUsuario);
                $historial_estado->save();
            }
            $tramite->idEstado_tramite=$historial_estado->idEstado_nuevo;
            $tramite->update();
            DB::commit();
            return response()->json(['status' => '200', 'message' => "Estado de voucher actualizado"], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function Pendientes(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idTipo_usuario=$apy['idTipo_usuario'];
        $idDependencia=$apy['idDependencia'];        
        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        $tramites=Tramite::select('tramite.idTramite','tramite.nro_tramite','tramite.idUnidad','tramite.idPrograma',
        'tramite.nro_matricula', 'tramite.exonerado_archivo',
        'programa.nombre as programa',
        DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'),
        DB::raw("(case 
                    when tramite.idTipo_tramite_unidad = 34 then (case
                                                                        when tramite.idDependencia=17 then 624.20
                                                                        when tramite.idDependencia=18 then 450
                                                                        when tramite.idDependencia=19 then 250
                                                                        when tramite.idDependencia=20 then 200
                                                                        when tramite.idDependencia=21 then 1000
                                                                        when tramite.idDependencia=22 then 250
                                                                        when tramite.idDependencia=23 then 1500
                                                                  end)
                    else tipo_tramite_unidad.costo
                end) AS costo"),
        'usuario.nro_documento', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno'), 
        'voucher.idVoucher', 'voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.archivo','voucher.comentario')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite','tipo_tramite.descripcion')
        ->where('tramite.idEstado_tramite',2)
        ->where('tramite.estado',1)
        ->where(function($query) use ($request) {
            $query->where('programa.nombre','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.costo','LIKE', '%'.$request->query('search').'%')
            ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('entidad','LIKE','%'.$request->query('search').'%')
            ->orWhere('nro_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('fecha_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE','%'.$request->query('search').'%');
        })
        ->where(function($query) use ($idTipo_usuario, $usuario_programas, $idDependencia) {
            if ($idTipo_usuario==3) {
                $query->where('voucher.entidad','!=','TESORERÍA');
            }elseif($idTipo_usuario==5){
                $query->where('voucher.entidad','TESORERÍA')
                ->whereIn('tramite.idPrograma',$usuario_programas);
            }elseif($idTipo_usuario==17){
                $query->where('voucher.entidad','TESORERÍA')
                ->where('tramite.idDependencia',$idDependencia);
            }
        })
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite','tipo_tramite.descripcion')
        ->where('tramite.idEstado_tramite',2)
        ->where('tramite.estado',1)
        ->where(function($query) use ($request) {
            $query->where('programa.nombre','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.costo','LIKE', '%'.$request->query('search').'%')
            ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('entidad','LIKE','%'.$request->query('search').'%')
            ->orWhere('nro_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('fecha_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE','%'.$request->query('search').'%');
        })
        ->where(function($query) use ($idTipo_usuario, $usuario_programas, $idDependencia) {
            if ($idTipo_usuario==3) {
                $query->where('voucher.entidad','!=','TESORERÍA');
            }elseif($idTipo_usuario==5){
                $query->where('voucher.entidad','TESORERÍA')
                ->whereIn('tramite.idPrograma',$usuario_programas);
            }elseif($idTipo_usuario==17){
                $query->where('voucher.entidad','TESORERÍA')
                ->where('tramite.idDependencia',$idDependencia);
            }
        })
        ->count();

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

    public function Aprobados(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idTipo_usuario=$apy['idTipo_usuario'];

        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        $tramites=Voucher::select('tramite.idTramite','tramite.nro_tramite','tramite.idUnidad','tramite.idPrograma',
        'tramite.nro_matricula', 'tramite.exonerado_archivo', DB::raw("(case when tramite.exonerado_archivo is null then 'NO' else 'SI' end) as exonerado"),
        'programa.nombre as programa',
        DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'), 'tipo_tramite_unidad.costo',
        'usuario.nro_documento', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno'), 
        'voucher.idVoucher', 'voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.archivo','voucher.comentario')
        ->join('tramite','tramite.idVoucher','voucher.idVoucher')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite','tipo_tramite.descripcion')
        ->where('des_estado_voucher','APROBADO')
        ->where('tramite.idEstado_tramite','!=',29)
        ->where('tramite.estado',1)
        ->where(function($query) use ($request) {
            $query->where('programa.nombre','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.costo','LIKE', '%'.$request->query('search').'%')
            ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('entidad','LIKE','%'.$request->query('search').'%')
            ->orWhere('nro_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('fecha_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE','%'.$request->query('search').'%');
        })
        ->where(function($query) use ($idTipo_usuario,$usuario_programas) {
            if($idTipo_usuario==5||$idTipo_usuario==17){
                $query->where('voucher.entidad','TESORERÍA')
                ->whereIn('tramite.idPrograma',$usuario_programas);
            }
        })
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Voucher::join('tramite','tramite.idVoucher','voucher.idVoucher')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite','tipo_tramite.descripcion')
        ->where('des_estado_voucher','APROBADO')
        ->where('tramite.idEstado_tramite','!=',29)
        ->where('tramite.estado',1)
        ->where(function($query) use ($request) {
            $query->where('programa.nombre','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.costo','LIKE', '%'.$request->query('search').'%')
            ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('entidad','LIKE','%'.$request->query('search').'%')
            ->orWhere('nro_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('fecha_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE','%'.$request->query('search').'%');
        })
        ->where(function($query) use ($idTipo_usuario,$usuario_programas) {
            if($idTipo_usuario==5||$idTipo_usuario==17){
                $query->where('voucher.entidad','TESORERÍA')
                ->whereIn('tramite.idPrograma',$usuario_programas);
            }
        })
        ->count();

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

    public function Rechazados(Request $request){
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idTipo_usuario=$apy['idTipo_usuario'];

        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        $tramites=Tramite::select('tramite.idTramite','tramite.nro_tramite','tramite.idUnidad','tramite.idPrograma',
        'tramite.nro_matricula', 'tramite.exonerado_archivo',
        'programa.nombre as programa',
        DB::raw('CONCAT(tipo_tramite.descripcion,"-",tipo_tramite_unidad.descripcion) as tramite'), 'tipo_tramite_unidad.costo',
        'usuario.nro_documento', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as alumno'), 
        'voucher.idVoucher', 'voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','voucher.archivo','voucher.comentario')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite','tipo_tramite.descripcion')
        ->where('tramite.idEstado_tramite',4)
        ->where('tramite.estado',1)
        ->where(function($query) use ($request) {
            $query->where('programa.nombre','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.costo','LIKE', '%'.$request->query('search').'%')
            ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('entidad','LIKE','%'.$request->query('search').'%')
            ->orWhere('nro_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('fecha_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE','%'.$request->query('search').'%');
        })
        ->where(function($query) use ($idTipo_usuario,$usuario_programas) {
            if($idTipo_usuario==5||$idTipo_usuario==17){
                $query->where('voucher.entidad','TESORERÍA')
                ->whereIn('tramite.idPrograma',$usuario_programas);
            }
        })
        ->orderBy($request->query('sort'), $request->query('order'))
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();

        $total=Tramite::join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite','tipo_tramite.descripcion')
        ->where('tramite.idEstado_tramite',4)
        ->where('tramite.estado',1)
        ->where(function($query) use ($request) {
            $query->where('programa.nombre','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.costo','LIKE', '%'.$request->query('search').'%')
            ->orWhere('nro_tramite','LIKE', '%'.$request->query('search').'%')
            ->orWhere('entidad','LIKE','%'.$request->query('search').'%')
            ->orWhere('nro_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('fecha_operacion','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.nombres','LIKE','%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE','%'.$request->query('search').'%');
        })
        ->where(function($query) use ($idTipo_usuario,$usuario_programas) {
            if($idTipo_usuario==5||$idTipo_usuario==17){
                $query->where('voucher.entidad','TESORERÍA')
                ->whereIn('tramite.idPrograma',$usuario_programas);
            }
        })
        ->count();

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

    public function vouchersAprobados(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idTipo_usuario=$apy['idTipo_usuario'];
        $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');

        $vouchers=Tramite::select('tramite.nro_matricula',
        'programa.nombre as programa',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'usuario.nro_documento',
        DB::raw("(case 
                    when tipo_tramite.idTipo_tramite = 1 then tipo_tramite_unidad.descripcion
                    when tipo_tramite.idTipo_tramite = 2 then tipo_tramite_unidad.descripcion
                    when tipo_tramite.idTipo_tramite = 4 then tipo_tramite_unidad.descripcion
                    else CONCAT(tipo_tramite.descripcion,'-',tipo_tramite_unidad.descripcion)
                end) AS tipo_tramite"),
        // 'tipo_tramite_unidad.descripcion as tipo_tramite',
        'voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion',
        DB::raw("(case 
                    when tramite.idTipo_tramite_unidad = 34 then (case
                                                                        when tramite.idDependencia=17 then 624.20
                                                                        when tramite.idDependencia=18 then 450
                                                                        when tramite.idDependencia=19 then 250
                                                                        when tramite.idDependencia=20 then 200
                                                                        when tramite.idDependencia=21 then 1000
                                                                        when tramite.idDependencia=22 then 250
                                                                        when tramite.idDependencia=23 then 1500
                                                                  end)
                    else tipo_tramite_unidad.costo
                end) AS costo")
        // 'tipo_tramite_unidad.costo'
        
        )
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('usuario','tramite.idUsuario','usuario.idUsuario')
        ->join('tipo_tramite_unidad','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->where('voucher.des_estado_voucher','APROBADO')
        ->where(function($query) use ($request)
        {
            if($request->fecha_inicio){
                $query->where('voucher.fecha_operacion','>=',$request->fecha_inicio);
            }
            if($request->fecha_fin){
                $query->where('voucher.fecha_operacion','<=',$request->fecha_fin);
            }
        })
        ->where(function($query) use ($idTipo_usuario,$usuario_programas) {
            if ($idTipo_usuario==3) {
                $query->where('voucher.entidad','!=','TESORERÍA');
            }elseif($idTipo_usuario==5||$idTipo_usuario==17){
                $query->where('voucher.entidad','TESORERÍA')
                ->whereIn('tramite.idPrograma',$usuario_programas);
            }
        })
        ->where('tramite.idTipo_tramite_unidad','!=',37)
        ->where('tramite.idEstado_tramite','!=',29)
        ->orderBy('fecha_operacion','asc')
        ->orderBy('programa','asc')
        ->orderBy('tipo_tramite','asc')
        ->orderBy('solicitante','asc')
        ->take($request->query('size'))
        ->skip($request->query('page')*$request->query('size'))
        ->get();
        
        $total=Tramite::join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('usuario','tramite.idUsuario','usuario.idUsuario')
        ->join('tipo_tramite_unidad','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->where('voucher.des_estado_voucher','APROBADO')
        ->where(function($query) use ($request)
        {
            if($request->fecha_inicio){
                $query->where('voucher.fecha_operacion','>=',$request->fecha_inicio);
            }
            if($request->fecha_fin){
                $query->where('voucher.fecha_operacion','<=',$request->fecha_fin);
            }
        })
        ->where(function($query) use ($idTipo_usuario,$usuario_programas) {
            if ($idTipo_usuario==3) {
                $query->where('voucher.entidad','!=','TESORERÍA');
            }elseif($idTipo_usuario==5||$idTipo_usuario==17){
                $query->where('voucher.entidad','TESORERÍA')
                ->whereIn('tramite.idPrograma',$usuario_programas);
            }
        })
        ->where('tramite.idTipo_tramite_unidad','!=',37)
        ->where('tramite.idEstado_tramite','!=',29)
        ->count();

        $begin = $request->query('page')*$request->query('size');
        $end = min(($request->query('size') * ($request->query('page')+1)-1), $total);
        return response()->json(['status' => '200', 'data' =>$vouchers,"pagination"=>[
            'length'    => $total,
            'size'      => $request->query('size'),
            'page'      => $request->query('page'),
            'lastPage'  => (int)($total/$request->query('size')),
            'startIndex'=> $begin,
            'endIndex'  => $end
        ]], 200);

    }

    public function reporteTesoreria(Request $request, $fecha_inicio, $fecha_fin){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::setToken($request->access);
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];
            $idTipo_usuario=$apy['idTipo_usuario'];

            $usuario_programas = Usuario_Programa::where('idUsuario', $idUsuario)->pluck('idPrograma');
            $descarga=Excel::download(new ReporteTesoreriaExport($fecha_inicio,$fecha_fin,$idTipo_usuario,$usuario_programas), 'Reporte_tesoreria.xlsx');
            return $descarga;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function Paginacion($items, $size, $page = null, $options = [])
    {
        // $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return $response=new LengthAwarePaginator($items->forPage($page, $size), $items->count(), $size, $page, $options);
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
