<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmailLogController;
use App\Http\Controllers\GlAccountController;
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

    Route::post('email-logs/bulk-destroy', [EmailLogController::class, 'bulkDestroy']);
    Route::get('email-logs', [EmailLogController::class, 'index']);
    Route::get('email-logs/{email_log}', [EmailLogController::class, 'show']);
    Route::delete('email-logs/{email_log}', [EmailLogController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | User API Resource
    |--------------------------------------------------------------------------
    */
    Route::post('users/bulk-destroy', [UserController::class, 'bulkDestroy']);
    Route::post('users/bulk-update-department', [UserController::class, 'bulkUpdateDepartment']);
    Route::get('users/export', [UserController::class, 'export']);
    Route::apiResource('users', UserController::class);
    Route::get('departments/options', [DepartmentController::class, 'options']);
    Route::get('departments/export', [DepartmentController::class, 'export']);
    Route::apiResource('departments', DepartmentController::class);

    Route::get('gl-accounts/export', [GlAccountController::class, 'export']);
    Route::apiResource('gl-accounts', GlAccountController::class);

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
