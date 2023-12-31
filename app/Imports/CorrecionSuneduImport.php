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
use App\Universidad;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;

class Correcionsuneduimport implements ToCollection,WithMultipleSheets
{

    private $estado=0;
    private $dato="";
    private $numFilas=0;
    
    public function sheets(): array
    {
        return [
        'PADRÓN' => $this, // Nombre de la hoja de Excel que va a leer. Si no se encuentra el nombre tal cual, se omitirá el registro.
        ];
    }

    // public function onUnknownSheet($sheetName)
    // {
    //     info("La hoja {$sheetName} no se encontró.");
    //     $this->estado=1;
    // }

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
        // $this->setDato("hola"); 
        $numAlumnos_porRegistrar = count($collection);
        $this->setNumFilas( $numAlumnos_porRegistrar );
        foreach ($collection as $key => $value) {
            if ($key>=1) {
                if ($value[0]) {
                    
                    $tramite=Tramite::find($value[0]);
                    $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);

                    if ($tramite->idTipo_tramite_unidad==16||$tramite->idTipo_tramite_unidad==34) {
                        $modalidades_carpeta=Modalidad_Carpeta::where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)
                        ->where('estado',1)
                        ->where('modalidad_sustentacion',$value[62])
                        ->get();
                    }else {
                        $modalidades_carpeta=Modalidad_Carpeta::where('idTipo_tramite_unidad',$tramite->idTipo_tramite_unidad)
                        ->where('estado',1)
                        ->get();
                    }

                    foreach ($modalidades_carpeta as $modalidad) {
                        if ($modalidad->acto_academico=$value[22]) {
                            $tramite_detalle->idModalidad_carpeta=$modalidad->idModalidad_carpeta;
                            break;
                        }
                    }

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
                    
                    if ($value[17]) {
                        $universidad=Universidad::where('codigo_sunedu',$value[17])->first();
                        if ($universidad) {
                            $tramite_detalle->idUniversidad=$universidad->idUniversidad;
                        }
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
