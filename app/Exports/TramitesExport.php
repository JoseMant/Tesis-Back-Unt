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
            // 'Email',
        ];
    }
    public function collection()
    {
         $tramites = Tramite::select('usuario.tipo_documento','usuario.nro_documento')->join('usuario','tramite.idUsuario','usuario.idUsuario')
         ->where('tramite.idEstado_tramite',25)
         ->get();
         
         return $tramites;

    }
}
