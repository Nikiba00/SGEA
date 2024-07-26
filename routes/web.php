<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AreasController;
use App\Http\Controllers\ArticulosController;
use App\Http\Controllers\ArticulosAutoresController;
use App\Http\Controllers\ComiteController;
use App\Http\Controllers\EventosController;
use App\Http\Controllers\ParticipantesController;
use App\Http\Controllers\RevisoresArticulosController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\LoginController;


Route::resource('areas', AreasController::class)->middleware('auth')->middleware('can:areas.index');
Route::resource('comite_editorial', Comite_EditorialController::class)->middleware('auth');
Route::resource('eventos', EventosController::class)->names('eventos')->middleware('auth');
Route::resource('usuarios', UsuariosController::class)->middleware('auth');
// PARTICIPANTES
Route::resource('participantes', ParticipantesController::class)->except(['index'])->middleware('auth');
Route::get('participantes/evento/{eventoId}', [ParticipantesController::class, 'index'])->name('participantes.evento.index')->middleware('auth');
Route::delete('participantes/{eventoId}/{usuarioId}', [ParticipantesController::class, 'destroy'])->name('participantes.destroy')->middleware('auth')->middleware('can:participantes.destroy');
//COMITE-EDITORIAL
Route::get('comite/evento/{eventoId}', [ComiteController::class,'Index'])->name('comite.evento.index')->middleware('auth');
Route::delete('comite/{eventoId}/{usuarioId}', [ComiteController::class, 'remove'])->name('comite.destroy')->middleware('auth')->middleware('can:comite.destroy');
Route::resource('comite', ComiteController::class)->except(['index'])->middleware('auth');

//ARTICULOS
Route::resource('articulos', ArticulosController::class)->except(['index'])->middleware('auth');
Route::get('{eventoId}/articulos/', [ArticulosController::class, 'index'])->name('articulos.evento.index')->middleware('auth');
Route::get('{evento_id}/articulo/{id}', [ArticulosController::class, 'show'])->middleware('auth');
Route::get('{evento_id}/articulo/{id}/edit', [ArticulosController::class, 'edit'])->middleware('auth');
Route::put('{evento_id}/articulo/{id}', [ArticulosController::class, 'update']);
    //Rutas AJAX
    Route::post('/check-author', [ArticulosController::class, 'checkAuthor'])->name('revisar-existencia');
    Route::post('/verify-curp', [LoginController::class, 'verifyCurp'])->name('verify-curp');
    Route::post('/insert-user', [UsuariosController::class, 'insertUser'])->name('insertar-usuario');
//AUTORES
Route::resource('autores', ArticulosAutoresController::class)->except(['index'])->middleware('auth');
Route::get('{eventoId}/autores/', [ArticulosAutoresController::class, 'index'])->name('autores.index')->middleware('auth');
Route::get('{eventoId}/autores/{id}/edit', [ArticulosAutoresController::class, 'edit'])->middleware('auth');


// REVISORES DE ARTICULOS:
Route::resource('revisores_articulos', RevisoresArticulosController::class)->middleware('auth');
Route::get('revisores_articulos/evento/{eventoId}', [RevisoresArticulosController::class, 'index'])->name('revisores_articulos.evento.index')->middleware('auth');
Route::delete('revisores_articulos/{eventoId}/{usuarioId}/{articuloId}', [RevisoresArticulosController::class, 'destroy'])->name('revisores_articulos.destroy')->middleware('auth')->middleware('can:revisores_articulos.destroy');

//LOGIN - REGISTER
Route::view('/login',"login")->name('login');

 Route::post('/registrar', [LoginController::class, 'register'])->name('registrar');

 Route::post('/verificar-email', [LoginController::class, 'verificarEmail'])->name('verificar-email');
 Route::post('/reenviar-codigo', [LoginController::class, 'reenviarCodigo'])->name('reenviar-codigo');
 Route::post('/setPassword', [LoginController::class, 'setPassword'])->name('Password');
 Route::post('/reset-password', [LoginController::class, 'resetPassword'])->name('password.reset');
 
 Route::post('/validar-registro',[LoginController::class,'register'])->name('validar-registro');
 Route::post ('/inicia-sesion',[LoginController::class,'login'])->name('inicia-sesion');
 Route::get('/logout',[LoginController::class,'logout'])->name('logout');


 // DASHBOARDS

 Route::get('/redirect', [UsuariosController::class, 'redirectToAppropriateView'])->name('user.redirect');
 Route::get('/{acronimo}-index/{edicion}', [LoginController::class, 'index'])->name('evento.index');
 

 
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });


Route::get('/', function () {
    return view('login');
});

Route::get('/forgot-password', function () {
    return view('ResetPass');
});
Route::get('/registro', function () {
    return view('register');
});

Route::get('/dashboard', function () {
    return view('HomeViews.dashboard');
})->name('dashboard');

Route::get('pruebas', function () {
    return view('pruebas');
});
Route::get('pruebas2', function () {
    return view('pruebas2');
});