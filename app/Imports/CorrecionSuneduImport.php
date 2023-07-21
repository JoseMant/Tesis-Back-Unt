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
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
                        if ($modalidad->acto_academico=$value[22]) {
                            $tramite_detalle->idModalidad_carpeta=$modalidad->idModalidad_carpeta;
                            break;
                        }
                    }

                    // $modalidad_carpeta=Modalidad_Carpeta::where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)
                    // ->where('nombre_padron', $value[22])
                    // ->where('modalidad_sustentacion',$value[62])
                    // ->where('estado',1)
                    // ->first();
                    // $tramite_detalle->idModalidad_carpeta=$modalidad_carpeta->idModalidad_carpeta;

                    if (is_string($value[11])) $tramite_detalle->fecha_primera_matricula=$value[11];
                    else {
                        $date = Date::excelToDateTimeObject($value[11]);
                        $tramite_detalle->fecha_primera_matricula=date_format($date,"Y-m-d");
                    }

                    if (is_string($value[12])) $tramite_detalle->fecha_ultima_matricula=$value[12];
                    else {
                        $date = Date::excelToDateTimeObject($value[12]);
                        $tramite_detalle->fecha_ultima_matricula=date_format($date,"Y-m-d");
                    }
                    
                    $modalidad_estudio_carpeta=Programa_Estudios_Carpeta::where('descripcion',$value[20])->first();
                    $tramite_detalle->idPrograma_estudios_carpeta=$modalidad_estudio_carpeta->idPrograma_estudios_carpeta;
                    $tramite_detalle->nro_creditos_carpeta=$value[21];
                    $tramite_detalle->url_trabajo_carpeta=$value[24];
                    $tramite_detalle->nombre_trabajo_carpeta=$value[25];

                    if (is_string($value[30])) $tramite_detalle->fecha_inicio_acto_academico=$value[30];
                    else {
                        $date = Date::excelToDateTimeObject($value[30]);
                        $tramite_detalle->fecha_inicio_acto_academico=date_format($date,"Y-m-d");
                    }

                    // $tramite_detalle->fecha_inicio_acto_academico=$value[30];
                    $tramite_detalle->originalidad=$value[32];
                    $tramite_detalle->save();
                }
            }
        }
    }
}
