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
    Route::post('verifyCodePassword', 'AuthController@verifyCodePassword');
    Route::post('ResetPassword', 'AuthController@ResetPassword');
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//TRÁMITE
Route::resource('tramites','TramiteController');
Route::get('tramite/usuario','TramiteController@GetByUser');
Route::post('tramite/update/{id}','TramiteController@update');
Route::get('tramite/usuario/all','TramiteController@GetTramitesByUser');
Route::get('tramite/certificados','CertificadoController@GetCertificados');
Route::post('tramite/certificados/asignar','TramiteController@AsignacionCertificados');
Route::get('tramite/certificados/validados','CertificadoController@GetCertificadosValidados');
Route::get('tramite/certificados/asignados','CertificadoController@GetCertificadosAsignados');
Route::get('tramite/certificados/aprobados','CertificadoController@GetCertificadosAprobados');
Route::get('tramite/carnets','CarnetController@GetCarnets');
Route::get('tramite/carnets/validados','CarnetController@GetCarnetsValidados');
Route::get('tramite/carnets/asignados','CarnetController@GetCarnetsAsignados');
Route::get('tramite/carnets/aprobados','CarnetController@GetCarnetsAprobados');
Route::get('tramite/constancias','ConstanciaController@GetConstancias');
Route::get('tramite/constancias/validados','ConstanciaController@GetConstaciasValidados');
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
Route::put('certificados/update/{id}','TramiteController@updateTramiteRequisitos');
Route::get('fut/{idTramite}','PDF_FutController@pdf_fut');
//-------------------------------

// Route::resource('cargos','CargoController');
Route::resource('personas','PersonaController');
Route::resource('usuarios','UserController');
Route::get('usuario/search','UserController@buscar');
Route::get('usuario/uraa','UserController@getUsuariosUraa');
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
Route::get('/auth/verify/{code}', 'UserController@verify');


//RUTAS DOCENTES
Route::post('docentes', 'DocenteController@GetDocente');
Route::post('cargaLectiva', 'DocenteController@getCursosDocentePrincipal');
Route::get('personasSuv', 'PersonaSuvController@index');
//RUTAS DESCARGA ZIP
Route::get('download/fotos', 'ZipController@downloadFotos');
//RUTAS IMPORTAR EXCEL
Route::post('import', 'TramiteController@import');