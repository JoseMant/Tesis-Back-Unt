<?php

namespace App\Imports;

use App\Tramite;
use App\Tramite_Requisito;
use App\User;
use App\Tipo_Tramite;
use App\Tipo_tramite_Unidad;
use App\Historial_Estado;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Illuminate\Support\Facades\DB;
use App\Jobs\ActualizacionTramiteJob;
use Tymon\JWTAuth\Facades\JWTAuth;
class TramitesImport implements ToCollection
{
    private $estado=0;
    private $dato="";
    private $numFilas=0;
    public function getStatus(): int
    {
        return $this->estado;
    }
    public function setNumFilas($numFilas)
    {
      $this->numFilas = $numFilas;
    }
  
    public function getNumFilas(): int
    {
        return $this->numFilas;
    }
    public function getDato(): string
    {
        return $this->dato;
    }
    public function setDato($dato)
    {
      $this->dato = $dato;
    }
    public function collection(Collection $collection)
    {
        // OBTENEMOS EL DATO DEL USUARIO QUE INICIO SESIÓN MEDIANTE EL TOKEN
        $token = JWTAuth::getToken();
        $apy = JWTAuth::getPayload($token);
        $idUsuario=$apy['idUsuario'];
        // count($collection);
        $this->estado=1;
        $numAlumnos_porRegistrar = count($collection)-6;
        $this->setNumFilas( $numAlumnos_porRegistrar );
        foreach ($collection as $key => $value) {
            if ($key==7) {
                
                // LÓGICA PARA RECHAZAR REQUISITOS
                //obtener carnets validados

                $tramite=Tramite::select('tramite.idTramite','tramite.nro_matricula','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idEstado_tramite','tramite.idUsuario',
                'tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite_unidad')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->where('tramite.idEstado_tramite',16)
                ->where('tipo_tramite.idTipo_tramite',3)
                ->where('usuario.nro_documento',$value[3])
                ->first();

                if ($tramite) {
                    //REGISTRAMOS EL ESTADO DEL TRÁMITE
                    $historial_estados=new Historial_Estado;
                    $historial_estados->idTramite=$tramite->idTramite;
                    $historial_estados->idUsuario=$idUsuario;
                    $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                    $historial_estados->idEstado_nuevo=9;
                    $historial_estados->fecha=date('Y-m-d h:i:s');
                    $historial_estados->save();
                    $tramite->idEstado_tramite=$historial_estados->idEstado_nuevo;
                    $tramite->save();
                    $tramite_requisito=Tramite_Requisito::select('tramite_requisito.idTramite','tramite_requisito.idRequisito','requisito.nombre','tramite_requisito.archivo'
                    ,'tramite_requisito.idUsuario_aprobador','tramite_requisito.validado','tramite_requisito.comentario')
                    ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
                    ->where('idTramite',$tramite->idTramite)
                    ->where('requisito.nombre','FOTO CARNET')
                    ->first();
                    $tramite_requisito->comentario=$value[16]; 
                    $tramite_requisito->des_estado_requisito="RECHAZADO"; 
                    $tramite_requisito->update();
                    //Datos para el envío del correo
                    $usuario=User::find($tramite->idUsuario);
                    $tipo_tramite=Tipo_Tramite::Find($tramite->idTipo_tramite);
                    $tipo_tramite_unidad=Tipo_tramite_Unidad::Find($tramite->idTipo_tramite_unidad);
                    // mensaje de rechazo de foto
                    dispatch(new ActualizacionTramiteJob($usuario,$tramite,$tipo_tramite,$tipo_tramite_unidad));
                }
            }
        }
    }
}