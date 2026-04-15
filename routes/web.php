<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
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

Route::post('/login', [LoginController::class, 'login'])->middleware('guest')->name('login.submit');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
    ->middleware('guest')
    ->name('password.email');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
    ->middleware('guest')
    ->name('password.update');
Route::post('/logout', [LoginController::class, 'logout'])->middleware(['auth', 'active'])->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/api/user', function () {
        return response()->json(auth()->user());
    });
});

Route::get('/', function () {
    return view('spa');
});

/*
| SPA shell for Vue Router (history mode). Register explicit GET routes above this line.
| Excludes: /api/* (JSON API), /sanctum/* (CSRF cookie), /up (Laravel health).
*/
Route::get('/{spa}', function () {
    return view('spa');
})->where(
    'spa',
    '^(?!(?:api|sanctum)(?:/|$)|up$).+$',
);
