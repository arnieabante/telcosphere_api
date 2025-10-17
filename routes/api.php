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
    Route::patch('users/{id}', [UserController::class, 'update']);
    Route::put('users/{id}', [UserController::class, 'replace']);

    Route::apiResource('roles', RoleController::class)->except(['update']);
    Route::patch('roles/{id}', [RoleController::class, 'update']);
    Route::put('roles/{id}', [RoleController::class, 'replace']);

    Route::apiResource('modules', ModuleController::class)->except(['update']);
    Route::patch('modules/{id}', [ModuleController::class, 'update']);
    Route::put('modules/{id}', [ModuleController::class, 'replace']);
});