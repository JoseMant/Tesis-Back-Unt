<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Tramite;
use App\Tramite_Detalle;
use App\Modalidad_Carpeta;
use App\Programa_Estudios_Carpeta;
use App\User;

class Correcionsuneduimport implements ToCollection
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
    
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        $numAlumnos_porRegistrar = count($collection);
        $this->setNumFilas( $numAlumnos_porRegistrar );
        foreach ($collection as $key => $value) {
            if ($key>=1) {
                if ($value[0]) {
                    $tramite=Tramite::find($value[0]);
                    $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                    $modalidades_carpeta=Modalidad_Carpeta::where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)
                    ->where('estado',1)
                    ->where('modalidad_sustentacion',$value[62])->get();
                    foreach ($modalidades_carpeta as $modalidad) {
                        $this->setDato("hola");
                        if ($modalidad->acto_academico=$value[22]) {
                            $tramite_detalle->idModalidad_carpeta=$modalidad->idModalidad_carpeta;
                            break;
                        }
                        
                    }                    
                    
                    $tramite_detalle->fecha_primera_matricula=$value[11];
                    $tramite_detalle->fecha_ultima_matricula=$value[12];
                    $modalidad_estudio_carpeta=Programa_Estudios_Carpeta::where('descripcion',$value[20])->first();
                    $tramite_detalle->idPrograma_estudios_carpeta=$modalidad_estudio_carpeta->idPrograma_estudios_carpeta;
                    $tramite_detalle->nro_creditos_carpeta=$value[21];
                    $tramite_detalle->url_trabajo_carpeta=$value[24];
                    $tramite_detalle->nombre_trabajo_carpeta=$value[25];
                    $tramite_detalle->fecha_inicio_acto_academico=$value[30];
                    $tramite_detalle->originalidad=$value[32];
                    $tramite_detalle->save();
                }
            }
        }
    }
}
