<?php

namespace App\Exports;

use App\Tramite;
use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;

class ReporteGradoExport implements ShouldAutoSize, WithEvents,FromArray
{
    public $idDependencia;
    public $cronograma;
    public $arrays;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct($idDependencia,$cronograma,$arrays){
        $this->idDependencia = $idDependencia;
        $this->cronograma = $cronograma;
        $this->arrays = $arrays;

    }
    // public function columnFormats(): array
    // {
    //     return [
    //         'B' => NumberFormat::FORMAT_TEXT,
    //         'Q' => NumberFormat::FORMAT_TEXT,
    //     ];
    // }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cellRange = '1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
    // public function headings(): array
    // {
    //     return [
    //         // 'NOMBRE'=>['First row', 'First row'],
    //         'N°',
    //         'NRO TRAMITE','EGRESADOS','MOTIVO','ESTADO','ASIGNADO'

    //     ];
    // }
    public function array(): array
    {
        return $this->arrays;

    }
    // public function collection()
    // {
    //     return $this->arrays;
    // }
}
