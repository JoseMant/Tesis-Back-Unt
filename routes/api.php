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
    Route::get('refresh', 'AuthController@refresh');
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
Route::get('bancos','BancoController@index');
Route::get('tipos_tramites','Tipo_TramiteController@index');
Route::get('sedes','SedeController@index');
Route::get('unidades','UnidadController@index');
Route::get('tipos_tramites_unidades/{idTipo_tramite}/{idUnidad}','Tipo_Tramite_UnidadController@getById');
Route::get('facultades_alumno/{idUnidad}','PersonaController@DatosAlumno2');





//-------------------------------

// Route::resource('cargos','CargoController');
Route::resource('personas','PersonaController');
Route::resource('usuarios','UserController');
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
//VOUCHERS
Route::resource('vouchers','VoucherController');

// E-mail verification
Route::get('/register/verify/{code}', 'UserController@verify');
// Route::post('/auth/forgot-password', 'UserController@forgotPassword');
// Route::post('/auth/verifyCodePassword', 'UserController@verifyCodePassword');
// Route::post('/auth/ResetPassword', 'UserController@ResetPassword');

// Route::resource('personasSuv','PersonaSuvController');