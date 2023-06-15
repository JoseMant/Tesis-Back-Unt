<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Mail\Mailable;
use App\Tramite;
use App\Tipo_Tramite;
use App\Tipo_Tramite_Unidad;
use App\Historial_Estado;
use App\Tramite_Requisito;
use App\Voucher;
use App\User;
use App\Tramite_Detalle;
use App\Estado_Tramite;
use App\Jobs\RegistroTramiteJob;
use App\Jobs\EnvioCertificadoJob;
use App\Jobs\NotificacionDecanatoJob;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Response;
use App\PersonaSE;

use App\Mencion;
use App\Escuela;
use App\PersonaSuv;
use App\Motivo_Certificado;
class CertificadoController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['downloadFoto']]);
    }

    public function GetCertificadosValidados(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'voucher.archivo as voucher')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idEstado_tramite',5)
            ->where('tipo_tramite_unidad.idTipo_tramite',1)
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
            ->where('tramite.estado',1)
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            //Requisitos
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);

    }
    
    public function GetCertificadosAsignados(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idTipo_usuario=$apy['idTipo_usuario'];
        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'voucher.archivo as voucher')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idEstado_tramite',7)
            ->where('tipo_tramite_unidad.idTipo_tramite',1)
            ->where(function($query) use($idUsuario,$idTipo_usuario){
                if ($idTipo_usuario==2) $query->where('tramite.idUsuario_asignado',$idUsuario);
            })
            ->where(function($query) use ($request)
            {
                if ($request->query('dependencia') != 0) {
                    $query->Where('dependencia.idDependencia',$request->query('dependencia'));
                }
            })
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
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetCertificadosAprobados(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idTipo_usuario=$apy['idTipo_usuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
            'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'voucher.archivo as voucher')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->where('tramite.idEstado_tramite',8)
            ->where('tipo_tramite_unidad.idTipo_tramite',1)
            ->where(function($query) use($idUsuario,$idTipo_usuario){
                if ($idTipo_usuario==2) $query->where('tramite.idUsuario_asignado',$idUsuario);
            })
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
            ->orderBy($request->query('sort'), $request->query('order'))
            ->get();
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }
    public function uploadCertificado(Request $request, $id){
        DB::beginTransaction();
        try {
            // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
            $token = JWTAuth::getToken();
            $apy = JWTAuth::getPayload($token);
            $idUsuario=$apy['idUsuario'];

            $tramite=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma',
            'tramite.idEstado_tramite', 'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula',
            'tramite.exonerado_archivo', 'tramite.idTramite_detalle', 'tramite.idTipo_tramite_unidad',
            'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
            'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
            DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
            'voucher.archivo as voucher')
            ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
            ->join('unidad','unidad.idUnidad','tramite.idUnidad')
            ->join('usuario','usuario.idUsuario','tramite.idUsuario')
            ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
            ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
            ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
            ->join('voucher','tramite.idVoucher','voucher.idVoucher')
            ->Find($id); 
            // Datos de correo
            $tipo_tramite_unidad=Tipo_Tramite_Unidad::Where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)->first();
            $tipo_tramite = Tipo_Tramite::select('tipo_tramite.idTipo_tramite','tipo_tramite.descripcion')
            ->join('tipo_tramite_unidad', 'tipo_tramite_unidad.idTipo_tramite', 'tipo_tramite.idTipo_tramite')
            ->where('tipo_tramite_unidad.idTipo_tramite_unidad', $tramite->idTipo_tramite_unidad)->first();
            $usuario = User::findOrFail($tramite->idUsuario);
            $tramite_detalle=Tramite_detalle::find($tramite->idTramite_detalle);
            if($request->hasFile("archivo")){
                $file=$request->file("archivo");
                if ($tramite->idEstado_tramite==8) {
                    $nombre = $tramite->nro_tramite.'.'.$file->guessExtension();
                    $nombreBD = "/storage/certificados/".$nombre;
                    if($file->guessExtension()=="pdf"){
                        $file->storeAs('public/certificados', $nombre);
                        $tramite_detalle->certificado_final = $nombreBD;
                    }
                } else {
                    if ($tramite->nro_tramite."_firmado.pdf"==$file->getClientOriginalName()) {
                        $nombre = $tramite->nro_tramite.'.'.$file->guessExtension();
                        $nombreBD = "/storage/certificados/".$nombre;
                        if($file->guessExtension()=="pdf"){
                            $file->storeAs('public/certificados', $nombre);
                            $tramite_detalle->certificado_final = $nombreBD;
                        }
                    } else {
                        return response()->json(['status' => '400', 'message' =>"El Documento no es el correcto"], 400);
                    }
                }
            } else {
                DB::rollback();
                return response()->json(['status' => '400', 'message' =>"Adjuntar el certificado."], 400);
            }
            $tramite_detalle->update();
            
            if ($tramite->idEstado_tramite==8) {
                $tramite->idEstado_tramite=11;
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 8, 10, $idUsuario);
                $historial_estado->save();
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 10, 11, $idUsuario);
                $historial_estado->save();
            }elseif ($tramite->idEstado_tramite==11) {
                $tramite->idEstado_tramite=13;
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 11, 12, $idUsuario);
                $historial_estado->save();
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 12, 13, $idUsuario);
                $historial_estado->save();
                
                /*---------------------- ENVIAR CORREO A DECANATO -------------*/
                // Datos de correo
                $decano=User::join('dependencia','usuario.idDependencia','dependencia.idDependencia')->where('idTipo_usuario',6)
                ->where('usuario.idDependencia',$tramite->idDependencia)->first();
                if (!$decano) {
                    $decano=User::join('dependencia','usuario.idDependencia','dependencia.idDependencia2')->where('idTipo_usuario',6)
                    ->where('dependencia.idDependencia',$tramite->idDependencia)->first();
                }
                // dispatch(new NotificacionDecanatoJob($decano,$tramite,$tipo_tramite,$tipo_tramite_unidad));
            }elseif ($tramite->idEstado_tramite==13) {
                $tramite->idEstado_tramite=15;
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 13, 14, $idUsuario);
                $historial_estado->save();
                //REGISTRAMOS EL ESTADO DEL TRÁMITE REGISTRADO
                $historial_estado = $this->setHistorialEstado($tramite->idTramite, 14, 15, $idUsuario);
                $historial_estado->save();
            }
            $tramite->update();
            $tramite->certificado_final=$tramite_detalle->certificado_final;
            $tramite->fut="fut/".$tramite->idTramite;
            //Requisitos
            $tramite->requisitos=Tramite_Requisito::select('*')
            ->join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
            ->where('tramite_requisito.idTramite',$tramite->idTramite)
            ->get();
            
            if ($tramite->idEstado_tramite==15 && $tramite->idTipo_tramite_unidad!=37) {
                $ruta=public_path().$tramite->certificado_final;
                dispatch(new EnvioCertificadoJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad,$ruta));
            }
            DB::commit();
            return response()->json($tramite, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }
    }

    public function GetCertificadosFirmaUraa(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'tramite_detalle.certificado_final',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher')
        ->join('tramite_detalle', 'tramite_detalle.idTramite_detalle', 'tramite.idTramite_detalle')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',11)
        ->where('tipo_tramite_unidad.idTipo_tramite',1)
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
        ->orderBy($request->query('sort'), $request->query('order'))
        ->get();
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetCertificadosFirmaDecano(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia_usuario=$apy['idDependencia'];
        
        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo','motivo_certificado.nombre as motivo',
        'tramite_detalle.certificado_final',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher')
        ->join('tramite_detalle', 'tramite_detalle.idTramite_detalle', 'tramite.idTramite_detalle')
        ->join('motivo_certificado','motivo_certificado.idMotivo_certificado','tramite_detalle.idMotivo_certificado')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',13)
        ->where('tipo_tramite_unidad.idTipo_tramite',1)
        ->where(function($query) use ($idDependencia_usuario)
        {
            if ($idDependencia_usuario!=null) {
                $query->where('tramite.idDependencia',$idDependencia_usuario)
                ->orWhere('dependencia.idDependencia2',$idDependencia_usuario);
            }
        })
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
        ->orderBy($request->query('sort'), $request->query('order'))
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }

        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()-1), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetCertificadosPendientes(Request $request){
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        DB::raw('CONCAT(asignado.nombres," ",asignado.apellidos) as responsable'),
        'voucher.archivo as voucher')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('usuario as asignado','asignado.idUsuario','tramite.idUsuario_asignado')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tipo_tramite_unidad.idTipo_tramite',1)
        ->where(function($query)
        {
            $query->where('tramite.idEstado_tramite',5)
            ->orWhere('tramite.idEstado_tramite',7)
            ->orWhere('tramite.idEstado_tramite',8);
        })
        ->where(function($query) use ($request)
        {
            $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('asignado.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('asignado.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->orderBy($request->query('sort'), $request->query('order'))
        ->get();

        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            // -------------------------------------------------
            // OBTENER LA CANTIDAD DE DÍAS QUE LLEVA ASIGNADO CADA TRÁMITE
            $hoy=date('y-m-d h:i:s a');
            $fecha_cambio_estado=Historial_Estado::select('fecha')->where('idTramite',$tramite->idTramite)
            ->where('idEstado_nuevo',$tramite->idEstado_tramite)
            ->latest('fecha')->first();
            echo $tramite->idTramite."/";
            $tramite->fecha_cambio_estado=$fecha_cambio_estado->fecha;

            $d1 = date_create($hoy);
            $d2 = date_create($tramite->fecha_cambio_estado);

            $diferencia=$d1->diff($d2);
            $tramite->tiempo=$diferencia->d;
        }

        $tramites=$tramites->where('tiempo','>',3);

        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
        $begin = ($pagination->currentPage()-1)*$pagination->perPage();
        $end = min(($pagination->perPage() * $pagination->currentPage()), $pagination->total());
        return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
            'length'    => $pagination->total(),
            'size'      => $pagination->perPage(),
            'page'      => $pagination->currentPage()-1,
            'lastPage'  => $pagination->lastPage()-1,
            'startIndex'=> $begin,
            'endIndex'  => $end - 1
        ]], 200);
    }

    public function GetCertificadosReasignados(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        $idDependencia_usuario=$apy['idDependencia'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        DB::raw('CONCAT(asignado.nombres," ",asignado.apellidos) as responsable'),
        'voucher.archivo as voucher')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('usuario as asignado','asignado.idUsuario','tramite.idUsuario_asignado')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tipo_tramite_unidad.idTipo_tramite',1)
        ->where('tramite.idUsuario_asignado','!=',null)
        ->where(function($query)
        {
            $query->where('tramite.idEstado_tramite',7)
            ->orWhere('tramite.idEstado_tramite',8);
        })
        ->where(function($query) use ($request)
        {
            $query->where('usuario.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('usuario.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('asignado.nombres','LIKE', '%'.$request->query('search').'%')
            ->orWhere('asignado.apellidos','LIKE', '%'.$request->query('search').'%')
            ->orWhere('unidad.descripcion','LIKE', '%'.$request->query('search').'%')
            ->orWhere('tipo_tramite_unidad.descripcion','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_tramite','LIKE','%'.$request->query('search').'%')
            ->orWhere('dependencia.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('programa.nombre','LIKE','%'.$request->query('search').'%')
            ->orWhere('tramite.nro_matricula','LIKE','%'.$request->query('search').'%');
        })
        ->orderBy($request->query('sort'), $request->query('order'))
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }
        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function GetCertificadosFinalizados(Request $request)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];

        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idUnidad','tramite.idPrograma','tramite.idEstado_tramite', 
        'tramite.created_at as fecha','tramite.nro_tramite','tramite.nro_matricula','tramite.exonerado_archivo',
        'unidad.descripcion as unidad','dependencia.nombre as dependencia', 'programa.nombre as programa',
        'tipo_tramite_unidad.descripcion as tramite','tipo_tramite_unidad.costo',
        'tramite_detalle.certificado_final',
        DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'), 'usuario.nro_documento', 'usuario.correo',
        'voucher.archivo as voucher')
        ->join('tramite_detalle', 'tramite_detalle.idTramite_detalle', 'tramite.idTramite_detalle')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa', 'programa.idPrograma', 'tramite.idPrograma')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tramite.idEstado_tramite',15)
        ->where('tipo_tramite_unidad.idTipo_tramite',1)
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
        ->orderBy($request->query('sort'), $request->query('order'))
        ->get();
        
        foreach ($tramites as $key => $tramite) {
            $tramite->fut="fut/".$tramite->idTramite;
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario','tramite_requisito.idRequisito','tramite_requisito.des_estado_requisito','requisito.responsable')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->get();
        }

        $pagination=$this->Paginacion($tramites, $request->query('size'), $request->query('page')+1);
            $begin = ($pagination->currentPage()-1)*$pagination->perPage();
            $end = min(($pagination->perPage() * $pagination->currentPage()), $pagination->total());
            return response()->json(['status' => '200', 'data' =>array_values($pagination->items()),"pagination"=>[
                'length'    => $pagination->total(),
                'size'      => $pagination->perPage(),
                'page'      => $pagination->currentPage()-1,
                'lastPage'  => $pagination->lastPage()-1,
                'startIndex'=> $begin,
                'endIndex'  => $end - 1
            ]], 200);
    }

    public function downloadFoto($id)
    {
        try {
            $tramite=Tramite::find($id);
            $requisito=Tramite_Requisito::join('requisito','tramite_requisito.idRequisito','requisito.idRequisito')
            ->where('idTramite', $tramite->idTramite)
            // ->where('requisito.nombre','FOTO CARNET')
            ->where(function($query)
            {
                $query->where('requisito.nombre','FOTO CARNET')
                ->orWhere('requisito.idRequisito',15)
                ->orWhere('requisito.idRequisito',23);
            })
            ->first();
            //PDF file is stored under project/public/download/info.pdf
            $file= public_path(). $requisito->archivo;
            $headers = array(
              'Content-Type: application/pdf',
            );
            return Response::download($file, $tramite->nro_tramite.'.'.$requisito->extension, $headers);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => '400', 'message' => $e->getMessage()], 400);
        }  
    }

    public function Paginacion($items, $size, $page = null, $options = [])
    {
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
