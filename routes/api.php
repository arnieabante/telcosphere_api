<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BillingCategoryController;
use App\Http\Controllers\Api\BillingItemController;
use App\Http\Controllers\Api\InternetplanController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\TicketCategoryController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\BillingController;
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
    Route::post('roles/{uuid}/modules/attach', [RoleController::class, 'attach']);
    Route::post('roles/{uuid}/modules/detach', [RoleController::class, 'detach']);

    Route::apiResource('modules', ModuleController::class)->except(['update']);
    Route::patch('modules/{uuid}', [ModuleController::class, 'update']);
    Route::put('modules/{uuid}', [ModuleController::class, 'replace']);

    Route::apiResource('billingcategories', BillingCategoryController::class)->except(['update']);
    Route::patch('billingcategories/{uuid}', [BillingCategoryController::class, 'update']);
    Route::put('billingcategories/{uuid}', [BillingCategoryController::class, 'replace']);

    Route::apiResource('servers', ServerController::class)->except(['update']);
    Route::patch('servers/{uuid}', [ServerController::class, 'update']);
    Route::put('servers/{uuid}', [ServerController::class, 'replace']);

    Route::apiResource('internetplans', InternetplanController::class)->except(['update']);
    Route::patch('internetplans/{uuid}', [InternetplanController::class, 'update']);
    Route::put('internetplans/{uuid}', [InternetplanController::class, 'replace']);

    Route::apiResource('clients', ClientController::class)->except(['update']);
    Route::patch('clients/{uuid}', [ClientController::class, 'update']);
    Route::put('clients/{uuid}', [ClientController::class, 'replace']);
    
    Route::apiResource('ticketcategories', TicketCategoryController::class)->except(['update']);
    Route::patch('ticketcategories/{uuid}', [TicketCategoryController::class, 'update']);
    Route::put('ticketcategories/{uuid}', [TicketCategoryController::class, 'replace']);

    Route::apiResource('tickets',TicketController::class)->except(['update']);
    Route::patch('tickets/{uuid}', [TicketController::class, 'update']);
    Route::put('tickets/{uuid}', [TicketController::class, 'replace']);

    Route::apiResource('billings', BillingController::class)->except(['update']);
    Route::patch('billings/{uuid}', [BillingController::class, 'update']);
    Route::put('billings/{uuid}', [BillingController::class, 'replace']);

    Route::apiResource('billingitems', BillingItemController::class)->except(['update']);
    Route::patch('billingitems/{uuid}', [BillingItemController::class, 'update']);
    Route::put('billingitems/{uuid}', [BillingItemController::class, 'replace']);

});
