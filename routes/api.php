<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
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
});

/*
TODO:
1. repo - create development and staging - DONE
3. modules - add icon field - DONE
4. routes - use uuid instead of id - DONE
2. users/roles - move foriegn key to users table

*/