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
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
class ReporteTesoreriaExport implements FromCollection,WithHeadings,ShouldAutoSize, WithEvents,WithColumnFormatting
{
    public $fecha_inicio;
    public $fecha_fin;

    public function __construct($fecha_inicio,$fecha_fin){
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_fin = $fecha_fin;
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cellRange = '1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
        ];
    }
    public function headings(): array
    {
        return [
            'ALUMNO',
            'DNI',
            'COD. MATRÍCULA',
            'TRÁMITE',
            'ESCUELA/MENCIÓN/PROGRAMA',
            'BANCO',
            'NRO. OPERACIÓN',
            'FECHA OPERACIÓN',
            'MONTO(S./)'
        ];
    }
    public function collection()
    {
        $fecha_inicio=$this->fecha_inicio;
        $fecha_fin=$this->fecha_fin;
        $vouchers=Tramite::select(DB::raw('CONCAT(usuario.apellidos," ",usuario.nombres) as solicitante'),'usuario.nro_documento','tramite.nro_matricula',
        DB::raw("(case 
            when tramite.idUnidad = 1 then CONCAT(tipo_tramite_unidad.descripcion) 
            when tramite.idUnidad = 2 then CONCAT(tipo_tramite_unidad.descripcion) 
            when tramite.idUnidad = 3 then CONCAT(tipo_tramite_unidad.descripcion,'-',tipo_tramite.descripcion) 
            when tramite.idUnidad = 4 then CONCAT(tipo_tramite_unidad.descripcion) 
        end) as tipo_tramite"),
        DB::raw("(case 
            when tramite.idUnidad = 1 then (select nombre from escuela where idEscuela=tramite.idDependencia_detalle)  
            when tramite.idUnidad = 4 then  (select denominacion from mencion where idMencion=tramite.idDependencia_detalle)
        end) as programa"),
        'voucher.entidad','voucher.nro_operacion','voucher.fecha_operacion','tipo_tramite_unidad.costo'
        )
        ->join('voucher','tramite.idVoucher','voucher.idVoucher')
        ->join('usuario','tramite.idUsuario','usuario.idUsuario')
        ->join('tramite_detalle','tramite.idTramite_detalle','tramite_detalle.idTramite_detalle')
        ->join('tipo_tramite_unidad','tramite.idTipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad')
        ->join('tipo_tramite','tipo_tramite.idTipo_tramite','tipo_tramite_unidad.idTipo_tramite')
        ->where('voucher.des_estado_voucher','APROBADO')
        ->where(function($query) use ($fecha_inicio,$fecha_fin)
        {
            if($fecha_inicio){
                $query->where('voucher.fecha_operacion','>=',$fecha_inicio);
            }
            if($fecha_fin){
                $query->where('voucher.fecha_operacion','<=',$fecha_fin);
            }
        })
        ->where('tramite.idTipo_tramite_unidad','!=',37)
        ->where('tramite.idEstado_tramite','!=',29)
        ->orderBy('fecha_operacion','asc')
        ->orderBy('programa','asc')
        ->orderBy('tipo_tramite','asc')
        ->orderBy('solicitante','asc')
        // ->take($request->query('size'))
        // ->skip($request->query('page')*$request->query('size'))
        ->get();
        return $vouchers;
    }
}
