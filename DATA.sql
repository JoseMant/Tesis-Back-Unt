INSERT INTO tipo_usuario(NAME) VALUES('Alumno'); 


INSERT INTO usuario(username,`password`,nro_matricula,nombres,apellidos,tipo_doc,nro_doc,correo,celular,sexo,idTipoUsuario) 
VALUES('kjuarez','$2y$10$J32LozOcUvuZ3vUEZJePeeO3gnu//yo8XGpZ6ovV81sbTgGuIB8Ra','1054544895','Kevin','Juarez','1','75411199','kevin@gmail.com','954854684','M',1);

INSERT INTO tipo_tramite(descripcion,pago) VALUES('CERTIFICADO',75.00),
('GRADO',60.00)
; 


INSERT INTO estado_tramite(descripcion) VALUES('TRÁMITE REGISTRADO')
; 

INSERT INTO requisitos(idTipo_tramite,nombre) VALUES(1,'Copia de dni');

INSERT INTO unidad(nombre) VALUES('PREGRADO'),
('POSTGRADO - DOCTORADO'),
('POSTGRADO - MAESTRÍA'),
('SEGUNDA ESPECIALIDAD');

INSERT INTO tipo_tramite_unidad(idTipo_Tramite,idUnidad,descripcion) VALUES(1,1,'CERTIFICADO DE ESTUDIOS DE 1 AÑO'),
(1,1,'CERTIFICADO DE ESTUDIOS DE 2 AÑO'),
(1,1,'CERTIFICADO DE ESTUDIOS DE 3 AÑO'),
(1,1,'CERTIFICADO DE ESTUDIOS DE 4 AÑO'),
(1,1,'CERTIFICADO DE ESTUDIOS DE 5 AÑO'),
(1,1,'CERTIFICADO DE ESTUDIOS DE 6 AÑO'),
(1,1,'CERTIFICADO DE ESTUDIOS DE 7 AÑO'),
(1,2,'CERTIFICADO DE ESTUDIOS DE 1 AÑO'),
(1,2,'CERTIFICADO DE ESTUDIOS DE 2 AÑO'),
(1,2,'CERTIFICADO DE ESTUDIOS DE 3 AÑO');


INSERT INTO dependencia(nombre,idUnidad) VALUES('FACULTAD DE INGENIERIA',1),
('FACULTAD DE CIENCIAS BIOLOGICAS',1);


INSERT INTO cronograma(idDependencia,fecha_cierre_alumno,fecha_colacion,fecha_cierre_secretaria,fecha_cierre_decanato,fecha_cierre_registro_tecnico) 
VALUES(1,23/01/2022,23/02/2022,23/03/2022,23/04/2022,23/05/2022);

INSERT INTO escuela(idDependencia,nombre,descripcion_grado,descripcion_titulo) VALUES(1,'ESCUELA DE INGENIERIA DE SISTEMAS','DESCRIPCION_GRADO','DESCRIPCION_TITULO'),
(1,'ESCUELA DE INGENIERIA CIVIL','DESCRIPCION_GRADO','DESCRIPCION_TITULO'),
(1,'ESCUELA DE INGENIERIA DE MINAS','DESCRIPCION_GRADO','DESCRIPCION_TITULO'),
(2,'ESCUELA PROFESIONAL DE CIENCIAS BIOLOGICAS','DESCRIPCION_GRADO','DESCRIPCION_TITULO')
;

/*INSERT INTO voucher(entidad,nro_operacion,fecha_operacion,archivo) VALUES('BCP','215484',23/03/1997,'voucher.jpg');

INSERT INTO tramite(idTipo_tramite,nro_documento,idColacion,idVoucher,idEstado_tramite,idModalidad_grado,descripcion_estado,codigo) 
VALUES (1,'75488855',1,1,1,1,'solicitud de certificado de estudios universitarios','1255544845')
; 




INSERT INTO tramite_requisito(idTramite,idRequisito,archivo) VALUES(1,1,'archivo.docx');

INSERT INTO historial_estado(idTramite,idUsuario,estado_nuevo,fecha) VALUES(1,1,'TRÁMITE REGISTRADO',23/03/1997);


SELECT * FROM tramite;
SELECT * FROM tramite_requisito;
SELECT * FROM historial_estado;
SELECT * FROM voucher;

SELECT * FROM tipo_tramite;

SELECT * FROM estado_tramite;




SELECT * FROM requisitos;



SELECT * FROM usuario;*/

