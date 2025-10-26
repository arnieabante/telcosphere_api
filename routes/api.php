<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BillingCategoryController;
use App\Http\Controllers\Api\ServerController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResource('users', UserController::class)->except(['update']);
    Route::patch('users/{uuid}', [UserController::class, 'update']);
    Route::put('users/{uuid}', [UserController::class, 'replace']);

    Route::apiResource('roles', RoleController::class)->except(['update']);
    Route::patch('roles/{uuid}', [RoleController::class, 'update']);
    Route::put('roles/{uuid}', [RoleController::class, 'replace']);

    Route::apiResource('modules', ModuleController::class)->except(['update']);
    Route::patch('modules/{uuid}', [ModuleController::class, 'update']);
    Route::put('modules/{uuid}', [ModuleController::class, 'replace']);

    // permissions
    // create permission - assign modules to roles AND set is_crud fields
    // update permission - re-assign modules to roles AND/OR change is_crud fields
    // delete permission - remove modules from roles
    Route::post('roles/{role_uuid}/modules/{module_uuid}/attach', [PermissionController::class, 'attachModule']);
    Route::post('roles/{role_uuid}/modules/{module_uuid}/detach', [PermissionController::class, 'detachModule']);

    Route::apiResource('billingcategories', BillingCategoryController::class)->except(['update']);
    Route::patch('billingcategories/{uuid}', [BillingCategoryController::class, 'update']);
    Route::put('billingcategories/{uuid}', [BillingCategoryController::class, 'replace']);

    Route::apiResource('servers', ServerController::class)->except(['update']);
    Route::patch('servers/{uuid}', [ServerController::class, 'update']);
    Route::put('servers/{uuid}', [ServerController::class, 'replace']);

});
