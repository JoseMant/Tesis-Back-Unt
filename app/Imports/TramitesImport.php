<?php

namespace App\Imports;

use App\Tramite;
use App\Tramite_Requisito;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Illuminate\Support\Facades\DB;

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
        // count($collection);
        $this->estado=1;
        $numAlumnos_porRegistrar = count($collection)-6;
        $this->setNumFilas( $numAlumnos_porRegistrar );
        foreach ($collection as $key => $value) {
            if ($key==7) {
                // $this->setDato( $value[3]);
                // LÓGICA PARA RECHAZAR REQUISITOS
                //obtener carnets validados

                $tramite=Tramite::select('tramite.idTramite','tramite.nro_matricula','tipo_tramite_unidad.idTipo_tramite_unidad')
                ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
                ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
                ->join('usuario','usuario.idUsuario','tramite.idUsuario')
                ->where('tramite.idEstado_tramite',3)
                ->where('tipo_tramite.idTipo_tramite',3)
                ->where('usuario.nro_documento',$value[3])
                ->first();

                // $this->setDato( $tramite->idTramite);


                $tramite_requisito=Tramite_Requisito::select('tramite_requisito.idTramite','tramite_requisito.idRequisito','requisito.nombre','tramite_requisito.archivo'
                ,'tramite_requisito.idUsuario_aprobador','tramite_requisito.validado','tramite_requisito.comentario')
                ->join('requisito','requisito.idRequisito','tramite_requisito.idRequisito')
                ->where('idTramite',$tramite->idTramite)
                ->where('requisito.nombre','FOTO CARNET')
                ->first();
                // foreach ($tramite_requisito as $key => $value) {
                    $this->setDato( $tramite_requisito->idTramite);
                // }
                
                
                // $update=Tramite_Requisito::find($tramite_requisito->idTramite,$tramite_requisito->idRequisito);
                // // foreach ($tramite->requisitos as $key => $value) {
                    $tramite_requisito->comentario=$value[16]; 
                    $tramite_requisito->update();
                    $this->setDato( $tramite_requisito->nombre);
                // // }
                
                
                // $tramite->requisitos->comentario=$value[16]; 
                // $tramite->update();
                
                
                
                // $tramite_requisito->validado=1;
                // $this->setDato( $tramite_requisito->comentario);

            }
        }
    }
}