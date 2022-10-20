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
Route::get('tramite/certificados','CertificadoController@GetCertificados');
Route::post('tramite/asignar','TramiteController@AsignacionTramites');
Route::get('tramite/certificados/validados','CertificadoController@GetCertificadosValidados');
Route::get('tramite/certificados/asignados','CertificadoController@GetCertificadosAsignados');
Route::get('tramite/certificados/aprobados','CertificadoController@GetCertificadosAprobados');
Route::get('tramite/certificados/pendientes','CertificadoController@GetCertificadosPendientes');
Route::post('certificados/upload/{id}','CertificadoController@uploadCertificado');
Route::get('constancias/enviar/{id}','ConstanciaController@enviarConstancia');
Route::post('constancias/upload/{id}','ConstanciaController@uploadConstancia');
Route::get('tramite/certificados/firma_uraa','CertificadoController@GetCertificadosFirmaUraa');
Route::get('tramite/certificados/firma_decano','CertificadoController@GetCertificadosFirmaDecano');
Route::get('tramite/carnets','CarnetController@GetCarnets');
Route::get('tramite/carnets/regulares','CarnetController@GetCarnetsRegulares');
Route::get('tramite/carnets/duplicados','CarnetController@GetCarnetsDuplicados');
Route::get('tramite/carnets/asignados','CarnetController@GetCarnetsAsignados');
Route::get('tramite/carnets/aprobados','CarnetController@GetCarnetsAprobados');
Route::get('carnets/validacion/sunedu','CarnetController@EnvioValidacionSunedu');
Route::get('tramite/constancias','ConstanciaController@GetConstancias');
Route::get('tramite/constancias/validados','ConstanciaController@GetConstaciasValidados');
Route::get('tramite/constancias/asignados','ConstanciaController@GetConstaciasAsignados');
Route::get('tramite/constancias/firma_uraa','ConstanciaController@GetConstanciasFirmaUraa');
Route::get('bancos','BancoController@index');
Route::get('tipos_tramites','Tipo_TramiteController@index');
Route::get('sedes','SedeController@index');
Route::get('unidades','UnidadController@index');
Route::get('tipo_tramites_unidades/{idTipo_tramite}/{idUnidad}','Tipo_Tramite_UnidadController@getAllByTipo_tramiteUnidad');
Route::get('requisitos/{idTipo_tramite_unidad}','RequisitoController@getAllByTipo_tramite_unidad');
Route::get('facultades_alumno/{idUnidad}','PersonaController@DatosAlumno2');
Route::resource('motivos_certificado','Motivo_CertificadoController');
Route::resource('alumnosSE','PersonaSEController');


//VOUCHERS
Route::resource('/voucher','VoucherController');
Route::get('vouchers/pendientes','VoucherController@Pendientes');
Route::get('vouchers/aprobados','VoucherController@Aprobados');
Route::get('vouchers/rechazados','VoucherController@Rechazados');
Route::post('vouchers/update/{id}','TramiteController@updateVoucher');
Route::post('requisitos/update/{id}','TramiteController@UpdateFilesRequisitos');
Route::put('tramite/update','TramiteController@updateTramiteRequisitos');
//-----------------PDFs
Route::get('fut/{idTramite}','PDF_FutController@pdf_fut');
Route::get('constancia/{idTramite}','PDF_ConstanciaController@pdf_constancia');
//-------------------------------

// Route::resource('cargos','CargoController');
Route::resource('personas','PersonaController');
Route::get('users/all','UserController@index');
Route::get('users/search','UserController@buscar');
Route::get('usuario/uraa','UserController@getUsuariosUraa');
Route::put('users/update/{id}','UserController@update');
Route::post('users/create','UserController@store');
Route::put('settings','UserController@settings');
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
//RUTAS IMPORTAR EXCEL
Route::post('carnets/import', 'TramiteController@import');


//Roles
Route::get('roles', 'Tipo_UsuarioController@GetRoles');
//Cronograma
Route::get('cronogramas/all', 'CronogramaController@index');
Route::get('cronogramas/activos/{idDependencia}/{idTipo_tramite_unidad}', 'CronogramaController@getCronogramasActivos');
Route::get('cronogramas/search', 'CronogramaController@buscar');
Route::post('cronogramas/create', 'CronogramaController@store');
Route::put('cronogramas/update/{id}', 'CronogramaController@update');

//GRADOS Y TITULOS
Route::get('grados/titulos/validados', 'GradoController@GetGradosValidados');
Route::get('grados/titulos/aprobados', 'GradoController@GetGradosAprobados');

//DEPENDENCIAS
Route::get('dependencias/{idUnidad}', 'DependenciaController@getDependenciasByUnidad');
