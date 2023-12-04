<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// configuration jwt
Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('sign-up', 'AuthController@register');
    Route::post('sign-in', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('SignInUsingToken', 'AuthController@SignInUsingToken');
    Route::get('me', 'AuthController@me');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('getAlumnoByDocument','PersonaController@DatosAlumno');
    Route::post('forgot-password', 'AuthController@forgotPassword');
    Route::get('verifyCodePassword/{code}', 'AuthController@verifyCodePassword');
    Route::post('reset-password', 'AuthController@ResetPassword');
    Route::get('verify/{code}', 'AuthController@verify');

});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//TRÁMITE
Route::resource('tramites','TramiteController');

Route::post('tramites/secretaria','TramiteSecretariaController@store');
Route::get('tramite/usuario','TramiteController@GetByUser');
//DOCENTE
// Route::post('docente/registrar','TramiteSecretariaController@prueba');
Route::post('docente/registrar','TramiteSecretariaController@registroDocente');
Route::get('tramites/docentes/registrar','TramiteSecretariaController@GetTramitesDocente');
Route::get('tramites/docentes/validar','TramiteSecretariaController@GetDocenteValidar');
Route::post('tramites/docentes/validados','TramiteSecretariaController@validarDocente');
Route::post('docente/rechazar/{id}','TramiteSecretariaController@rechazarDocente');
Route::get('tramites/docentes/finalizados','TramiteSecretariaController@GetDocenteFinalizados');
Route::get('docente/search', 'TramiteSecretariaController@getDocenteByCodigo');

//RESOLUCIONES
Route::post('resoluciones/update/{id}','TramiteController@updateResolucion');

Route::get('tramites/resoluciones/validar','TramiteSecretariaController@GetResolucionesValidar');
Route::put('tramites/resoluciones/update','TramiteSecretariaController@updateTramiteRequisitos');
Route::get('tramites/resoluciones/observadas','TramiteSecretariaController@GetResolucionesObservadas');
Route::get('tramites/resoluciones/finalizadas','TramiteSecretariaController@GetResolucionesFinalizados');

// Route::post('tramite/update/{id}','TramiteController@update');
// Route::get('tramite/usuario/all','TramiteController@GetTramitesByUser');
Route::get('tramite/{id}','TramiteController@GetTramiteById');

//tramite fisico
Route::post('tramites_fisicos','Tramite_FisicoController@PostTramiteFisicoByUser');
//
Route::get('tramite/certificados','CertificadoController@GetCertificados');
Route::post('tramite/asignar','TramiteController@AsignacionTramites');
Route::get('tramite/certificados/validados','CertificadoController@GetCertificadosValidados');
Route::get('tramite/certificados/asignados','CertificadoController@GetCertificadosAsignados');
Route::get('tramite/certificados/aprobados','CertificadoController@GetCertificadosAprobados');
Route::get('tramite/certificados/aprobados/reporte','ReporteController@reporteAprobados');
Route::get('tramite/certificados/pendientes','CertificadoController@GetCertificadosPendientes');
Route::get('tramite/certificados/firma_uraa','CertificadoController@GetCertificadosFirmaUraa');
Route::get('tramite/certificados/firma_decano','CertificadoController@GetCertificadosFirmaDecano');
Route::get('tramite/certificados/reasignados','CertificadoController@GetCertificadosReasignados');
Route::get('tramite/certificados/finalizados','CertificadoController@GetCertificadosFinalizados');
Route::post('certificados/upload/{id}','CertificadoController@uploadCertificado');
Route::get('certificados/download/foto/{id}','CertificadoController@downloadFoto');
Route::get('constancias/enviar/{id}','ConstanciaController@enviarConstancia');
Route::post('constancias/upload/{id}','ConstanciaController@uploadConstancia');
// Route::get('tramite/carnets','CarnetController@GetCarnets');
Route::get('tramite/carnets/regulares','CarnetController@GetCarnetsRegulares');
Route::get('tramite/carnets/duplicados','CarnetController@GetCarnetsDuplicados');
// Route::get('tramite/carnets/asignados','CarnetController@GetCarnetsAsignados');
Route::get('tramite/carnets/aprobados','CarnetController@GetCarnetsAprobados');
Route::get('tramite/carnets/solicitados','CarnetController@GetCarnetsSolicitados');
// Route::get('tramite/carnets/aprobados/refresh','CarnetController@GetCarnetsAprobadosRefresh');
Route::get('carnets/solicitados/recibidos','CarnetController@setRecibidos');
Route::get('tramite/carnets/recibidos','CarnetController@GetCarnetsRecibidos');
Route::get('tramite/carnets/finalizados','CarnetController@GetCarnetsFinalizados');
// Route::get('tramite/carnets/entregados','CarnetController@GetCarnetsEntregados');
Route::get('carnets/validacion/sunedu','CarnetController@EnvioValidacionSunedu');
Route::put('carnets/recibidos/finalizar','CarnetController@setEntregado');
Route::get('tramite/constancias','ConstanciaController@GetConstancias');
Route::get('tramite/constancias/validados','ConstanciaController@GetConstaciasValidados');
Route::get('tramite/constancias/asignados','ConstanciaController@GetConstaciasAsignados');
Route::get('tramite/constancias/firma_uraa','ConstanciaController@GetConstanciasFirmaUraa');
Route::get('bancos','BancoController@index');
Route::get('respuesta','BancoController@respuesta');
Route::get('tipos_tramites','Tipo_TramiteController@index');
Route::get('paises','PaisController@index');
Route::get('sedes','SedeController@index');
Route::get('unidades','UnidadController@index');
Route::get('tipo_tramites_unidades/{idTipo_tramite}/{idUnidad}','Tipo_Tramite_UnidadController@getAllByTipo_tramiteUnidad');
Route::get('requisitos/{idTipo_tramite_unidad}','RequisitoController@getAllByTipo_tramite_unidad');
Route::get('facultades_alumno/{idUnidad}','PersonaController@DatosAlumno2');
//Route::post('facultades_alumno/{idUnidad}','PersonaController@DatosAlumno2'); //Método duplicado para la documentación en POSTMAN
Route::resource('motivos_certificado','Motivo_CertificadoController');
Route::resource('alumnosSE','PersonaSEController');


Route::get('profesiones','ProfesionDocenteController@index');
Route::get('categorias','CategoriaSGAController@index');
Route::get('dependenciasSGA','DependenciaSGAController@getDependenciasSGA');
Route::get('departamentos/{idDependencia}','DependenciaSGAController@DepartamentosByDependencia');
Route::get('dedicaciones/docente','DedicacionDocenteController@index');

Route::resource('motivos_certificado','Motivo_CertificadoController');
Route::resource('alumnosSE','PersonaSEController');

//VOUCHERS Y TRÁMITES
Route::resource('/voucher','VoucherController');
Route::get('vouchers/pendientes','VoucherController@Pendientes');
Route::get('vouchers/aprobados','VoucherController@Aprobados');
Route::get('vouchers/rechazados','VoucherController@Rechazados');
Route::get('reporte/tesoreria/aprobados','VoucherController@vouchersAprobados');
Route::post('vouchers/update/{id}','TramiteController@updateVoucher');
Route::post('requisitos/update/{id}','TramiteController@UpdateFilesRequisitos');
Route::post('chancar','AdicionalController@chancarArchivo');
Route::put('tramite/update','TramiteController@updateTramiteRequisitos');
Route::put('tramite/update/requisito','TramiteController@aprobarRequisito');
Route::post('tramites/notification','TramiteController@notificacionUpdate');
Route::post('tramites/anular','TramiteController@anularTramite');
Route::post('carpetas/finalizar','CarpetaController@finalizarCarpetas');

Route::put('vouchers/pendientes/validarVoucher/{id}', 'TramiteController@validarVoucher2');

//-----------------PDFs
Route::get('fut/{uuid}','PDF_FutController@pdf_fut');
Route::get('fut_fisico/{idTramite}','PDF_Fut_FisicoController@pdf_fut_fisico');
Route::get('constancia/{idTramite}','PDF_ConstanciaController@pdf_constancia');
Route::get('libro','PDF_LibroController@pdf_libro');
Route::get('diploma/{idTramite}','PDF_DiplomaController@Diploma');
Route::get('enviados/impresion/{idResolucion}','PDF_Enviados_ImpresionController@pdf_enviados_impresion');
//-------------------------------

// Route::resource('cargos','CargoController');
Route::resource('personas','PersonaController');
Route::get('users/all','UserController@index');
Route::get('users/search','UserController@buscar');
Route::get('usuario/uraa','UserController@getUsuariosUraa');
Route::put('users/update/{id}','UserController@update');
Route::post('users/create','UserController@store');
Route::put('settings/user','UserController@settings');
Route::put('settings/password','UserController@resetPassword');
// Route::get('personas/datosAlumno/{dni}','PersonaController@DatosAlumno');


//TIPO TRAMITE UNIDAD
Route::get('tipos_unidad_tramite/pendientes_impresion','Tipo_Tramite_UnidadController@getTramitesforPendientesImpresion');
Route::get('tipos_unidad_tramite/validacion_ura','Tipo_Tramite_UnidadController@getTramitesforValidaUraDuplicados');

//TIPOS DE TRÁMITE
Route::resource('tipos_tramites','Tipo_TramiteController');
//ESTADO DE TRÁMITE
Route::resource('estados_tramites','Estado_TramiteController');

//REQUISITOS
Route::resource('requisitos','RequisitoController');
//TRÁMITES_REQUISITOS
Route::resource('tramites_requisitos','Tramite_RequisitoController');
//HISTORIAL ESTADOS
Route::resource('historial_estados','Historial_EstadoController');


//DIPLOMA_ANULACION

Route::resource('historial_codigo_diploma','Historial_Codigo_DiplomaController');
Route::put('edit_ultimo_historial','Historial_Codigo_DiplomaController@editUltimoRegistro');


// E-mail verification
// Route::get('/auth/verify/{code}', 'AuthController@verify');


//RUTAS DOCENTES
Route::post('docentes', 'DocenteController@GetDocente');
Route::post('cargaLectiva', 'DocenteController@getCursosDocentePrincipal');
Route::get('personasSuv', 'PersonaSuvController@index');
//RUTAS DESCARGA ZIP
Route::get('download/fotos', 'ZipController@downloadFotos');
Route::get('backup/{idResolucion}', 'ZipController@backupFiles');
//RUTAS IMPORTAR Y EXPORTAR EXCEL
Route::post('carnets/import/observados', 'CarnetController@observadosimport');
Route::post('carnets/import/aprobados', 'CarnetController@aprobadosImport');
Route::post('carnets/import/solicitados', 'CarnetController@solicitadosImport');
Route::get('carnets/export', 'ExcelController@export');
Route::get('padron_sunedu/{idResolucion}', 'PadronController@padron');
Route::get('padron_oficio_sunedu/{idOficio}', 'PadronController@padronOficio');
Route::post('correccion/padron_sunedu', 'PadronController@correccion');
Route::get('excel/tesoreria/{fecha_inicio}/{fecha_fin}', 'VoucherController@reporteTesoreria');
Route::get('download/diplomas/{idResolucion}', 'ZipController@downloadDiplomas');
Route::post('upload/diplomas', 'UploadController@uploadzip');


//Roles
Route::get('roles', 'Tipo_UsuarioController@GetRoles');

//Tipos_documentos
Route::get('tipos_documentos', 'Tipo_DocumentoController@GetTipos_documentos');

//Cronograma
Route::get('cronogramas/all', 'CronogramaController@index');
Route::get('cronogramas/activos/{idDependencia}/{idTipo_tramite_unidad}', 'CronogramaController@getCronogramasActivos');
Route::get('cronogramas/search', 'CronogramaController@buscar');
Route::post('cronogramas/create', 'CronogramaController@store');
Route::put('cronogramas/update/{id}', 'CronogramaController@update');
Route::get('cronogramas/unidad/dependencia', 'CronogramaController@GetUnidadDependencia');
Route::get('resolucion/cronogramas/{idResolucion}', 'CronogramaController@getCronogramasLibres');
Route::get('cronogramas/dependencia/{idDependencia}/{idTipo_tramite_unidad}', 'CronogramaController@cronogramasByDependencia');

//Año
Route::get('anios', 'CronogramaController@getAnioCronogramas');

//GRADOS
Route::get('grados/validados/escuela', 'GradoController@GetGradosValidadosEscuela');
Route::get('grados/aprobados/escuela', 'GradoController@GetGradosAprobadosEscuela');
Route::get('grados/revalidados/escuela', 'GradoController@GetGradosRevalidadosEscuela');
Route::get('grados/validados/facultad', 'GradoController@GetGradosValidadosFacultad');
Route::get('grados/aprobados/facultad', 'GradoController@GetGradosAprobadosFacultad');
Route::get('grados/revalidados/facultad', 'GradoController@GetGradosRevalidadosFacultad');
Route::get('grados/diplomas/escuela', 'GradoController@GetGradosDatosDiplomaEscuela');
Route::get('grados/diplomas/facultad', 'GradoController@GetGradosDatosDiplomaFacultad');
Route::get('grados/diplomas/ura', 'GradoController@GetGradosDatosDiplomaUra');
Route::get('grados/validacion/ura', 'GradoController@GetGradosValidadosUra');
Route::put('grados/correccion', 'GradoController@cambiarEstado');//Usado
Route::put('grados/envio/facultad', 'GradoController@enviarFacultad');//Usado
Route::put('grados/envio/ura', 'GradoController@enviarUraa');
Route::put('grados/envio/escuela', 'GradoController@enviarEscuela');//Usado
Route::put('grados/registrar/libro', 'GradoController@registrarEnLibro');
Route::get('grados/firma/decano/{idResolucion}', 'GradoController@GetGradosFirmaDecano');
Route::get('grados/firma/secretaria/{idResolucion}', 'GradoController@GetGradosFirmaSecretaria');
Route::get('grados/firma/rector/{idResolucion}', 'GradoController@GetGradosFirmaRector');
Route::get('grados/pendientes/impresion/{idResolucion}', 'GradoController@GetGradosPendientesImpresion');
Route::get('grados/finalizados', 'GradoController@GetGradosFinalizados');
Route::post('grados/upload/{id}','GradoController@uploadDiploma');
// Route::get('grados/validados/secretaria', 'GradoController@GetGradosValidadosSecretaria');
Route::get('secretaria/observados', 'GradoController@GetGradosRechazadosSecretaria');
Route::get('grados/validados/secretaria/{idResolucion}', 'GradoController@GetGradosResolucion');
Route::get('resolucion/secretaria/{nro_resolucion}', 'GradoController@GetResolucion');
Route::get('grados/aprobados/secretaria', 'GradoController@GetGradosAprobadosSecretaria');
Route::get('modalidad/carpeta/{idTipo_tramite_unidad}', 'Modalidad_CarpetaController@getModalidadGrado');
Route::get('programas_estudios/carpeta', 'Programa_Estudios_CarpetaController@getProgramaEstudios');
Route::get('diplomas/carpeta/{idUnidad}/{idTipo_tramite_unidad}/{idPrograma}', 'Diploma_CarpetaController@getDiplomaCarpetas');
Route::put('grados/datos', 'GradoController@GuardarDatosDiploma');//Usado
Route::get('dependencia/escuelas/{id}', 'DependenciaController@getEscuelas');
Route::get('dependencia/{idDependencia_detalle}', 'DependenciaController@getDependenciaByPrograma');
Route::put('create/codigo', 'GradoController@createCodeDiploma');
//TITULOS
Route::get('titulos/validados/escuela', 'TituloController@GetTitulosValidadosEscuela');
Route::get('titulos/aprobados/escuela', 'TituloController@GetTitulosAprobadosEscuela');
Route::get('titulos/revalidados/escuela', 'TituloController@GetTitulosRevalidadosEscuela');
Route::get('titulos/validados/facultad', 'TituloController@GetTitulosValidadosFacultad');
Route::get('titulos/aprobados/facultad', 'TituloController@GetTitulosAprobadosFacultad');
Route::get('titulos/revalidados/facultad', 'TituloController@GetTitulosRevalidadosFacultad');
Route::get('titulos/diplomas/escuela', 'TituloController@GetTitulosDatosDiplomaEscuela');
Route::get('titulos/diplomas/facultad', 'TituloController@GetTitulosDatosDiplomaFacultad');
Route::get('titulos/diplomas/ura', 'TituloController@GetTitulosDatosDiplomaUra');
Route::get('titulos/validacion/ura', 'TituloController@GetTitulosValidadosUra');
Route::put('titulos/correccion', 'TituloController@cambiarEstado');
Route::put('titulos/envio/facultad', 'TituloController@enviarFacultad');
Route::put('titulos/envio/ura', 'TituloController@enviarUraa');
Route::put('titulos/envio/escuela', 'TituloController@enviarEscuela');
Route::put('titulos/registrar/libro', 'TituloController@registrarEnLibro');
Route::get('titulos/firma/decano', 'TituloController@GetTitulosFirmaDecano');
Route::get('titulos/firma/secretaria', 'TituloController@GetTitulosFirmaSecretaria');
Route::get('titulos/firma/rector', 'TituloController@GetTitulosFirmaRector');
//Route::get('titulos/pendientes/impresion/{nro_resolucion}', 'TituloController@GetTitulosPendientesImpresion');
Route::get('titulos/finalizados', 'TituloController@GetTitulosFinalizados');
Route::post('titulos/upload/{id}','TituloController@uploadDiploma');
Route::get('titulos/aprobados/secretaria', 'TituloController@GetTitulosAprobadosSecretaria');
Route::put('titulos/datos', 'TituloController@GuardarDatosDiploma');
//Route::put('create/codigo', 'TituloController@createCodeDiploma');
Route::get('carnets/solicitados','PDF_CarnetsController@pdf_carnetsSolicitados');
Route::get('carnets/recibidos','PDF_CarnetsController@pdf_carnetsRecibidos');
Route::get('carnets/finalizados','PDF_CarnetsController@pdf_carnetsFinalizados');
Route::get('sedes/uraa','PDF_CarnetsController@getSedesUraa');

//TITULOS segunda especialidad
Route::get('titulos/validados/especialidad', 'SegundaEspecialidadController@GetTitulosValidadosEscuela');
Route::get('titulos/aprobados/especialidad', 'SegundaEspecialidadController@GetTitulosAprobadosEscuela');
Route::get('titulos/revalidados/especialidad', 'SegundaEspecialidadController@GetTitulosRevalidadosEscuela');
Route::get('titulos/validados/facultadSE', 'SegundaEspecialidadController@GetTitulosValidadosFacultad');
Route::get('titulos/aprobados/facultadSE', 'SegundaEspecialidadController@GetTitulosAprobadosFacultad');
Route::get('titulos/revalidados/facultadSE', 'SegundaEspecialidadController@GetTitulosRevalidadosFacultad');
Route::get('titulos/diplomas/especialidad', 'SegundaEspecialidadController@GetTitulosDatosDiplomaEscuela');
Route::get('titulos/diplomas/facultadSE', 'SegundaEspecialidadController@GetTitulosDatosDiplomaFacultad');
Route::get('titulos/diplomas/uraSE', 'SegundaEspecialidadController@GetTitulosDatosDiplomaUra');
Route::get('titulos/validacion/uraSE', 'SegundaEspecialidadController@GetTitulosValidadosUra');
Route::put('titulos/correccionSE', 'SegundaEspecialidadController@cambiarEstado'); //usado
Route::put('titulos/envio/facultadSE', 'SegundaEspecialidadController@enviarFacultad'); //usado
Route::put('titulos/envio/ura', 'TituloController@enviarUraa');
Route::put('titulos/envio/especialidad', 'SegundaEspecialidadController@enviarEscuela');//usado
Route::put('titulos/registrar/libro', 'TituloController@registrarEnLibro');
Route::get('titulos/firma/decano', 'TituloController@GetTitulosFirmaDecano');
Route::get('titulos/firma/secretaria', 'TituloController@GetTitulosFirmaSecretaria');
Route::get('titulos/firma/rector', 'TituloController@GetTitulosFirmaRector');
Route::get('titulos/pendientes/impresion/{nro_resolucion}', 'TituloController@GetTitulosPendientesImpresion');
Route::get('titulos/finalizados', 'TituloController@GetTitulosFinalizados');
Route::post('titulos/upload/{id}','TituloController@uploadDiploma');
Route::put('titulos/datosSE', 'SegundaEspecialidadController@GuardarDatosDiploma');//usado
// Route::put('create/codigo', 'TituloController@createCodeDiploma');

//DUPLICADOS
Route::get('diplomas/duplicados/validar', 'Diplomas_DuplicadosController@GetDiplomasDuplicadosValidados');
Route::get('diplomas/duplicados/aprobar', 'Diplomas_DuplicadosController@GetDiplomasDuplicadosAprobados');
Route::get('diplomas/duplicados/revalidar', 'Diplomas_DuplicadosController@GetDiplomasDuplicadosRevalidados');
Route::get('diplomas/duplicados/validar/ura', 'Diplomas_DuplicadosController@GetDiplomasDuplicadosValidacionUra');
Route::get('diplomas/duplicados/datos/diplomas/ura', 'Diplomas_DuplicadosController@GetDiplomasDuplicadosDatosDiplomaUra');
Route::put('diplomas/duplicados/datos', 'Diplomas_DuplicadosController@GuardarDatosDiploma');//Usado


Route::get('carpeta/{id}', 'CarpetaController@getDataPersona');
Route::get('carpetas/finalizadas/{idResolucion}', 'CarpetaController@getFinalizados');
Route::get('carpetas/search', 'CarpetaController@getCarpetaBySearch');
Route::put('firmas/decano', 'GradoController@firmaDecano');
Route::put('firmas/rector', 'GradoController@firmaRector');
Route::put('firmas/secretaria', 'GradoController@firmaSecretaria');

//DEPENDENCIAS
Route::get('dependencias/{idUnidad}', 'DependenciaController@getDependenciasByUnidad');
Route::resource('dependencias', 'DependenciaController');

// ACREDITACIONES
Route::get('acreditadas/all', 'AcreditacionController@index');
Route::post('acreditadas/create', 'AcreditacionController@store');
// RESOLUCIONES
Route::get('resoluciones/all', 'ResolucionController@index');
Route::post('resoluciones/create', 'ResolucionController@store');
Route::put('resoluciones/update/{id}', 'ResolucionController@update');
Route::get('oficio/resoluciones/{idOficio}', 'ResolucionController@getResolucionesLibres');
Route::get('tipos/resoluciones', 'ResolucionController@getTipoResoluciones');
Route::get('resolucion/tramites/duplicados/{idResolucion}', 'ResolucionController@getTramitesDuplicadosLibres');

// OFICIOS
Route::get('oficios/all', 'OficioController@index');
Route::post('oficios/create', 'OficioController@store');
Route::put('oficios/update/{id}', 'OficioController@update');

//Universidades
Route::resource('universidades', 'UniversidadController');

//Libros digitales
Route::get('libros/tipo_tramite_unidad/{idTipo_tramite_unidad}', 'LibroController@getLibrosByTipoTramiteUnidad');

//Reportes
Route::get('reporte/enviado/facultad', 'ReporteController@enviadoFacultad');
Route::get('reporte/enviado/ura', 'ReporteController@enviadoUra');
Route::get('reporte/enviado/secretaria', 'ReporteController@enviadoSecretariaGeneral');
Route::get('reporte/elaboracion_carpeta/status_tramites', 'ReporteController@reporteCarpeta');
Route::get('reporte/status_tramites/certificados/pendientes/{idDependencia}/{cronograma}', 'ReporteController@crearExcelCertificadosPendientes');
Route::get('reporte/status_tramites/certificados/observados/{idDependencia}/{cronograma}', 'ReporteController@crearExcelCertificadosObservados');
Route::get('reporte/status_tramites/pdf/{idDependencia}/{cronograma}', 'ReporteController@certificadosObservadosCarpetasPDF');
Route::get('reporte/elaboracion_carpeta/expedientes', 'ReporteController@reporteExpediente');
Route::get('reporte/expedientesPDF', 'ReporteController@expedientesPDF');
Route::get('reporte/certificados/observados', 'ReporteController@certificadosObservados');
Route::get('programas/{idDependencia}', 'ReporteController@getProgramas');
Route::get('diploma', 'ReporteController@GetDiploma');
Route::get('reporte/elaboracion_carpeta/carpetas_aptas', 'ReporteController@reporteCarpetasAptas');
Route::get('reporte/carpetas_aptas/excel/{idDependencia}/{cronograma}', 'ReporteController@aptosColacion');
Route::get('reporte/status_tramites/carpetas/aptas/{idDependencia}/{idTipo_tramite_unidad}/{cronograma}', 'ReporteController@crearExcelCarpetasAptas');
Route::get('indicadores', 'ReporteController@indicadores');
Route::get('indicador/certificados', 'ReporteController@indicadorCertificados');
Route::get('indicador/grados', 'ReporteController@indicadorGrados');
Route::get('indicador/carpetas', 'ReporteController@indicadorCarpetas');

// Adicional
Route::post('eliminar', 'AdicionalController@eliminarHistorial');
Route::get('fecha', 'AdicionalController@getFecha');
Route::get('actualizar', 'AdicionalController@rechazar');
Route::put('separar', 'AdicionalController@separarApellidos');
Route::get('fecha/diploma', 'AdicionalController@getDatosDiploma');
Route::post('diploma/carpeta', 'AdicionalController@diploma_carpeta');
Route::get('agregar/programa/tramite', 'AdicionalController@setValuesProgramasTramite');
Route::get('agregar/programa/diploma_carpeta', 'AdicionalController@setValuesProgramasDiplomaCarpeta');
Route::get('uuid', 'AdicionalController@setValuesUuid');
Route::get('createCodeDiploma', 'AdicionalController@createCodeDiploma');
Route::get('createHistorialCodeDiploma', 'AdicionalController@createHistorialCodeDiploma');
Route::get('fotosIngresantes', 'AdicionalController@fotosIngresantes2023');

Route::get('graduado', 'AdicionalController@getGraduado');
Route::get('reporte/tramites/espera', 'ReporteController@tramitesEspera');
