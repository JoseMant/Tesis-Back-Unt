DROP DATABASE uraa_tramites;
CREATE DATABASE uraa_tramites;
USE uraa_tramites;

CREATE TABLE tipo_usuario(
 idTipoUsuario INT AUTO_INCREMENT PRIMARY KEY,
 `name` VARCHAR (250) NOT NULL,
 estado TINYINT(1) NOT NULL DEFAULT 1
);


CREATE TABLE usuario(
 idUsuario INT AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR (50) NOT NULL,
 `password` VARCHAR (100) NOT NULL,
 estado TINYINT(1) NOT NULL DEFAULT 1,
 nro_matricula VARCHAR(15) NOT NULL,
 nombres VARCHAR(250) NOT NULL,
 apellidos VARCHAR(250) NOT NULL,
 tipo_doc VARCHAR(30) NOT NULL,
 nro_doc VARCHAR(30) NOT NULL,
 correo VARCHAR(250) NOT NULL, 
 celular VARCHAR(9),
 sexo CHAR(1) NOT NULL,
 idTipoUsuario INT NOT NULL,
 confirmed TINYINT(1) NOT NULL DEFAULT 0,
 confirmation_code VARCHAR(25) NULL,
 reset_password VARCHAR(25) NULL,
FOREIGN KEY(idTipoUsuario) REFERENCES tipo_usuario(idTipoUsuario)ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE tipo_tramite(
idTipo_tramite INT AUTO_INCREMENT PRIMARY KEY,
descripcion VARCHAR(255) NOT NULL,
pago FLOAT NOT NULL,
estado TINYINT(1) NOT NULL DEFAULT 1
);


CREATE TABLE estado_tramite(
idEstado_tramite INT AUTO_INCREMENT PRIMARY KEY,
descripcion VARCHAR(255) NOT NULL,
estado TINYINT(1) NOT NULL DEFAULT 1
);


CREATE TABLE voucher(
idVoucher INT AUTO_INCREMENT PRIMARY KEY,
entidad VARCHAR(250) NOT NULL,
nro_operacion VARCHAR(10) NOT NULL,
fecha_operacion DATETIME NOT NULL,
archivo VARCHAR(255) NOT NULL,
descipcion_estado TEXT NULL,
idUsuario_aprobador INT NULL,
validado TINYINT(1) NOT NULL DEFAULT 0,
estado TINYINT(1) NOT NULL DEFAULT 1
);


CREATE TABLE unidad(
idUnidad INT AUTO_INCREMENT PRIMARY KEY,
nombre VARCHAR(255) NOT NULL,
estado TINYINT(1) NOT NULL DEFAULT 1
);


CREATE TABLE dependencia(
idDependencia INT AUTO_INCREMENT PRIMARY KEY,
nombre VARCHAR(255) NOT NULL,
estado TINYINT(1) NOT NULL DEFAULT 1,
idUnidad INT NOT NULL,
FOREIGN KEY(idUnidad) REFERENCES unidad(idUnidad)ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE cronograma(
idCronograma INT AUTO_INCREMENT PRIMARY KEY,
fecha_cierre_alumno DATETIME NOT NULL,
fecha_colacion DATETIME NOT NULL,
fecha_cierre_secretaria DATETIME NOT NULL,
fecha_cierre_decanato DATETIME NOT NULL,
fecha_cierre_registro_tecnico DATETIME NOT NULL,
estado TINYINT(1) NOT NULL DEFAULT 1,
idDependencia INT NOT NULL,
FOREIGN KEY(idDependencia) REFERENCES dependencia(idDependencia)ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE escuela(
idEscuela INT AUTO_INCREMENT PRIMARY KEY,
idDependencia INT NOT NULL,
nombre VARCHAR(255) NOT NULL,
descripcion_grado VARCHAR(255) NULL,
descripcion_titulo VARCHAR(255) NULL,
estado TINYINT(1) NOT NULL DEFAULT 1,
FOREIGN KEY(idDependencia) REFERENCES dependencia(idDependencia)ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE tramite(
idTramite INT AUTO_INCREMENT PRIMARY KEY,
idTipo_tramite INT NOT NULL,
nro_documento VARCHAR(30) NOT NULL,
idCronograma INT NOT NULL,
idVoucher INT NOT NULL,
idEstado_tramite INT NOT NULL,
estado TINYINT(1) NOT NULL DEFAULT 1,
idModalidad_grado INT NULL,
descripcion_estado TEXT NULL,
exonerado TINYINT(1) NOT NULL DEFAULT 0,
codigo VARCHAR(15) NOT NULL,
FOREIGN KEY(idTipo_tramite) REFERENCES tipo_tramite(idTipo_tramite)ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY(idEstado_tramite) REFERENCES estado_tramite(idEstado_tramite)ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY(idCronograma) REFERENCES cronograma(idCronograma)ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY(idVoucher) REFERENCES voucher(idVoucher)ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE requisitos(
idRequisito INT AUTO_INCREMENT PRIMARY KEY,
idTipo_tramite INT NOT NULL,
nombre VARCHAR(255) NOT NULL,
estado TINYINT(1) NOT NULL DEFAULT 1,
FOREIGN KEY(idTipo_tramite) REFERENCES tipo_tramite(idTipo_tramite)ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE tramite_requisito(
idRequisito INT NOT NULL,
idTramite INT NOT NULL,
archivo VARCHAR(255) NULL,
estado TINYINT(1) NOT NULL DEFAULT 1,
PRIMARY KEY(idTramite,idRequisito),
FOREIGN KEY(idTramite) REFERENCES tramite(idTramite)ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY(idRequisito) REFERENCES requisitos(idRequisito)ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE historial_estado(
idHistorial_estado INT AUTO_INCREMENT PRIMARY KEY,
idTramite INT NOT NULL,
idUsuario INT NOT NULL,
estado_actual VARCHAR(255) NULL,
estado_nuevo VARCHAR(255) NULL,
fecha DATETIME NOT NULL,
estado TINYINT(1) NOT NULL DEFAULT 1
);


CREATE TABLE tipo_tramite_unidad(
idTipo_tramite_unidad INT AUTO_INCREMENT PRIMARY KEY,
idTipo_Tramite INT NOT NULL,
idUnidad INT NOT NULL,
descripcion VARCHAR(255) NOT NULL,
estado TINYINT(1) NOT NULL DEFAULT 1,
FOREIGN KEY(idTipo_Tramite) REFERENCES tipo_tramite(idTipo_Tramite)ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY(idUnidad) REFERENCES unidad(idUnidad)ON DELETE CASCADE ON UPDATE CASCADE
);


