<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Tramite;
use App\Tramite_Detalle;
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
                // $this->setDato($value[11]);
                // LÓGICA PARA RECHAZAR REQUISITOS
                //obtener carnets validados
                if ($value[0]) {
                    $tramite=Tramite::find($value[0]);
                    $tramite_detalle=Tramite_Detalle::find($tramite->idTramite_detalle);
                    
                    $tramite_detalle->fecha_primera_matricula=$value[11];
                    $tramite_detalle->fecha_ultima_matricula=$value[12];

                    switch ($value[20]) {
                        case 'CICLO REGULAR':
                            $idprograma=2;
                            break;
                        case 'CONVALIDACIÓN':
                            $idprograma=3;
                            break;
                        case 'COMPLEMENTACIÓN ACADÉMICA':
                            $idprograma=4;
                            break;
                        case 'COMPLEMENTACIÓN PEDAGÓGICA':
                            $idprograma=5;
                            break;
                        case 'PROGRAMA PARA ADULTOS':
                             $idprograma=6;
                             break;
                         case 'OTROS':
                             $idprograma=7;
                            break;

                        default:
                            # code...
                            break;
                    }
                    $tramite_detalle->idPrograma_estudios_carpeta=$idprograma;

                    $tramite_detalle->nro_creditos_carpeta=$value[21];
                    $tramite_detalle->url_trabajo_carpeta=$value[24];
                    $tramite_detalle->nombre_trabajo_carpeta=$value[25];
                    $tramite_detalle->fecha_inicio_acto_academico=$value[30];
                    $tramite_detalle->save();
                //     // $value[22];
                //     // $value[26];
                //     // $value[31];
                //     // $value[32];
                //     // $value[40];
                //     // $value[41];
                //     // $value[42];
                //     // $value[43];
                //     // $value[55];
                //     // $value[56];
                //     // $value[57];
                //     // $value[59];
                //     // $value[60];
                //     // $value[61];
                //     // $value[62];
                //     $this->setDato( $value[11] );
                
                    $usuario=User::find($tramite->idUsuario);
                    $usuario->sexo=$value[8];
                    $usuario->tipo_documento=$value[9];
                    $usuario->nro_documento=$value[10];
                    $usuario->save();



    
                }
            }
        }
    }
}
