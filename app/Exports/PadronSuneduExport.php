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
class PadronSuneduExport implements FromCollection,WithHeadings,ShouldAutoSize, WithEvents
{
    public $idOficio;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct($idOficio){
        $this->idOficio = $idOficio;
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cellRange = '1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);;
            },
        ];
    }
    public function headings(): array
    {
        return [
            'CODUNIV',
            'RAZ_SOC',
            'MATRI_FEC',
            'FAC_NOM',
            'CARR_PROG',
            'ESC_POS',
            'EGRES_FEC',
            'APEPAT',
            // 'APEMAT',
            'NOMBRE',
            'SEXO',
            'DOCU_TIP',
            'DOCU_NUM',
            'PROC_BACH',
            'GRAD_TITU',
            'DEN_GRAD',
            'SEG_ESP',
            'TRAB_INV',
            'NUM_CRED',
            'REG_METADATO',
            'PROG_ESTU',
            'PROC_TITULO_PED',
            'MOD_OBT',
            'PROG_ACREDIT',
            'FEC_INICIO_ACREDIT',
            'FECHA_FIN_ACREDIT',
            'FEC_INICIO_MOD_TIT_ACREDIT',
            'FEC_FIN_MOD_TIT_ACREDIT',
            'FEC_SOLICIT_GRAD_TIT',
            'FEC_TRAB_GRAD_TIT',
            'TRAB_INVEST_ORIGINAL',
            'MOD_EST',
            'ABRE_GYT',
            'PROC_REV_PAIS',
            'PROC_REV_UNIV',
            'PROC_REV_GRADO',
            'CRIT_REV',
            'RESO_NUM',
            'RESO_FEC',
            'DIP_FEC_ORG',
            'DIP_FECHA_DUP',
            'DIP_NUM',
            'DIP_TIP_EMI',
            'REG_LIBRO',
            'REG_FOLIO',
            'REG_REGISTRO',
            'CARGO 1',
            'AUTORIDAD 1',
            'CARGO 2',
            'AUTORIDAD 2',
            'CARGO 3',
            'AUTORIDAD 3',
            'PROC_PAIS_EXT',
            'PROC_UNIV_EXT',
            'PROC_GRADO_EXT',
            'REG_OFICIO',
            'FEC_MAT_PROG',
            'FEC_INICIO-PROG',
            'FEC_FIN_PROG',
            'MOD_SUSTENTACION'

        ];
    }
    public function collection()
    {
        $tramites = Tramite::select( DB::raw('CONCAT("004")'),DB::raw('CONCAT("UNIVERSIDAD NACIONAL DE TRUJILLO")'),'tramite_detalle.fecha_primera_matricula',
        DB::raw("(case 
                    when tramite.idUnidad = 1 then dependencia.nombre  
                    when tramite.idUnidad = 4 then  (select nombre from dependencia d where d.idDependencia=dependencia.idDependencia2)
                end)"),
        // 'dependencia.nombre',
        DB::raw("(case 
                    when tramite.idUnidad = 1 then (select nombre from escuela where idEscuela=tramite.idDependencia_detalle)  
                    when tramite.idUnidad = 4 then  (select denominacion from mencion where idMencion=tramite.idDependencia_detalle)
                end)"),
        // DB::raw('CONCAT("escuela")'),
        DB::raw('CONCAT("") as escp_pos'),'tramite_detalle.fecha_ultima_matricula','usuario.apellidos','usuario.nombres','usuario.sexo','usuario.tipo_documento','usuario.nro_documento',DB::raw('CONCAT("00","4")'),
        DB::raw("(case 
                    when tipo_tramite_unidad.idTipo_tramite_unidad = 15 then 'BACHILLER' 
                    when tipo_tramite_unidad.idTipo_tramite_unidad = 16 then 'TITULO PROFESIONAL'
                    when tipo_tramite_unidad.idTipo_tramite_unidad = 34 then 'TITULO DE SEGUNDA ESPECIALIDAD PROFESIONAL'
                end)"),
        'diploma_carpeta.descripcion as den_grado',DB::raw('CONCAT("") as seg_esp'),'tramite_detalle.nombre_trabajo_carpeta','tramite_detalle.nro_creditos_carpeta','tramite_detalle.url_trabajo_carpeta',
        'programa_estudios_carpeta.descripcion as prog_estu',DB::raw('CONCAT("") as proc_titulo_ped'),'modalidad_carpeta.acto_academico',
        DB::raw("(case 
                    when tramite_detalle.idAcreditacion != null then 'SI' 
                    else 'NO'
                end)"),
        'acreditacion.fecha_inicio','acreditacion.fecha_fin',DB::raw('CONCAT("") as FEC_INICIO_MOD_TIT_ACREDIT'),DB::raw('CONCAT("") as FEC_FIN_MOD_TIT_ACREDIT'),'tramite_detalle.fecha_inicio_acto_academico'
        ,'tramite_detalle.fecha_sustentacion_carpeta',DB::raw('CONCAT("SI") as TRAB_INVEST_ORIGINAL'),DB::raw('CONCAT("P") as MOD_EST'),DB::raw('substr(tipo_tramite_unidad.diploma_obtenido,1, 1)'),DB::raw('CONCAT("") as PROC_REV_PAIS'),
        DB::raw('CONCAT("") as PROC_REV_UNIV'),DB::raw('CONCAT("") as PROC_REV_GRADO'),DB::raw('CONCAT("") as CRIT_REV'),'resolucion.nro_resolucion','resolucion.fecha','cronograma_carpeta.fecha_colacion',
        DB::raw('CONCAT("") as DIP_FECHA_DUP'),'tramite_detalle.codigo_diploma',DB::raw('CONCAT("O") as DIP_TIP_EMI'),'tramite_detalle.nro_libro','tramite_detalle.folio','tramite_detalle.nro_registro',
        DB::raw("(case 
                    when (select sexo from usuario where idTipo_usuario=12 and estado=1) = \"M\" then 'RECTOR' 
                    else 'RECTORA'
                end)"),
        // DB::raw('CONCAT("RECTOR")'),
        DB::raw('(select CONCAT(apellidos," ",nombres) from usuario where idTipo_usuario=12 and estado=1) as rector'),
        DB::raw('CONCAT("SECRETARIA GENERAL"," ",(case when (select cargo from usuario where idTipo_usuario=10 and estado=1) IS NULL then ""
                                                        else (select cargo from usuario where idTipo_usuario=10 and estado=1)
                                                  end)
                        )'),
        DB::raw('(select CONCAT(apellidos," ",nombres) from usuario where idTipo_usuario=10 and estado=1) as secretaria_general'),
        DB::raw("(case 
                    when tramite.idUnidad = 1 then (case 
                                                        when (select sexo from usuario where idTipo_usuario=6 and estado=1 and idDependencia=tramite.idDependencia and estado=1) = \"M\" then 'DECANO' 
                                                        else 'DECANA'
                                                    end) 
                    when tramite.idUnidad = 4 then  (case 
                                                        when (select sexo from usuario where idTipo_usuario=6 and estado=1 and idDependencia=dependencia.idDependencia2 and estado=1) = \"M\" then 'DECANO' 
                                                        else 'DECANA'
                                                    end) 
                end)"),
        // DB::raw("(case 
        //             when (select sexo from usuario where idTipo_usuario=6 and estado=1 and idDependencia=tramite.idDependencia and estado=1) = \"M\" then 'DECANO' 
        //             else 'DECANA'
        //         end)"),
        // DB::raw('CONCAT("DECANA")'),
        DB::raw("(case 
                    when tramite.idUnidad = 1 then (select CONCAT(apellidos,' ',nombres) from usuario where idTipo_usuario=6 and idDependencia=tramite.idDependencia and estado=1) 
                    when tramite.idUnidad = 4 then  (select CONCAT(apellidos,' ',nombres) from usuario where idTipo_usuario=6 and idDependencia=dependencia.idDependencia2 and estado=1)
                end)"),
        // DB::raw('(select CONCAT(apellidos," ",nombres) from usuario where idTipo_usuario=6 and idDependencia=tramite.idDependencia and estado=1) as decano_a'),
        DB::raw('CONCAT("") as PROC_PAIS_EXT'),
        DB::raw('CONCAT("") as PROC_UNIV_EXT'),
        DB::raw('CONCAT("") as PROC_GRADO_EXT'),
        DB::raw('oficio.nro_oficio as REG_OFICIO'),
        DB::raw('CONCAT("") as FEC_MAT_PROG'),
        DB::raw('CONCAT("") as FEC_INICIO_PROG'),
        DB::raw('CONCAT("") as FEC_FIN_PROG'),
        'modalidad_carpeta.modalidad_sustentacion'
        )
        ->join('tramite_detalle','tramite.idTramite_detalle','tramite_detalle.idTramite_detalle') 
        ->join('modalidad_carpeta','modalidad_carpeta.idModalidad_carpeta','tramite_detalle.idModalidad_carpeta')
        ->join('dependencia','dependencia.idDependencia','tramite.idDependencia')
        ->join('usuario','usuario.idUsuario','tramite.idUsuario')
        ->join('tipo_tramite_unidad','tipo_tramite_unidad.idTipo_tramite_unidad','tramite.idTipo_tramite_unidad')
        ->join('diploma_carpeta','diploma_carpeta.idDiploma_carpeta','tramite_detalle.idDiploma_carpeta')
        ->join('programa_estudios_carpeta','programa_estudios_carpeta.idPrograma_estudios_carpeta','tramite_detalle.idPrograma_estudios_carpeta')
        ->join('cronograma_carpeta','cronograma_carpeta.idCronograma_carpeta','tramite_detalle.idCronograma_carpeta')
        ->join('resolucion','resolucion.idResolucion','cronograma_carpeta.idResolucion')
        ->join('oficio','resolucion.idOficio','oficio.idOficio')
        ->leftJoin('acreditacion','acreditacion.idAcreditacion','tramite_detalle.idAcreditacion')
        ->where(function($query)
            {
                $query->where('tramite.idTipo_tramite_unidad',15)
                ->orWhere('tramite.idTipo_tramite_unidad',16)
                ->orWhere('tramite.idTipo_tramite_unidad',34);
            })
        ->where('tramite.idEstado_tramite',44)
        // ->where('tramite.idDependencia',8)
        // ->where(function($query)
        //     {
        //         $query->where('tramite.idDependencia_detalle',9)
        //         ->orWhere('tramite.idDependencia_detalle',11)
        //         ->orWhere('tramite.idDependencia_detalle',47);
        //     })
        ->where('oficio.idOficio',$this->idOficio)
        ->orderBy('tramite.idTipo_tramite_unidad','asc')
        ->orderBy('tramite.idDependencia_detalle','asc')
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
