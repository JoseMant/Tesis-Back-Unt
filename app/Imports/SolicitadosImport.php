<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use App\Tramite;
use App\Historial_Estado;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;


class SolicitadosImport implements ToCollection
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
            if ($key>=7) {
                
                // LÓGICA PARA RECHAZAR REQUISITOS
                //obtener carnets validados
                
                if ($value[2]!=null) {
                    # code...
                    $tramite=Tramite::select('tramite.idTramite','tramite.nro_matricula','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idEstado_tramite','tramite.idUsuario',
                    'tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite_unidad')
                    ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                    ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                    ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                    ->where('tramite.idEstado_tramite',25)
                    ->where('tipo_tramite.idTipo_tramite',3)
                    ->where('usuario.nro_documento',$value[2])
                    ->first();
    
                    if ($tramite) {
                        //CAMBIAMOS EL ESTADO DE CADA TRÁMITE A PROCESO INICIADO ANTE SUNEDU
                        $historial_estados=new Historial_Estado;
                        $historial_estados->idTramite=$tramite->idTramite;
                        $historial_estados->idUsuario=$idUsuario;
                        $historial_estados->idEstado_actual=$tramite->idEstado_tramite;
                        $historial_estados->idEstado_nuevo=26;
                        $historial_estados->fecha=date('Y-m-d h:i:s');
                        $historial_estados->save();
                        $tramite->idEstado_tramite=$historial_estados->idEstado_nuevo;
                        $tramite->save();
                    }
                }
            }
        }
    }
}
