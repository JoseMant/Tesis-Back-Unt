<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PadronSuneduExport implements WithMultipleSheets
{
    public $idResolucion;

    public function __construct($idResolucion){
        $this->idResolucion = $idResolucion;
    }
    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new HojaPadron($this->idResolucion);
        $sheets[] = new MaestroExport();

        return $sheets;
    }
}

class MaestroExport implements WithTitle,FromArray,WithEvents,ShouldAutoSize
{
    public function title(): string
    {
        return 'MAESTRO';
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cellRange = '1'; 
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
                $cellRange = '9'; 
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
                $cellRange = '16'; 
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('0FD9F1');
                // $event->sheet->getDelegate()->mergeCells('E1:F1');
            },
        ];
    }
    public function array(): array
    {
        return [
            ["N°","PROG_ESTU","","","TODOS LOS CAMPOS RESALTADOS SON EDITABLES EDITABLES"],
            ["1","CICLO REGULAR"],
            ["2","CONVALIDACIÓN"],
            ["3","COMPLEMENTACIÓN ACADÉMICA"],
            ["4","COMPLEMENTACIÓN PEDAGÓGICA"],
            ["5","PROGRAMA PARA ADULTOS"],
            ["6","OTROS"],
            ["",""],
            ["N°","MOB_OBT"],
            ["1","AUTOMATICO"],
            ["2","TESIS"],
            ["3","TRABAJO DE INVESTIGACIÓN"],
            ["4","TRABAJO DE SUFICIENCIA PROFESIONAL"],
            ["5","TRABAJO ACADÉMICO"],
            ["",""],
            ["N°","MOD_SUSTENTACION"],
            ["1","PRESENCIAL"],
            ["2","VIRTUAL"]
        ];
    }
}
