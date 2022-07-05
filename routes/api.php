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
    Route::post('register', 'AuthController@register');
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('SignInUsingToken', 'AuthController@SignInUsingToken');
    Route::get('me', 'AuthController@me');
    Route::get('refresh', 'AuthController@refresh');
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});



Route::resource('bancos','BancoController');
Route::resource('cargos','CargoController');
Route::resource('personas','PersonaController');
Route::resource('usuarios','UserController');
Route::get('personas/datosAlumno/{dni}','PersonaController@DatosAlumno');

//TIPOS DE TRÁMITE
Route::resource('tipos_tramites','Tipo_TramiteController');
//ESTADO DE TRÁMITE
Route::resource('estados_tramites','Estado_TramiteController');
//TRÁMITE
Route::resource('tramites','TramiteController');
//REQUISITOS
Route::resource('requisitos','RequisitoController');
//TRÁMITES_REQUISITOS
Route::resource('tramites_requisitos','Tramite_RequisitoController');


// E-mail verification
Route::get('/register/verify/{code}', 'UserController@verify');