<style type="text/css">
<!--
    table.page_header {width: 100%; border: none; background-color: #DDDDFF; border-bottom: solid 1mm #AAAADD; padding: 2mm }
    table.page_footer {width: 100%; border: none; background-color: #DDDDFF; border-top: solid 1mm #AAAADD; padding: 2mm}
    table.page_content {width: 100%;border: none; padding: 2mm }
-->
</style>
<page backtop="14mm" backbottom="0mm" backleft="10mm" backright="10mm">
    <table class="page_content" border="0">
        <tr border="0">
            <td style="width: 100%; text-align: left;" colspan="5">
                <?php if ($opcFoto == 1){ ?>
                    <img src="../../Fotos/<?php echo $fotoAlumno; ?>" align="right" style="margin-left: 20px; margin-right: 40px;  margin-top:115px; padding: 5px; width: 129px; height: 170px;">
                <?php }else{ ?>
                    <img src="avatar2.png" align="right" style="margin-left: 20px; margin-right: 40px;  margin-top:115px; padding: 5px; width: 129px; height: 170px;">
                <?php } ?>
                <p style="text-indent: 70px; text-align: justify; font-size:16px">
                    &nbsp;<br>
                    &nbsp;<br>
                    &nbsp;<br>
                    &nbsp;<br>
                    &nbsp;<br>
                    &nbsp;<br>
                </p>
                <p style="text-align: justify; margin-left: 2px; margin-top: 6px; margin-bottom: 1px; font-size:22px;"><b>El Rector de la Universidad Nacional de Trujillo</b></p>
                <p style="text-indent: 50px; text-align: justify;  font-family: Times; font-size:18px; margin-bottom: -5px;">
                    Por cuanto:</p>
                <p style="text-indent: 50px; text-align: justify;  font-size:19px; line-height: 34px; ">
                    EL CONSEJO UNIVERSITARIO DE ESTA UNIVERSIDAD, en la fecha, ha conferido el
                        <?php 
                        if(substr($diploma, 0,1) == 'B' || substr($diploma, 0,1) == 'M' || substr($diploma, 0,1) == 'D'){
                            echo " GRADO ACADÉMICO ";
                        }else{
                            if($escuela=='SEGUNDA ESPECIALIDAD EN ENFERMERÍA' || $escuela=='PROGRAMA DE SEGUNDA ESPECIALIDAD EN EDUCACIÓN INICIAL')
                                echo " TÍTULO ";
                            else
                                echo " TÍTULO PROFESIONAL ";
                        }
                        ?>de :
                </p>
                <p style="text-align: center; margin-bottom:5px; margin-top: 15px;">
<!--                    <b><?php //echo $denominacion;?></b>-->
                    <b>
                        <?php 
                            if($posicion_coincidencia){
                                $primeraParte = substr($denominacion, 0, $posicion_coincidencia);
                                echo "<p style='font-size:32px; margin-top: -6px;'>".$primeraParte.'</p><br>';
                                $segundaParte = substr($denominacion, $posicion_coincidencia);
                                echo "<p style='font-size:23px;margin-top: -36px;;margin-bottom: -10px;'>".$segundaParte."</p>";
                            }else{
                                echo "<p style='font-size:37px;margin-top: -10px;'>".$denominacion."</p>";
                            }
                        ?></b>
                </p>
                <?php if ($diploma == 'T141' || $diploma == 'T142' || $diploma == 'T143' || $diploma == 'T144' || $diploma == 'T168' || $diploma == 'T055' || $diploma == 'T045' || $diploma == 'T138' || $diploma == 'T195' || $diploma == 'T108' || $diploma == 'T148' || $diploma == 'T151' || $diploma == 'T164' || $diploma == 'T196' || $diploma == 'T197' || $diploma == 'T154' || $diploma == 'T152' || $diploma == 'M137') {?>
                    <p style="font-size:20px; text-align: center; margin-top: -22px;">
                <?php }else {?>   
                    <p style="font-size:20px; text-align: center; ">
                <?php } ?>     
                <div style="float:right; margin-left: -532mm; ">a:&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <!--<font style="font-size:45px; font-family: brushib; margin-top:-10mm; "><b><?php //echo $nombreComp;?></b></font>-->
                <font style="font-size:31px; font-family: coopblb; margin-top:-8mm; "><b><?php echo $nombreComp;?></b></font>
                </p>
                <p style="text-align: justify; text-indent: 0px; margin-bottom: 18px; margin-top: -35px; font-size:18px">
                    De la <b><?php echo $facultad ?></b>,
                    <b>
                        <?php if ($idFicha==1 || $idFicha==2){?>ESCUELA PROFESIONAL DE <?php }?>
                        <?php
                            if ($escuela=='RESIDENTADO MÉDICO' || $escuela=='SEGUNDA ESPECIALIDAD EN ENFERMERÍA' || $escuela=='SEGUNDA ESPECIALIDAD EN CIENCIAS BIOLÓGICAS' || $escuela=='TECNOLOGÍA EDUCATIVA' || $escuela=='ESTIMULACIÓN TEMPRANA' || $escuela=='PROGRAMA DE SEGUNDA ESPECIALIDAD EN EDUCACIÓN INICIAL' || $escuela=='SEGUNDA ESPECIALIDAD EN FARMACIA Y BIOQUÍMICA' || $escuela=='SEGUNDA ESPECIALIDAD EN ESTOMATOLOGÍA') {
                                echo buscarDenominaciones($escuela, $diploma);
                            }else{
                                echo $escuela;
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
            </td>
        </tr>
        <tr style="margin-bottom: -5px;" valign="top">
            <td style="width: 30%; text-align: center;margin-top: 8px">
                <hr style=" width:80%;border-bottom: 0px dashed #ccc; background: #999;">
                SECRETARIA GENERAL (E)<br>
                <b><?php echo $decano?></b>
            </td>
            <td style="width: 5%; text-align: center;margin-top: 8px">
               &nbsp; 
            </td>
            <td style="width: 30%; text-align: center;">
                <hr style="width:80%; margin-bottom: 5px; border-bottom: 0px dashed #ccc; background: #999;">
                RECTOR<br>
                <b><?php echo $secretario?></b>
            </td>
            <td style="width: 5%; text-align: center;margin-top: 8px">
                &nbsp;
            </td>
            <td style="width: 30%; text-align: center;">
                <hr style="margin-bottom: 5px; border-bottom: 0px dashed #ccc; background: #999;">
                <?php echo $rectorCargo?><br>
                <b><?php echo $rector?></b>
            </td>
        </tr>
    </table>
</page>
<page pageset="old">
    <br>
    <table>
        <tr>
            <td style="width: 45mm; text-align: center; margin-left: 10px;">
                <qrcode value="<?php echo $msg; ?>" ec="L" style="width: 30mm; margin-top: -4px; margin-left: -51px; background-color: white; color: #251e9b;"></qrcode><br><br>
                <!--<span style="margin-left: -60px;"> C&oacute;digo para verificar validez del Documento </span>   251e9b-->
            </td>
            <td style="width: 130mm;">
                <div style="margin-top: -20px; margin-left: -40px;">
                    <p  style="font-size: 11px; margin-top: -0px; line-height: 15px;">
                        CÓDIGO DE UNIVERSIDAD :<b> 004</b><br>
                        REGISTRADO EN EL LIBRO DE
                        <?php 
                        if(substr($diploma, 0,1) == 'B' || substr($diploma, 0,1) == 'M' || substr($diploma, 0,1) == 'D'){
                            echo " GRADOS ";
                        }else{
                            echo " TÍTULOS ";
                        }
                        ?>
                        Nº:<b> <?php echo $nrolibro?> </b> <br>
                        EN EL FOLIO Nº:<b> <?php echo $folio;?></b> <br>
                        REGISTRO Nº: <b><?php echo $nroRegistro; ?> </b> DE SECRETARIA GENERAL <br>
                        TIPO DOCUMENTO:<b> <?php echo $tipoDocumento; ?></b> N° DOCUMENTO: <b> <?php echo $_POST['nroDoc']?></b> <br>
                        DIPLOMA OBTENIDO: <b><?php echo $tipoFicha; ?></b> <br>
                        OBTENIDO POR: <b><?php echo $tipoActo;?></b><br>
                        MODALIDAD DE ESTUDIOS: <b> PRESENCIAL</b>
                    </p>
                </div>
            </td>
            <td>
                <p  style="font-size: 11px; margin-top: -60px;">
                    RESOLUCIÓN <?php if(isset($_POST['idgraduadoDup'])) { echo "RECTORAL"; }else{ echo "DE CONSEJO UNIVERSITARIO"; } ?> Nº :<b> <?php echo $numResolucionUniv; ?></b><br>
                    FECHA RESOLUCIÓN <?php if(isset($_POST['idgraduadoDup'])) { echo "RECTORAL"; }else{ echo "DEL CONSEJO UNIVERSITARIO"; } ?>: <b> <?php echo $fechaResolucionCU; ?></b><br>
                    EMISIÓN DE DIPLOMA:  <b><?php echo $diplomasEstado; ?></b><br>
                    <?php if($diplomasEstado=='DUPLICADO') {
                        $date = date_create($fechaEmision);
                        echo "FECHA DE EMISIÓN: ";?><b>
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
                    &nbsp;____________________________________<br>
                    <p style="font-size: 11px; text-align: right; margin-right: -15px;">
                        Firma del Interesado</p>
                </div>
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
</page>