<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use DB;
use App\Tramite;
class CarnetsSolicitadosExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new SolicitadosExport();
        $sheets[] = new AyudaExport();
        $sheets[] = new MaestroExport();

        return $sheets;
    }
}
class SolicitadosExport implements FromCollection,WithHeadings, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function title(): string
    {
        return 'ESTUDIANTES';
    }
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
class AyudaExport implements WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function title(): string
    {
        return 'AYUDA';
    }
}
class MaestroExport implements WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function title(): string
    {
        return 'MAESTRO';
    }
}
