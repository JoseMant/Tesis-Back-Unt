<?php

namespace App\Exports;

use App\Tramite;
use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TramitesExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function headings(): array
    {
        return [
            'TIPO_DOCUMENTO',
            'NUMERO_DOCUMENTO',
            'TIPO_TRAMITE',
        ];
    }
    public function collection()
    {
         $tramites = Tramite::select('usuario.tipo_documento','usuario.nro_documento','tipo_tramite_unidad.descripcion')->join('usuario','tramite.idUsuario','usuario.idUsuario')
         ->join('tipo_tramite_unidad','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
         ->where('tramite.idEstado_tramite',25)
         ->get();
         
         return $tramites;

    }
}
