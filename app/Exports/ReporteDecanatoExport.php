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

class ReporteDecanatoExport implements WithTitle,ShouldAutoSize, WithEvents,FromArray,WithColumnFormatting
{
    public $arrays;
    public $datos;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct($arrays,$datos){
        $this->arrays = $arrays;
        $this->datos = $datos;

    }
    public function title(): string
    {
        return 'CARPETAS';
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
                // Estableciendo formato a la columna ITEM
                $event->sheet->getColumnDimension('B')->setAutoSize(false);;
                $event->sheet->getColumnDimension('B')->setWidth(10);
                // Estableciendo formato a las columnas vacías
                $event->sheet->getColumnDimension('D')->setAutoSize(false);;
                $event->sheet->getColumnDimension('D')->setWidth(20);
                // Estableciendo formato a las columnas vacías
                $event->sheet->getColumnDimension('G')->setAutoSize(false);;
                $event->sheet->getColumnDimension('G')->setWidth(20);
                // Estableciendo formato a las columnas vacías
                $event->sheet->getColumnDimension('H')->setAutoSize(false);;
                $event->sheet->getColumnDimension('H')->setWidth(20);


                // Añadiendo estilos al título principal
                $event->sheet->getDelegate()->mergeCells('B1:H1');
                $event->sheet->getDelegate()->getStyle('B1:H1')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getDelegate()->getStyle('B1:H1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getDelegate()->getStyle('B1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                // $event->sheet->getDelegate()->getStyle('B1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('0FD9F1');
                foreach ($this->datos as $key => $dato) {
                    // Añadiendo estilos al subtitulo de cada programa 
                    $rango="B".($dato[0]+1).":"."H".($dato[0]+1);

                    // $event->sheet->getDelegate()->getRowDimension('1')->setAutoSize(false);
                    $event->sheet->getDelegate()->getRowDimension($dato[0]+1)->setRowHeight(30);
                    // $event->sheet->getDelegate()->getRowDimension('2')->setRowHeight(40);

                    $event->sheet->getDelegate()->mergeCells($rango);
                    $event->sheet->getDelegate()->getStyle($rango)->getFont()->setBold(true);
                    $event->sheet->getDelegate()->getStyle($rango)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $event->sheet->getDelegate()->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    // ---------------------
                    $rango="B".($dato[0]+2).":"."H".($dato[0]+2);
                    $event->sheet->getDelegate()->getStyle($rango)->getFont()->setBold(true);
                    $event->sheet->getDelegate()->getStyle($rango)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $event->sheet->getDelegate()->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    // $event->sheet->getDelegate()->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEB3B');
                    
                    // Añadiendo estilos a los datos de cada programna
                    $rango="B".($dato[0]+3).":"."H".($dato[0]+2+$dato[1]);
                    $event->sheet->getDelegate()->getStyle($rango)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $event->sheet->getDelegate()->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    // Creando los bordes de la tabla
                    $rango="B".($dato[0]+2).":"."H".($dato[0]+2+$dato[1]);
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
