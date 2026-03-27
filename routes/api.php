<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
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

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    /*
    |--------------------------------------------------------------------------
    | User API Resource
    |--------------------------------------------------------------------------
    */
    Route::post('users/bulk-destroy', [UserController::class, 'bulkDestroy']);
    Route::apiResource('users', UserController::class);
    Route::get('departments/options', [DepartmentController::class, 'options']);
    Route::apiResource('departments', DepartmentController::class);

    Route::get('permissions', [PermissionController::class, 'index']);
    Route::get('roles/options', [RoleController::class, 'options']);
    Route::get('roles', [RoleController::class, 'index']);
    Route::apiResource('roles', RoleController::class)->except(['index'])->names([
        'store' => 'roles.store',
        'show' => 'roles.show',
        'update' => 'roles.update',
        'destroy' => 'roles.destroy',
    ]);
});
