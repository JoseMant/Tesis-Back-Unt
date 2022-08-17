<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::Post('/usuarios', 'UsuarioController@store');
// Route::get('/personas/{dni}/{password}', 'PersonaController@Login');

Route::get('test', function () {

    $user = [
        'name' => 'Harsukh Makwana',
        'info' => 'Laravel & Python Devloper'
    ];

    \Mail::to('kevinkjjuarez@gmail.com')->send(new \App\Mail\NewMail($user));

    dd("success");

});


Route::view('/email', 'emails.registro_tramite');


// AUTENTICADOR
Route::resource('/autenticador', AutenticadorController::class);
// Route::get('/', ['as'=>'autenticador.qr','uses'=>'AutenticadorController@AuthQr']);
// Route::post('/check','AutenticadorController@CheckQr');