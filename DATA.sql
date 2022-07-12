INSERT INTO tipo_usuario(NAME) VALUES ('Alumno'); 


INSERT INTO usuario(username,`password`,nro_matricula,nombres,apellidos,tipo_doc,nro_doc,correo,celular,sexo,idTipoUsuario) 
VALUES ('kjuarez','$2y$10$J32LozOcUvuZ3vUEZJePeeO3gnu//yo8XGpZ6ovV81sbTgGuIB8Ra','1054544895','Kevin','Juarez','1','75411186','kevin@gmail.com','954854684','M',1);

INSERT INTO tipo_tramite(descripcion,pago) VALUES ('CERTIFICADO',75.00),
('GRADO',60.00)
; 


INSERT INTO estado_tramite(descripcion) VALUES ('TRÁMITE REGISTRADO')
; 

INSERT INTO requisitos(idTipo_tramite,nombre) VALUES(1,'Copia de dni');

INSERT INTO unidad(nombre) VALUES('PREGRADO'),
('POSTGRADO - DOCTORADO'),
('POSTGRADO - MAESTRÍA'),
('SEGUNDA ESPECIALIDAD');


INSERT INTO voucher(entidad,nro_operacion,fecha_operacion,archivo) VALUES('BCP','215484',23/03/1997,'voucher.jpg');

INSERT INTO tramite(idTipo_tramite,nro_documento,idColacion,idVoucher,idEstado_tramite,idModalidad_grado,descripcion_estado,codigo) 
VALUES (1,'75488855',1,1,1,1,'solicitud de certificado de estudios universitarios','1255544845')
; 




INSERT INTO tramite_requisito(idTramite,idRequisito,archivo) VALUES(1,1,'archivo.docx');

INSERT INTO historial_estado(idTramite,idUsuario,estado_nuevo,fecha) VALUES(1,1,'TRÁMITE REGISTRADO',23/03/1997);


SELECT * FROM tramite;
SELECT * FROM tramite_requisito;
SELECT * FROM historial_estado;
SELECT * FROM voucher;

/*SELECT * FROM tipo_tramite;

SELECT * FROM estado_tramite;




SELECT * FROM requisitos;



SELECT * FROM usuario;*/

