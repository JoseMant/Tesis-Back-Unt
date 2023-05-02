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
$cadena_buscada   = ' MENCIÓN :';
$posicion_coincidencia = strpos($cadena_de_texto, $cadena_buscada);

$fecha = DATE($tramite->fecha_colacion);
$año = substr($fecha, 0, 4);
$mes = (int)substr($fecha, 5, 2);
$dia = substr($fecha, 8, 2);
// $cadena = $_POST['nombreComp'];//no funca para nombres con la primera letra con tilde
// //$cadena = str_replace('Á', 'á', $cadena);
// //$cadena = str_replace('É', 'é', $cadena);
// //$cadena = str_replace('Í', 'í', $cadena);
// //$cadena = str_replace('Ó', 'ó', $cadena);
// //$cadena = str_replace('Ú', 'ú', $cadena);
// //$cadena = str_replace('Ñ', 'ñ', $cadena);
// /*$cadena = str_replace('Á', 'A', $cadena);
// $cadena = str_replace('É', 'E', $cadena);
// $cadena = str_replace('Í', 'I', $cadena);
// $cadena = str_replace('Ó', 'O', $cadena);
// $cadena = str_replace('Ú', 'U', $cadena);
// $cadena = str_replace('Ñ', 'Ñ', $cadena);*/
// $nombreComp = strtoupper($cadena);

// //$nombreComp = (ucwords(strtolower($cadena)));

// $rector = $_POST['rector'];
// $decano = $_POST['secretario'];
// $secretario = $_POST['decano'];
// $rectorCargo = $_POST['rectorCargo'];
// $secretarioCargo = $_POST['secretarioCargo'];
// $decanoCargo = $_POST['decanoCargo'];

// $nrolibro = $_POST['libro'];
// $facultad = $_POST['facultad'];
// $escuela = $_POST['escuela'];
// $folio = $_POST['folio'];
// $nroRegistro = $_POST['nroRegistro'];
// $tipoDocumento = $_POST['tipoDocumento'];
// $tipoFicha = $_POST['tipoFicha']; //tipo de tramite
// $tipoActo = $_POST['actoAcademico'];
// $fechaResolucionCU = $_POST['fechaConsuniv'];
// $numResolucionUniv = $_POST['nroResolucionUniv'];
// $idFicha = $_POST['idFicha'];// Unidad (predgrado, post, etc)
// $fotoAlumno = $_POST['fotoAlumno'];
// $opcFoto = $_POST['opcFoto']; //condicional si aparece con foto o sin foto
// if(isset($_POST['idgraduadoDup'])) {
//     $diplomasEstado = "DUPLICADO";
//     $fechaEmision = $_POST['fechaEmision'];
// }
// else{
//     $diplomasEstado = "ORIGINAL";
// }

// $nombre_escuela_preford="";
// if ($idFicha==7){
//     //$conexion = new mysqli('localhost','u_diplomasunt','dY8iufe5Lth3Wmrld','diplomas_app',3306);
//     $conexion = new mysqli('localhost','u_diplomasunt','dY8iufe5Lth3Wmrld','diplomas_app',3306);
//     if (mysqli_connect_errno()) {
//         printf("La conexión con el servidor de base de datos falló: %s\n", mysqli_connect_error());
//         exit();
//     }
//     $consulta="SELECT nom_escuela FROM preford_escuela WHERE cod_alumno='".$id."'";
//     $resultado = $conexion->query($consulta);
//     if($resultado->num_rows > 0 ){
//         $fila = $resultado->fetch_array();
//         $nombre_escuela_preford=$fila['nom_escuela'];
//     }
// }

// ob_start();
$msg = "tramites-uraa.unitru.edu.pe/carpeta/".$tramite->idTramite;
// $dias = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");



// LÓGICA PARA SACAR LA FACULTAD DE LAS SEGUNDAS ESPECIALIDADES
$facultad=$tramite->facultad;
$escuela=$tramite->escuela;

// LÓGICA DE DENOMINACIONES
$r = '';
switch ($escuela) {
        case 'RESIDENTADO MÉDICO':
            switch ($tramite->diploma){
                case 'T021':
                    $r = 'PROGRAMA DE MEDICINA INTERNA';
                    break;
                case 'T022':
                    $r = 'PROGRAMA DE OFTALMOLOGÍA';
                    break;
                case 'T023':
                    $r = 'PROGRAMA DE CIRUGÍA GENERAL';
                    break;
                case 'T024':
                    $r = 'PROGRAMA DE OTORRINOLARINGOLOGÍA';
                    break;
                case 'T025':
                    $r = 'PROGRAMA DE GINECOLOGÍA Y OBSTETRICIA';
                    break;
                case 'T026':
                    $r = 'PROGRAMA DE ORTOPEDIA Y TRAUMATOLOGÍA';
                    break;
                case 'T027':
                    $r = 'PROGRAMA DE PEDIATRÍA';
                    break;
                case 'T028':
                    $r = 'PROGRAMA DE PATOLOGÍA';
                    break;
                case 'T029':
                    $r = 'PROGRAMA DE UROLOGÍA';
                    break;
                case 'T030':
                    $r = 'PROGRAMA DE ANESTESIOLOGÍA';
                    break;
                case 'T031':
                    $r = 'PROGRAMA DE MEDICINA TROPICAL';
                    break;
                case 'T032':
                    $r = 'PROGRAMA DE REUMATOLOGÍA';
                    break;
                case 'T033':
                    $r = 'PROGRAMA DE MEDICINA FAMILIAR';
                    break;
                case 'T034':
                    $r = 'PROGRAMA DE DERMATOLOGÍA';
                    break;
                case 'T035':
                    $r = 'PROGRAMA DE NEUROLOGÍA';
                    break;
                case 'T036':
                    $r = 'PROGRAMA DE MEDICINA INTERNA';
                    break;
                case 'T037':
                    $r = 'PROGRAMA DE RADIOLOGÍA';
                    break;
                case 'T086':
                    $r = 'PROGRAMA DE GASTROENTEROLOGÍA';
                    break;
                case 'T094':
                    $r = 'PROGRAMA DE TRAUMATOLOGÍA Y ORTOPEDIA';
                    break;
                case 'T101':
                    $r = 'PROGRAMA DE CIRUGIA PLÁSTICA';
                    break;
                case 'T103':
                    $r = 'PROGRAMA DE NEONATOLOGÍA';
                    break;
                case 'T104':
                    $r = 'PROGRAMA DE CARDIOLOGÍA';
                    break;
                case 'T108':
                    $r = 'PROGRAMA DE MEDICINA DE EMERGENCIAS Y DESASTRES';
                    break;
                case 'T111':
                    $r = 'PROGRAMA DE MEDICINA DE ENFERMEDADES INFECCIOSAS Y TROPICALES';
                    break;
                case 'T119':
                    $r = 'PROGRAMA DE NEUMOLOGÍA';
                    break;
                case 'T120':
                    $r = 'PROGRAMA DE PSIQUIATRÍA';
                    break;
                case 'T146':
                    $r = 'PROGRAMA DE MEDICINA FAMILIAR Y COMUNITARIA';
                    break;
                case 'T147':
                    $r = 'PROGRAMA DE MEDICINA INTENSIVA';
                    break;
                case 'T157':
                    $r = 'PROGRAMA DE MEDICINA ONCOLÓGICA';
                    break;
                case 'T158':
                    $r = 'PROGRAMA DE CIRUGÍA ONCOLÓGICA';
                    break;
                case 'T160':
                    $r = 'PROGRAMA DE MEDICINA FÍSICA Y REHABILITACIÓN';
                    break;
                case 'T162':
                    $r = 'PROGRAMA DE NEUROCIRUGÍA';
                    break;
                case 'T194':
                    $r = 'PROGRAMA DE GINECOLOGÍA Y OBSTETRICIA';
                    break;
                case 'T195':
                    $r = 'PROGRAMA DE MEDICINA FÍSICA Y DE REHABILITACIÓN';
                    break;
                case 'T197':
                    $r = 'PROGRAMA DE MEDICINA FÍSICA Y DE REHABILITACIÓN';
                    break;  
                default:
                    $r = 'PROGRAMA DE';
                    break;
            }
            break;
        case 'SEGUNDA ESPECIALIDAD PROFESIONAL EN ENFERMERÍA':
            switch ($tramite->diploma){
                case 'T089':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN ADULTO EN SITUACIONES CRÍTICAS';
                    break;
                case 'T090':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN NIÑO Y ADOLESCENTE';
                    break;
                case 'T091':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN PERINATAL';
                    break;
                case 'T092':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN GERENCIA EN SALUD';
                    break;
                case 'T106':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADO DEL ADULTO EN TERAPIA INTENSIVA';
                    break;
                case 'T107':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADO DEL NEONATO EN TERAPIA INTENSIVA';
                    break;
                case 'T109':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL ESPECIALISTA EN GERONTOLOGÍA Y GERIATRÍA';
                    break;
                case 'T110':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL ESPECIALISTA EN GERENCIA Y ADMINISTRACIÓN EN SALUD';
                    break;
                case 'T112':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADO DEL ADULTO EN URGENCIA Y EMERGENCIA';
                    break;
                case 'T113':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADO DEL ADULTO EN CENTRO QUIRÚRGICO';
                    break;
                case 'T115':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADO DE ENFERMERÍA EN CENTRO QUIRÚRGICO';
                    break;
                case 'T116':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADO DEL ADULTO EN TERAPIA NEFROLÓGICA';
                    break;
                case 'T117':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADO DEL ADULTO EN TERAPIA ONCOLÓGICA';
                    break;
                case 'T118':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADO DEL NIÑO EN TERAPIA INTENSIVA';
                    break;
                case 'T128':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADOS INTENSIVOS - ADULTO';
                    break;
                case 'T129':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADOS INTENSIVOS - PEDIATRÍA';
                    break;
                case 'T130':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADOS INTENSIVOS - NEONATOLOGÍA';
                    break;
                case 'T131':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN SALUD DEL ADULTO';
                    break;
                case 'T133':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN ONCOLOGÍA';
                    break;
                case 'T134':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN NEFROLOGÍA';
                    break;
                case 'T135':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CENTRO QUIRÚRGICO';
                    break;
                case 'T136':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN EMERGENCIAS Y DESASTRES';
                    break;
                case 'T141':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL ESPECIALISTA EN SALUD OCUPACIONAL';
                    break;
                case 'T142':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL ESPECIALISTA EN GERENCIA Y ADMINISTRACIÓN EN SALUD';
                    break;
                case 'T143':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL ESPECIALISTA EN GERONTOLOGÍA Y GERIATRÍA';
                    break;
                case 'T144':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL ESPECIALISTA EN SALUD FAMILIAR Y COMUNITARIA';
                    break;
                case 'T155':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADO DEL ADULTO EN TERAPIA CLÍNICA';
                    break;
                case 'T167':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADO DEL ADULTO EN TERAPIA QUIRÚRGICA';
                    break;
                case 'T173':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN CUIDADOS QUIRÚRGICOS';
                    break;
                case 'T185':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL ESPECIALISTA EN CUIDADO DE ENFERMERÍA EN CENTRO QUIRÚRGICO';
                    break;
                case 'T186':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL ESPECIALISTA EN ONCOLOGÍA';
                    break;
                default:
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL';
                    break;
            }
            break;
        case 'SEGUNDA ESPECIALIDAD EN CIENCIAS BIOLÓGICAS':
            switch ($tramite->diploma){
                case 'T148':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN BIOLOGÍA MOLECULAR Y GENÉTICA';
                        break;
                case 'T149':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN GESTIÓN AMBIENTAL';
                    break;
                case 'T150':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN BIOLOGÍA FORENSE';
                    break;
                case 'T151':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN LABORATORIO DE ANÁLISIS CLÍNICO Y BIOLÓGICOS';
                    break;
                case 'T152':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN EPIDEMIOLOGÍA';
                    break;
                case 'T153':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN GESTIÓN DE LA BIODIVERSIDAD';
                    break;
                case 'T154':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN ENTOMOLOGÍA MÉDICA Y CONTROL DE VECTORES';
                    break;
                case 'T165':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN HEMOTERAPIA Y BANCO DE SANGRE';
                    break;
                case 'T166':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN GESTIÓN DE LA CALIDAD E INOCUIDAD ALIMENTARIA';
                    break;
                case 'T170':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN SANIDAD VEGETAL';
                    break;
                case 'T171':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN EDUCACIÓN AMBIENTAL';
                    break;
                case 'T172':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD: EVALUACIÓN DE IMPACTO AMBIENTAL';
                    break;
                default:
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD';
                    break;
            }
            break;
        case 'TECNOLOGÍA EDUCATIVA':
            switch($tramite->diploma){
                case 'T096':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN TECNOLOGÍA EDUCATIVA MENCIÓN ADMINISTRACIÓN Y GERENCIA EDUCATIVA';
                    break;
                case 'T126':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN TECNOLOGÍA EDUCATIVA MENCIÓN CURRÍCULO Y ENSEÑANZA APRENDIZAJE';
                    break;
                case 'T099':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL EN TECNOLOGÍA EDUCATIVA MENCIÓN INFORMÁTICA EDUCATIVA';
                    break;
                default:
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD PROFESIONAL';
                    break;
            }
            break;
        case 'ESTIMULACIÓN TEMPRANA':
            switch($tramite->diploma){
                case 'T164':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN ESTIMULACIÓN TEMPRANA';
                    break;
                default:
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD';
                    break;
            }
            break;
        case 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN EDUCACIÓN INICIAL':
            switch($tramite->diploma){
                case 'T168':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN EDUCACIÓN INICIAL';
                    break;
                default:
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD';
                    break;
            }
            break;
        case 'SEGUNDA ESPECIALIDAD EN FARMACIA Y BIOQUÍMICA':
            switch($tramite->diploma){
                case 'T191':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN FARMACIA CLÍNICA';
                    break;
                case 'T192':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN FARMACIA HOSPITALARIA Y COMUNITARIA';
                    break;
                case 'T193':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN TOXICOLOGÍA Y QUÍMICA FORENSE';
                    break;
                default:
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD';
                    break;
            }
            break;
        case 'SEGUNDA ESPECIALIDAD EN ESTOMATOLOGÍA':
            switch($tramite->diploma){
                case 'T123':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN PERIODONCIA';
                    break;
                case 'T124':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN ODONTOPEDIATRÍA';
                    break;
                case 'T137':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN REHABILITACIÓN ORAL';
                    break;
                case 'T138':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN ORTODONCIA Y ORTOPEDIA MAXILAR';
                    break;
                case 'T139':
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD EN CARIELOGÍA Y ENDODONCIA';
                    break;
                default:
                    $r = 'PROGRAMA DE SEGUNDA ESPECIALIDAD';
                    break;
            }
            break;
}
// ----------------------------------------

// LÓGICA PARA EL TIPO DE DOCUMENTO
$tipoDocumento="";
if ($tramite->tipo_documento==1) {
    $tipoDocumento="DNI";
}if ($tramite->tipo_documento==2) {
    $tipoDocumento="";
}if ($tramite->tipo_documento==3) {
    $tipoDocumento="CARNET DE EXTRANJERÍA";
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
                    <!-- <p style="text-align: center; font-family: Times; font-size: 25px; font-weight: bold; margin-top: 5px; margin-bottom: 15px; margin-left: 214px">A NOMBRE DE LA NACIÓN</p> -->
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
                                if($escuela=='SEGUNDA ESPECIALIDAD EN ENFERMERÍA' || $escuela=='PROGRAMA DE SEGUNDA ESPECIALIDAD EN EDUCACIÓN INICIAL')
                                    echo " TÍTULO ";
                                else
                                    echo " TÍTULO PROFESIONAL ";
                            }
                            ?>de :
                    </p>
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
                    <?php if ($diploma == 'T141' || $diploma == 'T142' || $diploma == 'T143' || $diploma == 'T144' || $diploma == 'T168' || $diploma == 'T055' || $diploma == 'T045' || $diploma == 'T138' || $diploma == 'T195' || $diploma == 'T108' || $diploma == 'T148' || $diploma == 'T151' || $diploma == 'T164' || $diploma == 'T196' || $diploma == 'T197' || $diploma == 'T154' || $diploma == 'T152' || $diploma == 'M137') {?>
                        <p style="font-size:20px; text-align: center; margin-top: -22px;">
                    <?php }else {?>   
                        <p style="font-size:20px; text-align: center; ">
                    <?php } ?>     
                    <div style="float:right; margin-left: -532mm; ">a:&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <!--<font style="font-size:45px; font-family: brushib; margin-top:-10mm; "><b><?php //echo $nombreComp;?></b></font>-->
                    <font style="font-size:31px; /*font-family: coopblb;*/ font-family: arial; margin-top:-8mm; "><b><?php echo $tramite->nombreComp;?></b></font>
                    </p>

                    <p style="text-align: justify; text-indent: 0px; margin-bottom: 18px; margin-top: -35px; font-size:18px">
                        De la <b><?php echo $facultad ?>,</b>
                        <b>
                            <?php if ($idFicha==1 || $idFicha==2){?>ESCUELA PROFESIONAL DE <?php }?>
                            <?php if ($tramite->idDependencia_detalle==49){?>SEGUNDA ESPECIALIZACIÓN,<?php }?>
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
                            REGISTRADO EN EL LIBRO DE
                            <?php 
                            if(substr($tramite->diploma, 0,1) == 'B' || substr($tramite->diploma, 0,1) == 'M' || substr($tramite->diploma, 0,1) == 'D'){
                                echo " GRADOS ";
                            }else{
                                echo " TÍTULOS ";
                            }
                            ?>
                            Nº:<b> <?php echo $tramite->nro_libro?> </b> <br>
                            EN EL FOLIO Nº:<b> <?php echo $tramite->folio;?></b> <br>
                            REGISTRO Nº: <b><?php echo $tramite->nro_registro; ?> </b> DE SECRETARIA GENERAL <br>
                            TIPO DOCUMENTO:<b> <?php echo $tipoDocumento; ?></b> N° DOCUMENTO: <b> <?php echo $tramite->nro_documento?></b> <br>
                            DIPLOMA OBTENIDO: <b><?php echo $tramite->diploma_obtenido; ?></b> <br>
                            OBTENIDO POR: <b><?php echo $tramite->acto_academico;?></b><br>
                            MODALIDAD DE ESTUDIOS: <b> PRESENCIAL</b>
                        </p>
                    </div>
                </td>
                <td>
                    <p  style="font-size: 11px; margin-top: 0px;">
                        RESOLUCIÓN <?php if(isset($_POST['idgraduadoDup'])) { echo "RECTORAL"; }else{ echo "DE CONSEJO UNIVERSITARIO"; } ?> Nº :<b> <?php echo $tramite->nro_resolucion; ?></b><br>
                        FECHA RESOLUCIÓN <?php if(isset($_POST['idgraduadoDup'])) { echo "RECTORAL"; }else{ echo "DEL CONSEJO UNIVERSITARIO"; } ?>: <b> <?php echo $tramite->fecha_resolucion; ?></b><br>
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
    </div>
</div>
