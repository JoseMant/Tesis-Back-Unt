<?php
/**
 * HTML2PDF Librairy - example
 *
 * HTML => PDF convertor
 * distributed under the LGPL License
 *
 * @author      Laurent MINGUET <webmaster@html2pdf.fr>
 *
 * isset($_GET['vuehtml']) is not mandatory
 * it allow to display the result in the HTML format
 */
// session_start();
// include_once "denominaciones.php";

// if(!isset($_POST['codigoAlumno'])){
//     session_destroy();
//     header("Location:../../../index.php");
// }
// $id = $_POST['codigoAlumno'];
// $diploma = $_POST['diploma'];//cod. diploma
// $denominacion = $_POST['denominacion'];
$cadena_de_texto =$tramite->denominacion;
$cadena_buscada   = ' MENCIÓN:';
$posicion_coincidencia = strpos($cadena_de_texto, $cadena_buscada);

$fecha = DATE($tramite->fecha_colacion);
$año = substr($fecha, 0, 4);
$mes = (int)substr($fecha, 5, 2);
$dia = substr($fecha, 8, 2);

// ob_start();
$msg = "tramites-uraa.unitru.edu.pe/carpeta/".$tramite->idTramite;
// $dias = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","SÃ¡bado");
$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");



// LÃ“GICA PARA SACAR LA FACULTAD DE LAS SEGUNDAS ESPECIALIDADES
$dependencia=$tramite->dependencia;
$programa=$tramite->programa;


// LÃ“GICA PARA EL TIPO DE DOCUMENTO
$tipoDocumento="";
if ($tramite->tipo_documento==1) {
    $tipoDocumento="DNI";
}if ($tramite->tipo_documento==2) {
    $tipoDocumento="PAS";
}if ($tramite->tipo_documento==3) {
    $tipoDocumento="CE";
}if ($tramite->tipo_documento==4) {
    $tipoDocumento="CI";
}if ($tramite->tipo_documento==5) {
    $tipoDocumento="DE";
}if ($tramite->tipo_documento==6) {
    $tipoDocumento="CTP";
}if ($tramite->tipo_documento==7) {
    $tipoDocumento="CIP";
}



?>


<style type="text/css">
    
        table.page_header {width: 100%; border: none; background-color: #DDDDFF; border-bottom: solid 1mm #AAAADD; padding: 2mm }
        table.page_footer {width: 100%; border: none; background-color: #DDDDFF; border-top: solid 1mm #AAAADD; padding: 2mm}
        table.page_content {width: 100%;border: none; padding: 2mm }
        #cara1{margin-top: -20px;margin-left: -20px; padding-right:20px ;padding-bottom:50px ;
            /* background-image: url(<?php echo public_path('\img')."\\fondo_degradado.png"; ?>); */
            height: 100%;
        }
        #cara2{
            /* background-image:url(<?php echo public_path('\img')."\\fondo_degradado.png"; ?>);  */
            width: 100%; 
            height: auto; position:  absolute; 
            margin-top: -20px;margin-left: -20px; padding-right:23px ;padding-bottom:160px ;
            
        }
    
</style>
<div id="cara1">
    <img src=<?php echo public_path()."/img/cabecera_vacia_diploma.png"; ?> style="width: 1060px; height: 131px; position: absolute; top: 10px; left: 40px">
    <div style="margin-top: 0mm; margin-bottom: 0mm; margin-left: 10mm; margin-right: 10mm">
        <table class="page_content" border="0">
            <tr border="0">
                <td style="width: 100%; text-align: left;" colspan="5">
                    <?php if ($opcFoto == 1){ ?>
                        <?php if ($tramite->idTipo_tramite_unidad == 34){ ?>
                            <img src="<?php echo public_path().$fotoAlumno?>" align="right" style="margin-left: 20px; margin-right: 40px;  margin-top:30px; padding: 5px; width: 129px; height: 170px;">
                        <?php }else{ ?>
                            <img src="<?php echo public_path().$fotoAlumno?>" align="right" style="margin-left: 20px; margin-right: 40px;  margin-top:30px; padding: 5px; width: 129px; height: 170px;">
                        <?php } ?>
                    <?php }else{ ?>
                        <img src="avatar2.png" align="right" style="margin-left: 20px; margin-right: 40px;  margin-top:27px; padding: 5px; width: 129px; height: 170px;">
                    <?php } ?>
                    <!-- <p style="text-align: center; font-family: Times; font-size: 25px; font-weight: bold; margin-top: 5px; margin-bottom: 15px; margin-left: 214px">A NOMBRE DE LA NACIÃ“N</p> -->
                    <p style="text-align: center; font-family: Times; font-size: 25px; font-weight: bold; margin-top: 5px; margin-bottom: 15px; margin-left: 214px">&nbsp;</p>
                    <p style="text-align: justify; margin-left: 2px; margin-top: 6px; margin-bottom: 1px; font-size:22px;"><b>El Rector de la Universidad Nacional de Trujillo</b></p>
                    <p style="text-indent: 50px; text-align: justify;  font-family: Times; font-size:18px; margin-bottom: -5px;">
                        Por cuanto:</p>
                    <p style="text-indent: 50px; text-align: justify;  font-size:19px; line-height: 34px; ">
                        EL CONSEJO UNIVERSITARIO DE ESTA UNIVERSIDAD, en la fecha, ha conferido el
                            <?php 
                            if(substr($tramite->diploma, 0,1) == 'B' || substr($tramite->diploma, 0,1) == 'M' || substr($tramite->diploma, 0,1) == 'D'){
                                echo " GRADO ACADÉMICO ";
                            }else{
                                if($programa=='SEGUNDA ESPECIALIDAD EN ENFERMERÍA' || $programa=='PROGRAMA DE SEGUNDA ESPECIALIDAD EN EDUCACIÓN INICIAL')
                                    echo " TÍTULO ";
                                else
                                    echo " TÍTULO PROFESIONAL ";
                            }
                            ?>de :
                    </p>
                    <?php if ($idFicha==1 || $idFicha==2){?>
                        <p style="text-align: center; margin-bottom:5px; margin-top: 15px;">
                    <!-- <b><?php //echo $denominacion;?></b> -->
                            <b>
                                <?php 
                                    if($posicion_coincidencia){
                                        $primeraParte = substr($tramite->denominacion, 0, $posicion_coincidencia);
                                        echo "<p style='font-size:32px; margin-top: -6px;'>".$primeraParte.'</p><br>';
                                        $segundaParte = substr($tramite->denominacion, $posicion_coincidencia);
                                        echo "<p style='font-size:23px;margin-top: -36px;;margin-bottom: -10px;'>".$segundaParte."</p>";
                                    }else{
                                        echo "<p style='font-size:35px;margin-top: -10px;'>".$tramite->denominacion."</p>";
                                    }
                                ?></b>
                        </p>
                    <?php }?>

                    <?php if ($idFicha==4){?>
                        <p style="text-align: center; margin-bottom:5px; margin-top: 15px;">
                    <!-- <b><?php //echo $denominacion;?></b> -->
                            <b>
                                <?php 
                                    if($posicion_coincidencia){
                                        $primeraParte = substr($tramite->denominacion, 0, $posicion_coincidencia);
                                        echo "<p style='font-size:25px; margin-top: -6px;'>".$primeraParte.'</p><br>';
                                        $segundaParte = substr($tramite->denominacion, $posicion_coincidencia);
                                        echo "<p style='font-size:23px;margin-top: -36px;;margin-bottom: -10px;'>".$segundaParte."</p>";
                                    }else{
                                        echo "<p style='font-size:25px;margin-top: -10px;margin-bottom: 10px'>".$tramite->denominacion."</p>";
                                    }
                                ?></b>
                        </p>
                    <?php }?>

                    <?php if ($diploma == 'T141' || $diploma == 'T142' || $diploma == 'T143' || $diploma == 'T144' || $diploma == 'T168' || $diploma == 'T055' || $diploma == 'T045' || $diploma == 'T138' || $diploma == 'T195' || $diploma == 'T108' || $diploma == 'T148' || $diploma == 'T151' || $diploma == 'T164' || $diploma == 'T196' || $diploma == 'T197' || $diploma == 'T154' || $diploma == 'T152' || $diploma == 'M137') {?>
                        <p style="font-size:20px; text-align: center; margin-top: -22px;">
                    <?php }else {?>   
                        <p style="font-size:20px; text-align: center; ">
                    <?php } ?>   

                    <div style="float:right; margin-left: -532mm; ">a:&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <!--<font style="font-size:45px; font-family: brushib; margin-top:-10mm; "><b><?php //echo $nombreComp;?></b></font>-->

                    <?php if ($idFicha==1 || $idFicha==2){?>
                        <font style="font-size:31px; /*font-family: coopblb;*/ font-family: arial; margin-top:-8mm; "><b><?php echo $tramite->nombreComp;?></b></font>
                        </p>
                    <?php }?>
                    <?php if ($idFicha==4){?>
                        <font style="font-size:25px; /*font-family: coopblb;*/ font-family: arial; margin-top:-8mm; "><b><?php echo $tramite->nombreComp;?></b></font>
                        </p>
                    <?php }?>

                    <p style="text-align: justify; text-indent: 0px; margin-bottom: 18px; margin-top: -35px; font-size:18px">
                        De la <b><?php echo $dependencia ?>,</b>
                        <b>
                            <?php
                            if ($programa=='RESIDENTADO MÉDICO' || $programa=='SEGUNDA ESPECIALIDAD EN ENFERMERÍA' || $programa=='SEGUNDA ESPECIALIDAD EN CIENCIAS BIOLÓGICAS' || $programa=='TECNOLOGÍA EDUCATIVA' || $programa=='ESTIMULACIÓN TEMPRANA' || $programa=='PROGRAMA DE SEGUNDA ESPECIALIDAD EN EDUCACIÓN INICIAL' || $programa=='SEGUNDA ESPECIALIDAD EN FARMACIA Y BIOQUÍMICA' || $programa=='SEGUNDA ESPECIALIDAD EN ESTOMATOLOGÍA') {
                                echo buscarDenominaciones($programa, $diploma);
                            }else{
                                echo $programa;
                            }
                            ?>
                        </b>
                        <?php if ($idFicha==7){?> - <b>EDUCACIÓN <?php echo $nombre_escuela_preford ?></b> <?php }?>
                    </p>




                    <p style="text-align: justify; text-indent: 50px; font-family: Times;  margin-bottom: -3px; margin-top: -9px; font-size:18px">
                        Por tanto:</p>
                    <p style="text-align: justify; text-indent: 50px; font-size:18px; font-family: Times; line-height: 20px;">
                        Le expido el presente DIPLOMA para que se le reconozca como tal y se le otorgue los goces y privilegios que le 
                        confieren las Leyes de la República.
                    </p>
                    <p style="text-align: right;  margin-top: -12px; font-size:16px; font-family: Times;">
                        Trujillo, <?php echo $dia." de ".$meses[$mes-1]. " de ".$año; ?>
                    </p>
                </td>
            </tr>
            <tr>
                <td style="width: 100%; text-align: left;" colspan="5">
                    &nbsp;<br>
                    &nbsp;<br>
                    &nbsp;<br>
                    &nbsp;<br>
                </td>
            </tr>
            <tr style="margin-bottom: -5px;" valign="top">
                <td style="width: 30%; text-align: center;margin-top: 8px">
                    <?php if ($secretaria->sexo=='M'){?>SECRETARIO GENERAL<?php }?>  
                    <?php if ($secretaria->sexo=='F'){?>SECRETARIA GENERAL<?php }?>  
                    <?php echo $secretaria->cargo?>
                    <br>
                    <b><?php  if ($secretaria->grado){echo $secretaria->grado.". ";} echo $secretaria->nombres?></b>
                </td>
                <td style="width: 5%; text-align: center;margin-top: 8px">
                &nbsp; 
                </td>
                <td style="width: 30%; text-align: center;">
                    <?php if ($rector->sexo=='M'){?>RECTOR<?php }?>    
                    <?php if ($rector->sexo=='F'){?>RECTORA<?php }?>  
                    <!-- RECTOR(A) -->
                    <br>
                    <b><?php if ($rector->grado){echo $rector->grado.". ";} echo $rector->nombres?></b>
                </td>
                <td style="width: 5%; text-align: center;margin-top: 8px">
                    &nbsp;
                </td>
                <td style="width: 30%; text-align: center;">
                    <?php if ($decano->sexo=='M'){?>DECANO<?php }?>    
                    <?php if ($decano->sexo=='F'){?>DECANA<?php }?>    
                    <?php echo $decano->cargo?><br>
                    <b><?php  if ($decano->grado){echo $decano->grado.". ";} echo $decano->nombres?></b>
                </td>
            </tr>
        </table>
    </div>
</div>

<div id="cara2">
    <div style="margin-top: 14mm; margin-bottom: 0mm; margin-left: 10mm; margin-right: 10mm">
        <br>
        <table >
            <tr>
                <td style="width: 45mm; text-align: center; margin-left: 10px;">
                    <qrcode value="<?php echo $msg; ?>" ec="L" style="width: 30mm; margin-top: -4px; margin-left: -51px; background-color: white; color: #251e9b;"></qrcode><br><br>
                    <!--<span style="margin-left: -60px;"> C&oacute;digo para verificar validez del Documento </span>   251e9b-->
                </td>
                <td style="width: 130mm;">
                    <div style="margin-top: -5px; margin-left: -40px;">
                        <p  style="font-size: 11px; margin-top: 0px; line-height: 15px;">
                            CÓDIGO DE UNIVERSIDAD :<b> 004</b><br>
                            TIPO DOCUMENTO:<b> <?php echo $tipoDocumento; ?></b><br>
                            N° DOCUMENTO: <b> <?php echo $tramite->nro_documento?></b> <br>
                            ABREVIATURA GRADO/TÍTULO: <b><?php echo $tramite->diploma_obtenido; ?></b> <br>
                            MODALIDAD DE OBTENCIÓN: <b><?php echo $tramite->acto_academico;?></b><br>
                            MODALIDAD DE ESTUDIOS: <b> P - PRESENCIAL</b><br>

                            REGISTRADO EN EL LIBRO DE
                            <?php 
                            if(substr($tramite->diploma, 0,1) == 'B' || substr($tramite->diploma, 0,1) == 'M' || substr($tramite->diploma, 0,1) == 'D'){
                                echo " GRADOS ";
                            }elseif($idFicha==4 && substr($tramite->diploma, 0,1) == 'T'){
                                echo " TÍTULOS DE SEGUNDA ESPECIALIDAD";
                            }else {
                                echo " TÍTULOS";
                            }
                            ?>
                            Nº:<b> <?php echo $tramite->nro_libro?> </b> <br>
                            EN EL FOLIO Nº:<b> <?php echo $tramite->folio;?></b> <br>
                            REGISTRO Nº: <b><?php echo $tramite->nro_registro; ?> </b> DE SECRETARÍA GENERAL <br>
                        </p>
                    </div>
                </td>
                <td>
                    <p  style="font-size: 11px; margin-top: -5px;">
                        RESOLUCIÓN DE OTORGAMIENTO: <b> RCU N° <?php echo $tramite->nro_resolucion; ?></b><br>
                        FECHA DE RESOLUCIÓN DE OTORGAMIENTO: <b>
                            <?php 
                                $date=date_create($tramite->fecha_resolucion);
                                echo date_format($date,'d/m/Y');
                            ?>
                        </b><br>
                        CÓDIGO DEL DIPLOMA:  <b><?php echo $tramite->codigo_diploma; ?></b><br>
                        EMISIÓN DE DIPLOMA:  <b><?php echo $diplomasEstado; ?></b><br><br>
                        <?php if($diplomasEstado=='DUPLICADO') {
                            $date = date_create($fechaEmision);
                            echo "FECHA DE EMISIÃ“N: ";?><b>
                            <?php echo date_format($date,'d/m/Y');?></b>
                        <?php
                        }
                    ?>
                    </p>
                </td>
            </tr>
            <tr>
                <td>
                    <div>
                        <br><br><br><br><br><br><br>
                        <!-- &nbsp;____________________________________<br>
                        <p style="font-size: 11px; text-align: right; margin-right: -15px;">
                            Firma del Interesado</p> -->                    </div>
                </td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td colspan="3">
                    <div>
                        <br><br><br><br><br><br><br><br><br><br><br><br><br><br>

                        <br><br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                    </div>
                </td>
            </tr>
        </table>
        <br>
    </div>
</div>
