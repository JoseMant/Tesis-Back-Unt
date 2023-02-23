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
Route::get('tramite/usuario','TramiteController@GetByUser');
// Route::post('tramite/update/{id}','TramiteController@update');
Route::get('tramite/usuario/all','TramiteController@GetTramitesByUser');
//tramite fisico
Route::post('tramites_fisicos','Tramite_FisicoController@PostTramiteFisicoByUser');
//
Route::get('tramite/certificados','CertificadoController@GetCertificados');
Route::post('tramite/asignar','TramiteController@AsignacionTramites');
Route::get('tramite/certificados/validados','CertificadoController@GetCertificadosValidados');
Route::get('tramite/certificados/asignados','CertificadoController@GetCertificadosAsignados');
Route::get('tramite/certificados/aprobados','CertificadoController@GetCertificadosAprobados');
Route::get('tramite/certificados/pendientes','CertificadoController@GetCertificadosPendientes');
Route::get('tramite/certificados/firma_uraa','CertificadoController@GetCertificadosFirmaUraa');
Route::get('tramite/certificados/firma_decano','CertificadoController@GetCertificadosFirmaDecano');
Route::get('tramite/certificados/reasignados','CertificadoController@GetCertificadosReasignados');
Route::get('tramite/certificados/finalizados','CertificadoController@GetCertificadosFinalizados');
Route::post('certificados/upload/{id}','CertificadoController@uploadCertificado');
Route::get('certificados/download/foto/{id}','CertificadoController@downloadFoto');
Route::get('constancias/enviar/{id}','ConstanciaController@enviarConstancia');
Route::post('constancias/upload/{id}','ConstanciaController@uploadConstancia');
Route::get('tramite/carnets','CarnetController@GetCarnets');
Route::get('tramite/carnets/regulares','CarnetController@GetCarnetsRegulares');
Route::get('tramite/carnets/duplicados','CarnetController@GetCarnetsDuplicados');
Route::get('tramite/carnets/asignados','CarnetController@GetCarnetsAsignados');
Route::get('tramite/carnets/aprobados','CarnetController@GetCarnetsAprobados');
Route::get('tramite/carnets/solicitados','CarnetController@GetCarnetsSolicitados');
Route::get('tramite/carnets/aprobados/refresh','CarnetController@GetCarnetsAprobadosRefresh');
Route::get('carnets/solicitados/recibidos','CarnetController@setRecibidos');
Route::get('tramite/carnets/recibidos','CarnetController@GetCarnetsRecibidos');
Route::get('tramite/carnets/entregados','CarnetController@GetCarnetsEntregados');
Route::get('carnets/validacion/sunedu','CarnetController@EnvioValidacionSunedu');
Route::put('carnets/recibidos/finalizar','CarnetController@setEntregado');
Route::get('tramite/constancias','ConstanciaController@GetConstancias');
Route::get('tramite/constancias/validados','ConstanciaController@GetConstaciasValidados');
Route::get('tramite/constancias/asignados','ConstanciaController@GetConstaciasAsignados');
Route::get('tramite/constancias/firma_uraa','ConstanciaController@GetConstanciasFirmaUraa');
Route::get('bancos','BancoController@index');
Route::get('respuesta','BancoController@respuesta');
Route::get('tipos_tramites','Tipo_TramiteController@index');
Route::get('sedes','SedeController@index');
Route::get('unidades','UnidadController@index');
Route::get('tipo_tramites_unidades/{idTipo_tramite}/{idUnidad}','Tipo_Tramite_UnidadController@getAllByTipo_tramiteUnidad');
Route::get('requisitos/{idTipo_tramite_unidad}','RequisitoController@getAllByTipo_tramite_unidad');
Route::get('facultades_alumno/{idUnidad}','PersonaController@DatosAlumno2');
Route::resource('motivos_certificado','Motivo_CertificadoController');
Route::resource('alumnosSE','PersonaSEController');


//VOUCHERS Y TRÁMITES
Route::resource('/voucher','VoucherController');
Route::get('vouchers/pendientes','VoucherController@Pendientes');
Route::get('vouchers/aprobados','VoucherController@Aprobados');
Route::get('vouchers/rechazados','VoucherController@Rechazados');
Route::post('vouchers/update/{id}','TramiteController@updateVoucher');
Route::post('requisitos/update/{id}','TramiteController@UpdateFilesRequisitos');
Route::post('chancar','TramiteController@chancarExonerado');
Route::put('tramite/update','TramiteController@updateTramiteRequisitos');
Route::put('tramite/update/requisito','TramiteController@aprobarRequisito');
Route::post('tramites/notification','TramiteController@notificacionUpdate');
Route::post('tramites/anular','TramiteController@anularTramite');
//-----------------PDFs
Route::get('fut/{idTramite}','PDF_FutController@pdf_fut');
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


// E-mail verification
// Route::get('/auth/verify/{code}', 'AuthController@verify');


//RUTAS DOCENTES
Route::post('docentes', 'DocenteController@GetDocente');
Route::post('cargaLectiva', 'DocenteController@getCursosDocentePrincipal');
Route::get('personasSuv', 'PersonaSuvController@index');
//RUTAS DESCARGA ZIP
Route::get('download/fotos', 'ZipController@downloadFotos');
//RUTAS IMPORTAR Y EXPORTAR EXCEL
Route::post('carnets/import/observados', 'CarnetController@import');
Route::post('carnets/import/aprobados', 'CarnetController@aprobadosImport');
Route::get('carnets/export', 'ExcelController@export');
Route::get('padron_sunedu', 'PadronController@padron');


//Roles
Route::get('roles', 'Tipo_UsuarioController@GetRoles');
//Cronograma
Route::get('cronogramas/all', 'CronogramaController@index');
Route::get('cronogramas/activos/{idDependencia}/{idTipo_tramite_unidad}', 'CronogramaController@getCronogramasActivos');
Route::get('cronogramas/search', 'CronogramaController@buscar');
Route::post('cronogramas/create', 'CronogramaController@store');
Route::put('cronogramas/update/{id}', 'CronogramaController@update');
Route::get('cronogramas/unidad/dependencia', 'CronogramaController@GetUnidadDependencia');

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
Route::put('grados/correccion', 'GradoController@cambiarEstado');
Route::put('grados/envio/facultad', 'GradoController@enviarFacultad');
Route::put('grados/envio/ura', 'GradoController@enviarUraa');
Route::put('grados/envio/escuela', 'GradoController@enviarEscuela');
Route::put('grados/registrar/libro', 'GradoController@registrarEnLibro');
Route::get('grados/firma/decano', 'GradoController@GetGradosFirmaDecano');
Route::get('grados/firma/secretaria', 'GradoController@GetGradosFirmaSecretaria');
Route::get('grados/firma/rector', 'GradoController@GetGradosFirmaRector');
Route::get('grados/pendientes/impresion/{nro_resolucion}', 'GradoController@GetGradosPendientesImpresion');
Route::get('grados/finalizados', 'GradoController@GetGradosFinalizados');
Route::post('grados/upload/{id}','GradoController@uploadDiploma');
// Route::get('grados/validados/secretaria', 'GradoController@GetGradosValidadosSecretaria');
Route::get('secretaria/observados', 'GradoController@GetGradosRechazadosSecretaria');
Route::get('grados/validados/secretaria/{idResolucion}', 'GradoController@GetGradosResolucion');
Route::get('resolucion/secretaria/{nro_resolucion}', 'GradoController@GetResolucion');
Route::get('grados/aprobados/secretaria', 'GradoController@GetGradosAprobadosSecretaria');
Route::get('modalidad/carpeta/{idTipo_tramite_unidad}', 'Modalidad_CarpetaController@getModalidadGrado');
Route::get('programas_estudios/carpeta', 'Programa_Estudios_CarpetaController@getProgramaEstudios');
Route::get('diplomas/carpeta/{idUnidad}/{idTipo_tramite_unidad}/{idDependencia_detalle}', 'Diploma_CarpetaController@getDiplomaCarpetas');
Route::put('grados/datos', 'GradoController@GuardarDatosDiploma');
Route::get('dependencia/escuelas/{id}', 'DependenciaController@getEscuelas');
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
Route::get('titulos/secretaria/observados', 'TituloController@GetTitulosRechazadosSecretaria');
//Route::get('titulos/validados/secretaria/{nro_resolucion}', 'TituloController@GetTitulosResolucion');
Route::get('titulos/aprobados/secretaria', 'TituloController@GetTitulosAprobadosSecretaria');
Route::put('titulos/datos', 'TituloController@GuardarDatosDiploma');
//Route::put('create/codigo', 'TituloController@createCodeDiploma');

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
Route::put('titulos/correccionSE', 'SegundaEspecialidadController@cambiarEstado');
Route::put('titulos/envio/facultadSE', 'SegundaEspecialidadController@enviarFacultad');
Route::put('titulos/envio/ura', 'TituloController@enviarUraa');
Route::put('titulos/envio/especialidad', 'SegundaEspecialidadController@enviarEscuela');
Route::put('titulos/registrar/libro', 'TituloController@registrarEnLibro');
Route::get('titulos/firma/decano', 'TituloController@GetTitulosFirmaDecano');
Route::get('titulos/firma/secretaria', 'TituloController@GetTitulosFirmaSecretaria');
Route::get('titulos/firma/rector', 'TituloController@GetTitulosFirmaRector');
Route::get('titulos/pendientes/impresion/{nro_resolucion}', 'TituloController@GetTitulosPendientesImpresion');
Route::get('titulos/finalizados', 'TituloController@GetTitulosFinalizados');
Route::post('titulos/upload/{id}','TituloController@uploadDiploma');
Route::get('titulos/secretaria/observados', 'TituloController@GetTitulosRechazadosSecretaria');
Route::get('titulos/validados/secretaria/{nro_resolucion}', 'TituloController@GetTitulosResolucion');
Route::get('titulos/aprobados/secretaria', 'TituloController@GetTitulosAprobadosSecretaria');
Route::put('titulos/datosSE', 'SegundaEspecialidadController@GuardarDatosDiploma');
Route::put('create/codigo', 'TituloController@createCodeDiploma');


//DEPENDENCIAS
Route::get('dependencias/{idUnidad}', 'DependenciaController@getDependenciasByUnidad');

// ACREDITACIONES
Route::get('acreditadas/all', 'AcreditacionController@index');
Route::post('acreditadas/create', 'AcreditacionController@store');
// RESOLUCIONES
Route::get('resoluciones/all', 'ResolucionController@index');
Route::post('resoluciones/create', 'ResolucionController@store');
Route::put('resoluciones/update/{id}', 'ResolucionController@update');
// OFICIOS
Route::get('oficios/all', 'OficioController@index');
Route::post('oficios/create', 'OficioController@store');
Route::put('oficios/update/{id}', 'OficioController@update');
//Reportes
Route::get('reporte/enviado/facultad', 'ReporteController@enviadoFacultad');
Route::get('reporte/enviado/ura', 'ReporteController@enviadoUra');
Route::get('reporte/enviado/secretaria', 'ReporteController@enviadoSecretariaGeneral');
