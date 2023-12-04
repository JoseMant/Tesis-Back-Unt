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
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HojaPadronOficio implements WithTitle,FromCollection,WithHeadings,ShouldAutoSize, WithEvents,WithColumnFormatting
{
    public $idOficio;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct($idOficio){
        $this->idOficio = $idOficio;
    }
    public function title(): string
    {
        return 'PADRÓN_OFICIO';
    }
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'Q' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'R' => NumberFormat::FORMAT_TEXT,
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cellRange = '1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
                // No hay mas estilos para los headings
            },
        ];
    }
    public function headings(): array
    {
        return [
            'COD_UNIV','RAZ_SOC','FAC_NOM','ESC_POS','PRIM_APE','SEG_APE','NOMBRE','SEXO','DOCU_TIP','DOCU_NUM','MATRI_FEC','EGRES_FEC','ABRE_GYT','CARR_PROG','DEN_GRAD','SEG_ESP',
            'PROC_BACH','PROC_INST_ORIG', //NUEVO aplica solo para traslado externo  y universidades no licenciadas
            'PROC_TITULO_PED', //solo aplica para preford
            'PROG_ESTU','NUM_CRED','MOD_OBT','MOD_EST','REG_METADATO','TRAB_INV',
            'REQ_IDM', //NUEVO SÍ SOLO PARA BACHILLER
            'PROG_ACREDIT','FEC_INICIO_ACREDIT','FECHA_FIN_ACREDIT','FEC_INI_TRA_TIT','TRAB_INVEST_ORIGINAL',
            'MEC_UTI',//NUEVO NUEVO CAMPO EN DATOS DE DIPLOMA
            'DEP_VER_ORIG',//NUEVO PREGUNTAR A QUÉ ÁREA PERTENECE LA COMISIÓN DE ÉTICA
            'PROC_REV_PAIS','PROC_REV_UNIV','PROC_REV_GRADO','CRIT_REV','RESO_NUM','RESO_FEC',
            'RESO_NUM_DUP_NUE',//NUEVO SOLO APLICA PARA DUPLICADO
            'RESO_FEC_DUP_NUE',//NUEVO SOLO APLICA PARA DUPLICADO
            'DIPL_FEC_ORG',
            'DIPL_FECHA_DUP_NUEVO', //DIP_FECHA_DUP
            'DIPL_NUM','DIPL_TIP_EMI','REG_LIBRO','REG_FOLIO','REG_REGISTRO','CARGO1','AUTORIDAD1','CARGO2','AUTORIDAD2','CARGO3','AUTORIDAD3','PROC_PAIS_EXT','PROC_UNIV_EXT'
            ,'PROC_GRADO_EXT',
            'REG_OFICIO',
            'FEC_MAT_MOD', //FEC_MAT_PROG
            'FEC_INICIO_MOD',//FEC_INICIO_PROG
            'FEC_FIN_MOD',//FEC_FIN_PROG 
            'MOD_SUSTENTACION',

        ];
    }
    public function collection()
    {
        $tramites = Tramite::select(DB::raw('CONCAT("004") AS COD_UNIV'),
        DB::raw('CONCAT("UNIVERSIDAD NACIONAL DE TRUJILLO") AS RAZ_SOC'),
        DB::raw("(case 
                    when tramite.idUnidad = 1 then dependencia.denominacion  
                    when tramite.idUnidad = 4 then  (select denominacion from dependencia d where d.idDependencia=dependencia.idDependencia2)
                end) AS FAC_NOMBRE"),
        DB::raw('CONCAT("") AS ESC_POS'),
        'usuario.apellido_paterno',
        'usuario.apellido_materno',
        'usuario.nombres',
        'usuario.sexo',
        'usuario.tipo_documento',
        'usuario.nro_documento',
        'tramite_detalle.fecha_primera_matricula AS MATRI_FEC',
        'tramite_detalle.fecha_ultima_matricula AS EGRES_FEC',
        DB::raw('substr(tipo_tramite_unidad.diploma_obtenido,1, 1) AS ABRE_GYT'),
        DB::raw("(case 
                    when tramite.idUnidad = 1 then programa.nombre
                    when tramite.idUnidad = 4 then programa.denominacion
                end) AS CARR_PROG"),
        'diploma_carpeta.descripcion AS DEN_GRAD',
        DB::raw("(case 
                    when tramite.idUnidad = 4 then programa.nombre
                end) AS SEG_ESP"),
        DB::raw("(case 
                when tramite_detalle.idUniversidad IS NULL then '004'
                when tramite_detalle.idUniversidad IS NOT NULL then universidad.codigo_sunedu 
            end) AS PROC_BACH"),
        DB::raw('CONCAT("") AS PROC_INST_ORIG'),
        DB::raw('CONCAT("") AS PROC_TITULO_PED'),
        'programa_estudios_carpeta.descripcion AS PROG_ESTU',
        'tramite_detalle.nro_creditos_carpeta AS NUM_CRED',
        'modalidad_carpeta.nombre_padron AS MOD_OBT',
        DB::raw('CONCAT("P") AS MOD_EST'),
        'tramite_detalle.url_trabajo_carpeta AS REG_METADATO',
        'tramite_detalle.nombre_trabajo_carpeta AS TRAB_INV',
        DB::raw('CONCAT("") AS REQ_IDM'),
        // DB::raw("(case 
        //         when tramite_detalle.idTipo_tramite_unidad = 15 then 'SI'
        //         when tipo_tramite_unidad.idTipo_tramite_unidad = 15 then 'SI'
        //         when tipo_tramite_unidad.idTipo_tramite_unidad = 15 then 'SI'
        //     end) AS REQ_IDM"),
        DB::raw("(case 
                    when tramite_detalle.idAcreditacion IS NOT NULL then 'SI' 
                    else 'NO'
                end) AS PROG_ACREDIT"),
        'acreditacion.fecha_inicio AS FEC_INICIO_ACREDIT',
        'acreditacion.fecha_fin AS FECHA_FIN_ACREDIT',
        'tramite_detalle.fecha_inicio_acto_academico AS FEC_INI_TRA_TIT',
        DB::raw("(case 
                    when tramite_detalle.idModalidad_carpeta != 1 then 'SI' 
                end) as TRAB_INVEST_ORIGINAL"),
        DB::raw('CONCAT(tramite_detalle.originalidad,"%") AS MEC_UTI'),
        DB::raw("(case 
                    when tramite.idTipo_tramite_unidad = 16 then dependencia.denominacion  
                    when tramite.idTipo_tramite_unidad = 34 then  (select denominacion from dependencia d where d.idDependencia=dependencia.idDependencia2)
                end) AS DEP_VER_ORIG"),
        DB::raw('CONCAT("") as PROC_REV_PAIS'),
        DB::raw('CONCAT("") as PROC_REV_UNIV'),
        DB::raw('CONCAT("") as PROC_REV_GRADO'),
        DB::raw('CONCAT("") as CRIT_REV'),
        'resolucion.nro_resolucion AS RESO_NUM',
        'resolucion.fecha AS RESO_FEC',
        DB::raw('CONCAT("") as RESO_NUM_DUP_NUE'),
        DB::raw('CONCAT("") as RESO_FEC_DUP_NUE'),
        'cronograma_carpeta.fecha_colacion AS DIPL_FEC_ORG', //FECHA DE COLACIÓN
        DB::raw('CONCAT("") as DIPL_FECHA_DUP_NUEVO'),

        'tramite_detalle.codigo_diploma AS DIPL_NUM',
        DB::raw('CONCAT("O") as DIPL_TIP_EMI'),// HACERLO DINÁMICO CON UN NUEVO CAMPO
        'tramite_detalle.nro_libro AS REG_LIBRO',
        'tramite_detalle.folio AS REG_FOLIO',
        'tramite_detalle.nro_registro AS REG_REGISTRO',
        DB::raw("(case 
                    when (select sexo from usuario where idTipo_usuario=12 and estado=1) = \"M\" then 'RECTOR' 
                    else 'RECTORA'
                end) AS CARGO1"),
        DB::raw('(select CONCAT(apellidos," ",nombres) from usuario where idTipo_usuario=12 and estado=1) AS AUTORIDAD1'),
        DB::raw('CONCAT("SECRETARIA GENERAL"," ",(case when (select cargo from usuario where idTipo_usuario=10 and estado=1) IS NULL then ""
                                                        else (select cargo from usuario where idTipo_usuario=10 and estado=1)
                                                  end)
                        ) AS CARGO2'),
        DB::raw('(select CONCAT(apellidos," ",nombres) from usuario where idTipo_usuario=10 and estado=1) AS AUTORIDAD2'),
        DB::raw("(case 
                    when tramite.idUnidad = 1 then (case 
                                                        when (select sexo from usuario where idTipo_usuario=6 and estado=1 and idDependencia=tramite.idDependencia and estado=1) = \"M\" then 
                                                            (case 
                                                                when (select cargo from usuario where idTipo_usuario=6 and estado=1 and idDependencia=tramite.idDependencia and estado=1) IS NOT NULL then 'DECANO (E)'
                                                                else 'DECANO'
                                                            end)
                                                        else (case 
                                                                when (select cargo from usuario where idTipo_usuario=6 and estado=1 and idDependencia=tramite.idDependencia and estado=1) IS NOT NULL then 'DECANA (E)'
                                                                else 'DECANA'
                                                            end)
                                                    end) 
                    when tramite.idUnidad = 4 then  (case 
                                                        when (select sexo from usuario where idTipo_usuario=6 and estado=1 and idDependencia=dependencia.idDependencia2 and estado=1) = \"M\" then 
                                                            (case 
                                                                when (select cargo from usuario where idTipo_usuario=6 and estado=1 and idDependencia=dependencia.idDependencia2 and estado=1) IS NOT NULL then 'DECANO (E)'
                                                                else 'DECANO'
                                                            end)
                                                        else (case 
                                                                when (select cargo from usuario where idTipo_usuario=6 and estado=1 and idDependencia=dependencia.idDependencia2 and estado=1) IS NOT NULL then 'DECANA (E)'
                                                                else 'DECANA'
                                                            end)
                                                    end) 
                end) AS CARGO3"),
        DB::raw("(case 
                    when tramite.idUnidad = 1 then (select CONCAT(apellidos,' ',nombres) from usuario where idTipo_usuario=6 and idDependencia=tramite.idDependencia and estado=1) 
                    when tramite.idUnidad = 4 then  (select CONCAT(apellidos,' ',nombres) from usuario where idTipo_usuario=6 and idDependencia=dependencia.idDependencia2 and estado=1)
                end) AS AUTORIDAD3"),
        DB::raw('CONCAT("") AS PROC_PAIS_EXT'),
        DB::raw('CONCAT("") AS PROC_UNIV_EXT'),
        DB::raw('CONCAT("") AS PROC_GRADO_EXT'),
        DB::raw('CONCAT("") AS REG_OFICIO'),
        DB::raw('CONCAT("") AS FEC_MAT_MOD'),
        DB::raw('CONCAT("") AS FEC_INICIO_MOD'),
        DB::raw('CONCAT("") AS FEC_FIN_MOD'),
        'modalidad_carpeta.modalidad_sustentacion AS MOD_SUSTENTACION'
        // ---------------------------------------------------------------------

        // DB::raw("(case 
        //             when tipo_tramite_unidad.idTipo_tramite_unidad = 15 then 'BACHILLER' 
        //             when tipo_tramite_unidad.idTipo_tramite_unidad = 16 then 'TITULO PROFESIONAL'
        //             when tipo_tramite_unidad.idTipo_tramite_unidad = 34 then 'TITULO DE SEGUNDA ESPECIALIDAD PROFESIONAL'
        //         end)"),
        
        // DB::raw('CONCAT("") as proc_titulo_ped'),
        
        // DB::raw('CONCAT("") as FEC_INICIO_MOD_TIT_ACREDIT'),DB::raw('CONCAT("") as FEC_FIN_MOD_TIT_ACREDIT'),
        // 'tramite_detalle.fecha_sustentacion_carpeta'
        )
        ->join('tramite_detalle','tramite.idTramite_detalle','tramite_detalle.idTramite_detalle') 
        ->join('modalidad_carpeta','modalidad_carpeta.idModalidad_carpeta','tramite_detalle.idModalidad_carpeta')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('programa','programa.idPrograma','tramite.idPrograma')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('diploma_carpeta','diploma_carpeta.idDiploma_carpeta','tramite_detalle.idDiploma_carpeta')
        ->join('programa_estudios_carpeta','programa_estudios_carpeta.idPrograma_estudios_carpeta','tramite_detalle.idPrograma_estudios_carpeta')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
        ->join('oficio','oficio.idOficio','resolucion.idOficio')
        ->leftJoin('universidad','tramite_detalle.idUniversidad','universidad.idUniversidad')
        ->leftJoin('acreditacion','acreditacion.idAcreditacion','tramite_detalle.idAcreditacion')
        ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
        ->where(function($query)
        {
            $query->where('tramite.idEstado_tramite',42)
            ->orWhere('tramite.idEstado_tramite',44)
            ->orWhere('tramite.idEstado_tramite',15);
        })
        // ->where('tramite.idEstado_tramite',42)
        ->where('oficio.idOficio',$this->idOficio)
        ->orderBy('tramite.idTipo_tramite_unidad','asc')
        ->orderBy('tramite.idPrograma','asc')
        ->orderBy('usuario.apellidos','asc')
        ->orderBy('usuario.nombres','asc')
        // ->selectRaw("case 
        //                 when tipo_tramite_unidad.idTipo_tramite_unidad = 15 then 'BACHILLER' 
        //                 when tipo_tramite_unidad.idTipo_tramite_unidad = 16 then 'TÍTULO PROFESIONAL'
        //             end")
        // ->selectRaw("case when tipo_tramite_unidad.idTipo_tramite_unidad = 16 then 'TÍTULO PROFESIONAL' end")
        ->get();
        

         return $tramites;

    }


}
