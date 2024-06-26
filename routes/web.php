<?php

use App\Http\Controllers\LayoutsController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\Website\ShopController;
use Illuminate\Support\Facades\Route;

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
    return view('home');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/forgot', function () {
    return view('forgot');
});

Route::get('/verifikasi', function () {
    return view('verifikasi');
});

Route::get('/ganti', function () {
    return view('ganti');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

Route::get('/dropzone', 'UploadController@dropzone')->name ('dropzone'); 
Route::post('/dropzone/store', 'UploadController@dropzone_store')->name( 'dropzone.store' ); 


Route::get('/layouts/index', [LayoutsController::class, 'index']);
Route::get('/layouts/event', [LayoutsController::class, 'event']);
Route::get('/layouts/tambah', [LayoutsController::class, 'tambah']);
Route::get('/layouts/promo', [LayoutsController::class, 'promo']);
Route::get('/layouts/informasi', [LayoutsController::class, 'informasi']);

Route::get('/session/create', 'SessionController@create');

Route::get('/session/show', 'SessionController@show');

Route::get('/session/delete', 'SessionController@delete');

Route::get('/pegawai/{nama}', 'PegawaiController@index');

// Route::post('/upload/proses', [UploadController::class, 'proses_upload'])->name('upload.proses');
// Route::get('/upload', [UploadController::class, 'upload'])->name('upload');
Route::post('/upload/proses', [UploadController::class, 'proses_upload'])->name('upload.proses');


// Route::get('/dropzone', [UploadController::class, 'dropzone'])->name('dropzone');
// Route::post('/dropzone/store', [UploadController::class, 'dropzone_strore']) ->name('dropzone.store');

Route::get('/layouts', [LayoutsController::class, 'index']);


Route::post('/upload/proses', [ShopController::class, 'createShop'])->name('add.toko');

Route::post('/upload/proses1', [ShopController::class, 'createShop1'])->name('edit.toko');
Route::delete('/shops/{id_shop}', [ShopController::class, 'deleteShop'])->name('delete.toko');

