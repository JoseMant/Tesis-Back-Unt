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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
class ReporteCarpetasAptasExport implements WithMultipleSheets
{
    public $arrays;
    public $datos;
    public $idTipo_tramite_unidad;
    public $cronograma;
    public $arraysProgramas;


    public function __construct($arrays,$datos,$idTipo_tramite_unidad,$cronograma,$arraysProgramas){
        $this->arrays = $arrays;
        $this->datos = $datos;
        $this->idTipo_tramite_unidad = $idTipo_tramite_unidad;
        $this->cronograma = $cronograma;
        $this->arraysProgramas = $arraysProgramas;
    }
    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new HojaGeneralExport($this->arrays,$this->datos,$this->idTipo_tramite_unidad,$this->cronograma);

        $keyProgramas=0;
        foreach ($this->arraysProgramas as $key => $arrayPrograma) {
            $sheets[] = new HojaProgramaExport($arrayPrograma,$this->datos[$keyProgramas][1],$this->idTipo_tramite_unidad);
            $keyProgramas++;
        }

        return $sheets;
    }
}


class HojaGeneralExport implements WithTitle,ShouldAutoSize, WithEvents,FromArray,WithColumnFormatting
{
    public $arrays;
    public $datos;
    public $idTipo_tramite_unidad;
    public $cronograma;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct($arrays,$datos,$idTipo_tramite_unidad,$cronograma){
        $this->arrays = $arrays;
        $this->datos = $datos;
        $this->idTipo_tramite_unidad = $idTipo_tramite_unidad;
        $this->cronograma = $cronograma;
    }
    public function title(): string
    {
        if ($this->idTipo_tramite_unidad==15) {
            return 'Bachilleres '.$this->cronograma;
        }elseif ($this->idTipo_tramite_unidad==16) {
            return 'Títulos '.$this->cronograma;
        }elseif ($this->idTipo_tramite_unidad==34) {
            return 'Títulos Seg. Especialidad '.$this->cronograma;
        }
    }
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $final="F";
                if ($this->idTipo_tramite_unidad!=15) {
                    $final="H";
                }
                // Añadiendo estilos al título principal
                $event->sheet->getDelegate()->mergeCells('B1:'.$final.'1');
                $event->sheet->getDelegate()->getStyle('B1:'.$final.'1')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getDelegate()->getStyle('B1:'.$final.'1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getDelegate()->getStyle('B1:'.$final.'1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $event->sheet->getDelegate()->mergeCells('B2:'.$final.'2');
                $event->sheet->getDelegate()->getStyle('B2:'.$final.'2')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getDelegate()->getStyle('B2:'.$final.'2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getDelegate()->getStyle('B2:'.$final.'2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $event->sheet->getDelegate()->getStyle('B4:'.$final.'4')->getFont()->setBold(true)->setSize(12);
                $event->sheet->getDelegate()->getStyle('B4:'.$final.'4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getDelegate()->getStyle('B4:'.$final.'4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle('B4:F4')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ])->getAlignment()->setWrapText(true);
                // -----------------------------------------------------------------------------------------------------------
                foreach ($this->datos as $key => $dato) {
                    // Añadiendo estilos a los datos de cada programna
                    $rango="B".($dato[0]+1).":".$final.($dato[0]+1+$dato[1]);
                    $event->sheet->getDelegate()->getStyle($rango)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $event->sheet->getDelegate()->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    // Creando los bordes de la tabla
                    $rango="B".($dato[0]+1).":".$final.($dato[0]+1+$dato[1]);
                    $event->sheet->getStyle($rango)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => '000000'],
                            ],
                        ],
                    ])->getAlignment()->setWrapText(true);
                }
            },
        ];
    }

    public function array(): array
    {
        return $this->arrays;

    }

}

class HojaProgramaExport implements WithTitle,ShouldAutoSize, WithEvents,FromArray,WithColumnFormatting
{
    public $arrayPrograma;
    public $numTramites;
    public $idTipo_tramite_unidad;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct($arrayPrograma,$numTramites,$idTipo_tramite_unidad){
        $this->arrayPrograma = $arrayPrograma;
        $this->numTramites = $numTramites;
        $this->idTipo_tramite_unidad = $idTipo_tramite_unidad;
    }
    public function title(): string
    {
        return $this->arrayPrograma[1][1];
    }
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                // Añadiendo estilos al título principal
                $final="F";
                if ($this->idTipo_tramite_unidad!=15) {
                    $final="H";
                }
                $event->sheet->getDelegate()->mergeCells('B1:'.$final.'1');
                $event->sheet->getDelegate()->getStyle('B1:'.$final.'1')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getDelegate()->getStyle('B1:'.$final.'1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getDelegate()->getStyle('B1:'.$final.'1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $event->sheet->getDelegate()->mergeCells('B2:'.$final.'2');
                $event->sheet->getDelegate()->getStyle('B2:'.$final.'2')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getDelegate()->getStyle('B2:'.$final.'2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getDelegate()->getStyle('B2:'.$final.'2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $event->sheet->getDelegate()->mergeCells('B3:'.$final.'3');
                $event->sheet->getDelegate()->getStyle('B3:'.$final.'3')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getDelegate()->getStyle('B3:'.$final.'3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getDelegate()->getStyle('B3:'.$final.'3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $event->sheet->getDelegate()->getStyle('B5:'.$final.'5')->getFont()->setBold(true)->setSize(12);
                $event->sheet->getDelegate()->getStyle('B5:'.$final.'5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getDelegate()->getStyle('B5:'.$final.'5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $event->sheet->getStyle('B5:'.$final.'5')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ])->getAlignment()->setWrapText(true);
                // -----------------------------------------------------------------------------------------------------------
                
                // Añadiendo estilos a los datos de cada programna
                $rango="B5:".$final.($this->numTramites+5);
                $event->sheet->getDelegate()->getStyle($rango)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getDelegate()->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                // Creando los bordes de la tabla
                $rango="B6:".$final.($this->numTramites+5);
                $event->sheet->getStyle($rango)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ])->getAlignment()->setWrapText(true);
                
            },
        ];
    }

    public function array(): array
    {
        return $this->arrayPrograma;
    }

}
