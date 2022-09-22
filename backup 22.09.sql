/*
SQLyog Community v13.1.6 (64 bit)
MySQL - 8.0.30 : Database - uraa_tramite
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`uraa_tramite` /*!40100 DEFAULT CHARACTER SET utf8mb3 */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `uraa_tramite`;

/*Table structure for table `cronograma_carpeta` */

DROP TABLE IF EXISTS `cronograma_carpeta`;

CREATE TABLE `cronograma_carpeta` (
  `idCronograma_carpeta` int NOT NULL AUTO_INCREMENT,
  `idDependencia` int NOT NULL,
  `idUnidad` int NOT NULL,
  `fecha_cierre_alumno` date NOT NULL,
  `fecha_cierre_secretaria` date NOT NULL,
  `fecha_cierre_decanato` date NOT NULL,
  `fecha_cierre_uraa` date NOT NULL,
  `fecha_colacion` date NOT NULL,
  `estado` tinyint(1) NOT NULL,
  PRIMARY KEY (`idCronograma_carpeta`),
  KEY `fk_cronograma_carpeta_dependencia1_idx` (`idDependencia`,`idUnidad`),
  CONSTRAINT `fk_cronograma_carpeta_dependencia1` FOREIGN KEY (`idDependencia`, `idUnidad`) REFERENCES `dependencia` (`idDependencia`, `idUnidad`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `dependencia` */

DROP TABLE IF EXISTS `dependencia`;

CREATE TABLE `dependencia` (
  `idDependencia` int NOT NULL AUTO_INCREMENT,
  `idUnidad` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idDependencia`,`idUnidad`),
  KEY `fk_dependencia_unidad1_idx` (`idUnidad`),
  CONSTRAINT `fk_dependencia_unidad1` FOREIGN KEY (`idUnidad`) REFERENCES `unidad` (`idUnidad`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `escuela` */

DROP TABLE IF EXISTS `escuela`;

CREATE TABLE `escuela` (
  `idEscuela` int NOT NULL AUTO_INCREMENT,
  `idDependencia` int NOT NULL,
  `idUnidad` int NOT NULL,
  `idSGA_PREG` int DEFAULT NULL,
  `idSUV_PREG` int DEFAULT NULL,
  `nombre` varchar(45) NOT NULL,
  `denominacion` varchar(100) NOT NULL,
  `descripcion_grado` varchar(100) NOT NULL,
  `descripcion_titulo` varchar(100) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idEscuela`,`idDependencia`,`idUnidad`),
  KEY `fk_escuela_dependencia1_idx` (`idUnidad`,`idDependencia`),
  CONSTRAINT `fk_escuela_dependencia1` FOREIGN KEY (`idUnidad`, `idDependencia`) REFERENCES `dependencia` (`idUnidad`, `idDependencia`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `estado_tramite` */

DROP TABLE IF EXISTS `estado_tramite`;

CREATE TABLE `estado_tramite` (
  `idEstado_tramite` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) DEFAULT NULL,
  `descripcion` varchar(45) NOT NULL,
  `color` varchar(10) NOT NULL,
  `icono` varchar(20) NOT NULL,
  `estado` varchar(45) NOT NULL,
  PRIMARY KEY (`idEstado_tramite`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `historial_estado` */

DROP TABLE IF EXISTS `historial_estado`;

CREATE TABLE `historial_estado` (
  `idHistorial_estado` int NOT NULL AUTO_INCREMENT,
  `idEstado_actual` int DEFAULT NULL,
  `idEstado_nuevo` int NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `idTramite` int NOT NULL,
  `idUsuario` int NOT NULL,
  PRIMARY KEY (`idHistorial_estado`),
  KEY `fk_historial_estado_tramite1_idx` (`idTramite`),
  KEY `fk_historial_estado_usuario1_idx` (`idUsuario`),
  CONSTRAINT `fk_historial_estado_tramite1` FOREIGN KEY (`idTramite`) REFERENCES `tramite` (`idTramite`),
  CONSTRAINT `fk_historial_estado_usuario1` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `mencion` */

DROP TABLE IF EXISTS `mencion`;

CREATE TABLE `mencion` (
  `idMencion` int NOT NULL AUTO_INCREMENT,
  `idDependencia` int NOT NULL,
  `idUnidad` int NOT NULL,
  `idSGA_SE` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `denominacion` varchar(100) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idMencion`,`idDependencia`,`idUnidad`),
  KEY `fk_escuela_dependencia1_idx` (`idUnidad`,`idDependencia`),
  CONSTRAINT `fk_escuela_dependencia10` FOREIGN KEY (`idUnidad`, `idDependencia`) REFERENCES `dependencia` (`idUnidad`, `idDependencia`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `modalidad_titulo_carpeta` */

DROP TABLE IF EXISTS `modalidad_titulo_carpeta`;

CREATE TABLE `modalidad_titulo_carpeta` (
  `idModalidad_titulo_carpeta` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) NOT NULL,
  `desripcion` varchar(45) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idModalidad_titulo_carpeta`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `motivo_certificado` */

DROP TABLE IF EXISTS `motivo_certificado`;

CREATE TABLE `motivo_certificado` (
  `idMotivo_certificado` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idMotivo_certificado`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `programa` */

DROP TABLE IF EXISTS `programa`;

CREATE TABLE `programa` (
  `idPrograma` int NOT NULL AUTO_INCREMENT,
  `idDependencia` int NOT NULL,
  `idUnidad` int NOT NULL,
  `nombre` varchar(45) NOT NULL,
  `idSGA_SE` int NOT NULL,
  `denominacion` varchar(100) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idPrograma`,`idDependencia`,`idUnidad`),
  KEY `fk_escuela_dependencia1_idx` (`idUnidad`,`idDependencia`),
  CONSTRAINT `fk_escuela_dependencia100` FOREIGN KEY (`idUnidad`, `idDependencia`) REFERENCES `dependencia` (`idUnidad`, `idDependencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

/*Table structure for table `requisito` */

DROP TABLE IF EXISTS `requisito`;

CREATE TABLE `requisito` (
  `idRequisito` int NOT NULL AUTO_INCREMENT,
  `idTipo_tramite_unidad` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `responsable` int NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idRequisito`),
  KEY `fk_requisito_tipo_tramite_unidad1_idx` (`idTipo_tramite_unidad`),
  CONSTRAINT `fk_requisito_tipo_tramite_unidad1` FOREIGN KEY (`idTipo_tramite_unidad`) REFERENCES `tipo_tramite_unidad` (`idTipo_tramite_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `tipo_tramite` */

DROP TABLE IF EXISTS `tipo_tramite`;

CREATE TABLE `tipo_tramite` (
  `idTipo_tramite` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(50) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idTipo_tramite`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `tipo_tramite_unidad` */

DROP TABLE IF EXISTS `tipo_tramite_unidad`;

CREATE TABLE `tipo_tramite_unidad` (
  `idTipo_tramite_unidad` int NOT NULL AUTO_INCREMENT,
  `idTipo_tramite` int NOT NULL,
  `idUnidad` int NOT NULL,
  `descripcion` varchar(45) NOT NULL,
  `costo` double NOT NULL,
  `costo_exonerado` double NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idTipo_tramite_unidad`),
  KEY `fk_tipo_tramite_unidad_tipo_tramite1_idx` (`idTipo_tramite`),
  KEY `fk_tipo_tramite_unidad_unidad1_idx` (`idUnidad`),
  CONSTRAINT `fk_tipo_tramite_unidad_tipo_tramite1` FOREIGN KEY (`idTipo_tramite`) REFERENCES `tipo_tramite` (`idTipo_tramite`),
  CONSTRAINT `fk_tipo_tramite_unidad_unidad1` FOREIGN KEY (`idUnidad`) REFERENCES `unidad` (`idUnidad`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `tipo_usuario` */

DROP TABLE IF EXISTS `tipo_usuario`;

CREATE TABLE `tipo_usuario` (
  `idTipo_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idTipo_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `tramite` */

DROP TABLE IF EXISTS `tramite`;

CREATE TABLE `tramite` (
  `idTramite` int NOT NULL AUTO_INCREMENT,
  `idTramite_detalle` int NOT NULL,
  `idTipo_tramite_unidad` int NOT NULL,
  `idVoucher` int NOT NULL,
  `idUsuario` int NOT NULL,
  `nro_tramite` char(10) NOT NULL,
  `idUnidad` int NOT NULL,
  `idDependencia` int NOT NULL,
  `idDependencia_detalle` int NOT NULL,
  `nro_matricula` varchar(45) NOT NULL,
  `sede` varchar(45) NOT NULL,
  `exonerado_archivo` varchar(255) DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `idEstado_tramite` int NOT NULL,
  `firma_tramite` varchar(255) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `idUsuario_asignado` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idTramite`),
  KEY `fk_tramite_tipo_tramite_unidad1_idx` (`idTipo_tramite_unidad`),
  KEY `fk_tramite_voucher1_idx` (`idVoucher`),
  KEY `fk_tramite_estado_tramite1_idx` (`idEstado_tramite`),
  KEY `fk_tramite_tramite_detalle1_idx` (`idTramite_detalle`),
  KEY `fk_tramite_usuario1_idx` (`idUsuario`),
  CONSTRAINT `fk_tramite_estado_tramite1` FOREIGN KEY (`idEstado_tramite`) REFERENCES `estado_tramite` (`idEstado_tramite`),
  CONSTRAINT `fk_tramite_tipo_tramite_unidad1` FOREIGN KEY (`idTipo_tramite_unidad`) REFERENCES `tipo_tramite_unidad` (`idTipo_tramite_unidad`),
  CONSTRAINT `fk_tramite_tramite_detalle1` FOREIGN KEY (`idTramite_detalle`) REFERENCES `tramite_detalle` (`idTramite_detalle`),
  CONSTRAINT `fk_tramite_usuario1` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`),
  CONSTRAINT `fk_tramite_voucher1` FOREIGN KEY (`idVoucher`) REFERENCES `voucher` (`idVoucher`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `tramite_detalle` */

DROP TABLE IF EXISTS `tramite_detalle`;

CREATE TABLE `tramite_detalle` (
  `idTramite_detalle` int NOT NULL AUTO_INCREMENT,
  `idCronograma_carpeta` int DEFAULT NULL,
  `idModalidad_titulo_carpeta` int DEFAULT NULL,
  `idMotivo_certificado` int DEFAULT NULL,
  `certificado_final` varchar(100) DEFAULT NULL,
  `codigo_certificado` varchar(100) DEFAULT NULL,
  `pass_certificado` varchar(100) DEFAULT NULL,
  `constancia_final` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`idTramite_detalle`),
  KEY `fk_tramite_detalle_cronograma1_idx` (`idCronograma_carpeta`),
  KEY `fk_tramite_detalle_motivo_certificado1_idx` (`idMotivo_certificado`),
  KEY `fk_tramite_detalle_modalidad_titulo_carpeta1_idx` (`idModalidad_titulo_carpeta`),
  CONSTRAINT `fk_tramite_detalle_cronograma1` FOREIGN KEY (`idCronograma_carpeta`) REFERENCES `cronograma_carpeta` (`idCronograma_carpeta`),
  CONSTRAINT `fk_tramite_detalle_modalidad_titulo_carpeta1` FOREIGN KEY (`idModalidad_titulo_carpeta`) REFERENCES `modalidad_titulo_carpeta` (`idModalidad_titulo_carpeta`),
  CONSTRAINT `fk_tramite_detalle_motivo_certificado1` FOREIGN KEY (`idMotivo_certificado`) REFERENCES `motivo_certificado` (`idMotivo_certificado`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `tramite_requisito` */

DROP TABLE IF EXISTS `tramite_requisito`;

CREATE TABLE `tramite_requisito` (
  `idTramite` int NOT NULL,
  `idRequisito` int NOT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `idUsuario_aprobador` int DEFAULT NULL,
  `validado` tinyint(1) NOT NULL DEFAULT '0',
  `comentario` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `des_estado_requisito` varchar(45) NOT NULL DEFAULT 'PENDIENTE',
  PRIMARY KEY (`idTramite`,`idRequisito`),
  KEY `fk_tramite_requisito_requisito1_idx` (`idRequisito`),
  CONSTRAINT `fk_tramite_requisito_requisito1` FOREIGN KEY (`idRequisito`) REFERENCES `requisito` (`idRequisito`),
  CONSTRAINT `fk_tramite_requisito_tramite1` FOREIGN KEY (`idTramite`) REFERENCES `tramite` (`idTramite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

/*Table structure for table `unidad` */

DROP TABLE IF EXISTS `unidad`;

CREATE TABLE `unidad` (
  `idUnidad` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(50) NOT NULL,
  `estado` tinyint(1) NOT NULL,
  PRIMARY KEY (`idUnidad`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `usuario` */

DROP TABLE IF EXISTS `usuario`;

CREATE TABLE `usuario` (
  `idUsuario` int NOT NULL AUTO_INCREMENT,
  `idTipo_usuario` int NOT NULL,
  `username` varchar(10) NOT NULL,
  `password` varchar(100) NOT NULL,
  `nombres` varchar(255) NOT NULL,
  `apellidos` varchar(255) NOT NULL,
  `tipo_documento` varchar(50) NOT NULL,
  `nro_documento` varchar(12) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `celular` varchar(9) NOT NULL,
  `sexo` char(1) NOT NULL,
  `confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `confirmation_code` varchar(25) DEFAULT NULL,
  `reset_password` varchar(25) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idUsuario`),
  KEY `fk_usuario_tipo_usuario_idx` (`idTipo_usuario`),
  CONSTRAINT `fk_usuario_tipo_usuario` FOREIGN KEY (`idTipo_usuario`) REFERENCES `tipo_usuario` (`idTipo_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

/*Table structure for table `voucher` */

DROP TABLE IF EXISTS `voucher`;

CREATE TABLE `voucher` (
  `idVoucher` int NOT NULL AUTO_INCREMENT,
  `entidad` varchar(50) NOT NULL,
  `nro_operacion` varchar(45) NOT NULL,
  `fecha_operacion` date NOT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `des_estado_voucher` varchar(45) NOT NULL DEFAULT 'PENDIENTE',
  `idUsuario_aprobador` int DEFAULT NULL,
  `validado` tinyint(1) NOT NULL DEFAULT '0',
  `comentario` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idVoucher`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
