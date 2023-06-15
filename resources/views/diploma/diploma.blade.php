<?php
    // LÓGICA PARA LA FECHA IMPRESA
    $fecha = DATE($tramite->fecha_colacion);
    $año = substr($fecha, 0, 4);
    $mes = (int)substr($fecha, 5, 2);
    $dia = substr($fecha, 8, 2);
    $url_qr = "tramites-uraa.unitru.edu.pe/carpeta/".$tramite->idTramite;
    // $dias = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    // LÓGICA PARA EL TIPO DE DOCUMENTO
    $tipoDocumento="";
    switch ($tramite->tipo_documento) {
        case 1: 
            $tipoDocumento="DNI";
            break;
        case 2: 
            $tipoDocumento="PAS";
            break;
        case 3: 
            $tipoDocumento="CE";
            break;
        case 4: 
            $tipoDocumento="CI";
            break;
        case 5: 
            $tipoDocumento="DE";
            break;
        case 6: 
            $tipoDocumento="CTP";
            break;
        case 7: 
            $tipoDocumento="CIP";
            break;
    }
?>
<!DOCTYPE html>
<html>
<head>
	<title>Diploma UNT</title>
</head>
<style type="text/css">
    
        /* table.page_header {width: 100%; border: none; background-color: #DDDDFF; border-bottom: solid 1mm #AAAADD; padding: 2mm }
        table.page_footer {width: 100%; border: none; background-color: #DDDDFF; border-top: solid 1mm #AAAADD; padding: 2mm}
        table.page_content {width: 100%;border: none; padding: 2mm } */
        #cara1{
            margin: -20px;
            /* margin-left: -20px; 
            margin-top: -20px;  */
            padding: 170px 70px 50px;
            /* padding-bottom:50px; */
            background-image: url(<?php echo public_path('\img')."\\nueva_diploma_unt-1.jpg"; ?>);
            height: 100%;
            /* position:  absolute;  */
            /* width: 1125px; */
            background-repeat: no-repeat;
        }
        #cara2{
            margin: -20px;
            padding: 50px 70px 0 70px;
            background-image: url(<?php echo public_path('\img')."\\nueva_diploma_unt-2.png"; ?>);
            height: 100%;
            /* width: 104%; */
            /* height: auto;  */
            position:  absolute; 
            /* margin-top: -17px;
            margin-left: -17px; 
            padding-right:23px ;padding-bottom:160px ; */
            background-repeat: no-repeat;
            
        }
        h1 {
            text-align: center;
            font-family: Times; 
            font-size:27px;
            margin: 10px 0;
        }
        h2 {
            text-align: center;
            font-family: Times; 
            font-size:27px;
            margin: 0px 0;
        }
        p {
            text-align: justify;
            /* text-indent: 10px; */
            font-family: Times; 
            line-height: 120%;
            margin: 10px 0;
            font-size:20px;
        }
        #table1 {
            /* width: 318px; */
            /* font-family: Times; 
            text-align: center; */
            margin: 0;
            padding: 0;
            /* border: 1px; */
        }
        #table2 td {
            width: 318px;
            font-family: Times; 
            text-align: center;
            /* border: 1px; */
        }
        #cara2 table td {
            width: 131px;
            font-family: Times; 
            text-align: justify;
            /* border: 1px; */
        }
    
</style>
<body>
    <div id="cara1">
        <p>En nombre de la Nación, <b>La Universidad Nacional de Trujillo</b>, por medio del Consejo Universitario, confiere el
            <?php 
                if(substr($tramite->diploma_obtenido, 0,1) == 'B' || substr($tramite->diploma_obtenido, 0,1) == 'M' || substr($tramite->diploma_obtenido, 0,1) == 'D') {
                    echo " GRADO ACADÉMICO ";
                } else {
                //     if($escuela=='SEGUNDA ESPECIALIDAD EN ENFERMERÍA' || $escuela=='PROGRAMA DE SEGUNDA ESPECIALIDAD EN EDUCACIÓN INICIAL')
                //         echo " TÍTULO ";
                //     else
                        echo " TÍTULO PROFESIONAL ";
                }
            ?>de:
        </p>
        <table id="table1">
            <tbody>
                <tr>
                    <td style="width: 840px;"><h1><?php echo $tramite->denominacion; ?></h1></td>
                    <td rowspan=3><img src="<?php echo public_path().$foto_interesado?>" alt="FOTO DEL INTERESADO" style="width: 131px;"></td>                
                </tr>
                <tr>
                    <td style="width: 840px;"><p style="margin: 0;">a:</p></td>
                </tr>
                <tr>
                    <td style="width: 840px;"><h2><?php echo $tramite->nombreComp; ?></h2></td>
                </tr>
            </tbody>
        </table>
        <!-- <h1><?php echo $tramite->denominacion; ?></h1>
        <p style="margin: 0;">a:</p>
        <h2><?php echo $tramite->nombreComp; ?></h2> -->
        <p>De la <b><?php echo $tramite->facultad.", ".$tramite->programa; ?></b></p>
        <p>Cumpliendo con los requisitos exigidos por las disposiciones legales y reglamentarias vigentes, se expide el presente diploma para que se le reconozca como tal y se le otorgue los goces y privilegios que le confieren las Leyes de la República.</p>
        <p style="text-align: right"><?php echo "Trujillo, ".$dia." de ".$meses[$mes-1]. " de ".$año; ?></p>
        <br>
        <table id="table2">
            <tbody>
                <tr>
                    <td style="height: 90px;"><img src="./img/firmas/AMELIA_MORILLAS-ENFERMERIA.png" alt="FIRMA AUTORIDAD 1" style="height: 90px;"></td>
                    <td style="height: 90px;"><img src="./img/firmas/AMELIA_MORILLAS-ENFERMERIA.png" alt="FIRMA AUTORIDAD 2" style="height: 90px;"></td>
                    <td style="height: 90px;"><img src="./img/firmas/AMELIA_MORILLAS-ENFERMERIA.png" alt="FIRMA AUTORIDAD 3" style="height: 90px;"></td>
                
                </tr>
                <tr>
                    <td><b><?php echo $secretaria->nombres ?></b></td>
                    <td><b><?php echo $rector->nombres ?></b></td>
                    <td><b><?php echo $decano->nombres ?></b></td>
                </tr>
                <tr>
                    <td>
                        <?php if ($secretaria->sexo == 'M') echo "SECRETARIO GENERAL"; ?>
                        <?php if ($secretaria->sexo == 'F') echo "SECRETARIA GENERAL"; ?>
                        <?php if ($secretaria->cargo) echo $secretaria->cargo ?>
                    </td>
                    <td>
                        <?php if ($rector->sexo == 'M') echo "RECTOR"; ?>    
                        <?php if ($rector->sexo == 'F') echo "RECTORA"; ?>
                        <?php if ($rector->cargo) echo $rector->cargo ?>
                    </td>
                    <td>
                        <?php if ($decano->sexo == 'M') echo "DECANO"; ?>    
                        <?php if ($decano->sexo == 'F') echo "DECANA"; ?>
                        <?php if ($decano->cargo) echo $decano->cargo ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div id="cara2">
        <br>
        <table>
            <tbody>
                <tr>
                    <td colspan="6">
                        <div style="padding-left: 20px; width: 680px; line-height: 140%;">
                            CÓDIGO DE UNIVERSIDAD: <b>004</b><br>
                            TIPO DOCUMENTO: <b><?php echo $tipoDocumento; ?></b><br>
                            N° DOCUMENTO: <b><?php echo $tramite->nro_documento?></b><br>
                            ABREVIATURA GRADO/TÍTULO: <b><?php echo $tramite->diploma_obtenido; ?></b><br>
                            MODALIDAD DE OBTENCIÓN: <b><?php echo $tramite->acto_academico;?></b><br>
                            MODALIDAD DE ESTUDIOS: <b>P - PRESENCIAL</b><br>
                            <br>
                            RESOLUCIÓN DE OTORGAMIENTO: <b>RCU Nº <?php echo $tramite->nro_resolucion; ?></b><br>
                            FECHA DE RESOLUCIÓN DE OTORGAMIENTO: <b>
                                <?php 
                                    $date=date_create($tramite->fecha_resolucion);
                                    echo date_format($date,'d/m/Y');
                                ?>
                            </b><br>
                            <?php if(substr($emision_diploma, 0,1) == 'D') { ?>
                            <br>
                            RESOLUCIÓN DE DUPLICADO: <b>RR Nº <?php echo $tramite->nro_resolucion; ?></b><br>
                            FECHA DE RESOLUCIÓN DE DUPLICADO: <b>
                                <?php 
                                    $date=date_create($tramite->fecha_resolucion);
                                    echo date_format($date,'d/m/Y');
                                ?>
                            </b><br>
                            <?php } ?>
                            CÓDIGO DEL DIPLOMA: <b><?php echo $tramite->codigo_diploma; ?></b><br>
                            EMISIÓN DE DIPLOMA: <b><?php echo $emision_diploma; ?></b><br>
                            <br>
                            REGISTRADO EN EL LIBRO DE
                            <?php 
                                if(substr($tramite->diploma_obtenido, 0,1) == 'B' || substr($tramite->diploma_obtenido, 0,1) == 'M' || substr($tramite->diploma_obtenido, 0,1) == 'D'){
                                    echo "GRADOS DE ".substr($tramite->diploma_obtenido, 4);
                                }else{
                                    echo "TÍTULOS ".substr($tramite->diploma_obtenido, 11);
                                }
                            ?>
                            Nº: <b><?php echo $tramite->nro_libro?></b><br>
                            EN EL FOLIO Nº: <b><?php echo $tramite->folio;?></b><br>
                            REGISTRO Nº: <b><?php echo $tramite->nro_registro; ?></b> DE SECRETARÍA GENERAL
                        </div>
                    </td>
                    <td>
                        <div style="width: 131px;">
                            Para comprobar su autenticidad, escanear el código QR:<br>
                            <qrcode value="<?php echo $url_qr; ?>" ec="C" style="width: 131px; background-color: white; color: #000000;"></qrcode>
                            <!-- <img src="./img/qr.png" alt="QR"> -->
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <br><br><br><br><br>
                        <br><br><br><br>
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="3" style="border: 1px;">
                        <div style="width: 390px; padding: 20px;">
                            <?php if ($secretaria->sexo == 'M') echo "El Secretario General"; ?>
                            <?php if ($secretaria->sexo == 'F') echo "La Secretaria General"; ?>
                            de la Universidad Nacional de Trujillo CERTIFICA que este documento es auténtico y  ha  sido  expedido  por  la  institución  y  por   las autoridades  competentes  de  la  Universidad, cuyas  firmas   figuran   en   el   anverso  del presente diploma.
                            <span style="text-align: center;">
                                <br><br><br><br>
                                <br><br><br><br>
                                <b><?php echo $secretaria->nombres ?></b><br>
                                <?php if ($secretaria->sexo == 'M') echo "SECRETARIO GENERAL"; ?>
                                <?php if ($secretaria->sexo == 'F') echo "SECRETARIA GENERAL"; ?>
                                <?php if ($secretaria->cargo) echo $secretaria->cargo ?>
                            </span>
                        </div>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    <!-- <br><br><br> -->
    </div>
    
</body>
</html>