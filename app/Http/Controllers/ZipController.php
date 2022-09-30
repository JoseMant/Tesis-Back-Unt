<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Tymon\JWTAuth\Facades\JWTAuth;
use File;
use ZipArchive;
use App\Tramite;
use App\Tramite_Requisito;
use App\User;
use App\PersonaSuv;
use App\PersonaSga;
use App\Escuela;
use App\Historial_Estado;
use Illuminate\Support\Facades\DB;
class ZipController extends Controller
{
    public function downloadFotos()
    {
        //obtener carnets validados
        $tramites=Tramite::select('tramite.idTramite','tramite.idUsuario','tramite.idDependencia_detalle', DB::raw('CONCAT(usuario.nombres," ",usuario.apellidos) as solicitante')
        ,'tramite.created_at as fecha','unidad.descripcion as unidad','tipo_tramite_unidad.descripcion as tramite','tramite.nro_tramite as codigo','dependencia.nombre as facultad'
        ,'tramite.nro_matricula','usuario.nro_documento','usuario.correo','voucher.archivo as voucher'
        , DB::raw('CONCAT("N° ",voucher.nro_operacion," - ",voucher.entidad) as entidad'),'tipo_tramite_unidad.costo'
        ,'tramite.exonerado_archivo','tramite.idUnidad','tramite.idEstado_tramite')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->join('unidad','unidad.idUnidad','tramite.idUnidad')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tramite_detalle','tramite_detalle.idTramite_detalle','tramite.idTramite_detalle')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('estado_tramite','tramite.idEstado_tramite','estado_tramite.idEstado_tramite')
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->where('tipo_tramite.idTipo_tramite',3)
        ->where('tramite.idEstado_tramite',16)
        // ->where(function($query)
        // {
        //     $query->where('tramite.idEstado_tramite',7)
        //     ->orWhere('tramite.idEstado_tramite',16);
        // })
        ->get(); 
        foreach ($tramites as $key => $tramite) {
            $tramite->requisitos=Tramite_Requisito::select('requisito.nombre','tramite_requisito.archivo','tramite_requisito.idUsuario_aprobador','tramite_requisito.validado',
            'tramite_requisito.comentario')
            ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
            ->where('idTramite',$tramite->idTramite)
            ->where('requisito.nombre','FOTO CARNET')
            ->get();
        }
        // return $tramites;
        $zip = new ZipArchive;
        $date=date('Y-m-d h-i-s');
        // $fileName = 'Fotos_Carnet_'.$date.'.zip';
        $fileName = 'Fotos_Carnet.zip';

        if ($zip->open(public_path($fileName),ZipArchive::CREATE) === TRUE)
        {
            foreach ($tramites as $key => $tramite) {
                $usuario=User::findOrFail($tramite->idUsuario);
                foreach ($tramite->requisitos as $key => $requisito) {
                    $value =public_path($requisito->archivo);
                    $relativeNameInZipFile = basename(public_path($requisito->archivo));
                    $zip->addFile(public_path($requisito->archivo), $usuario->tipo_documento."_".$relativeNameInZipFile);
                }
                  
            }
            $zip->close();
        }
        return response()->download(public_path($fileName));
    }
}
