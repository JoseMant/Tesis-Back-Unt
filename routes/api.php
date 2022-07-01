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
});

// Route::prefix('auth')->group(function () {
//     Route::post('login', 'AuthController@login');
//     Route::middleware('auth.jwt')->group(function () {
//         Route::post('logout', 'AuthController@logout');
//         Route::post('refresh', 'AuthController@refresh');
//         Route::post('me', 'AuthController@me');
//     });
// });


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});



Route::resource('bancos','BancoController');
Route::resource('cargos','CargoController');
Route::resource('personas','PersonaController');
Route::resource('usuarios','UserController');
Route::get('personas/datosAlumno/{dni}','PersonaController@DatosAlumno');

Route::group(['middleware' => ['api']], function () {
// Route::post('login','LoginController@Login');
Route::get('dato','LoginController@Dato');
});



// Login
// Route::Post('login','LoginController@DatosAlumno');


// E-mail verification
Route::get('/register/verify/{code}', 'UsuarioController@verify');