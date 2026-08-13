<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BillingCategoryController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ExpenseItemController;
use App\Http\Controllers\Api\BillingItemController;
use App\Http\Controllers\Api\InternetplanController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\TicketCategoryController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PesoWifiAreaController;
use App\Http\Controllers\Api\PesoWifiClientController;
use App\Http\Controllers\Api\PesoWifiHarvestController;
use App\Http\Controllers\Api\HomepageSettingsController;
use App\Http\Controllers\Api\AboutUsSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('sites/url/{url}', [SiteController::class, 'showByUrl']);
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

    Route::get('clients/find', [ClientController::class, 'find']);
    Route::apiResource('clients', ClientController::class)->except(['update']);
    Route::patch('clients/{uuid}', [ClientController::class, 'update']);
    Route::put('clients/{uuid}', [ClientController::class, 'replace']);
    Route::get('clients/{uuid}/billings', [ClientController::class, 'billings']);
    Route::get('clients/{uuid}/soa', [ClientController::class, 'fetchClientSOA']);
    Route::get('clients/{uuid}/accounthistory', [ClientController::class, 'fetchAccountHistory']);

    Route::apiResource('ticketcategories', TicketCategoryController::class)->except(['update']);
    Route::patch('ticketcategories/{uuid}', [TicketCategoryController::class, 'update']);
    Route::put('ticketcategories/{uuid}', [TicketCategoryController::class, 'replace']);

    Route::apiResource('tickets',TicketController::class)->except(['update']);
    Route::patch('tickets/{uuid}', [TicketController::class, 'update']);
    Route::put('tickets/{uuid}', [TicketController::class, 'replace']);

    Route::get('billings/find', [BillingController::class, 'find']);
    Route::apiResource('billings', BillingController::class)->except(['update','find']);
    Route::patch('billings/{uuid}', [BillingController::class, 'update']);
    Route::put('billings/{uuid}', [BillingController::class, 'replace']);

    Route::apiResource('billingitems', BillingItemController::class)->except(['update']);
    Route::patch('billingitems/{uuid}', [BillingItemController::class, 'update']);
    Route::put('billingitems/{uuid}', [BillingItemController::class, 'replace']);

    Route::apiResource('employees', EmployeeController::class)->except(['update']);
    Route::patch('employees/{uuid}', [EmployeeController::class, 'update']);
    Route::put('employees/{uuid}', [EmployeeController::class, 'replace']);

    Route::apiResource('payments', PaymentController::class)->except(['update']);
    Route::patch('payments/{uuid}', [PaymentController::class, 'update']);
    Route::put('payments/{uuid}', [PaymentController::class, 'replace']);

    Route::apiResource('expensecategories', ExpenseCategoryController::class)->except(['update']);
    Route::patch('expensecategories/{uuid}', [ExpenseCategoryController::class, 'update']);
    Route::put('expensecategories/{uuid}', [ExpenseCategoryController::class, 'replace']);

    Route::apiResource('expenses', ExpenseController::class)->except(['update']);
    Route::patch('expenses/{uuid}', [ExpenseController::class, 'update']);
    Route::put('expenses/{uuid}', [ExpenseController::class, 'replace']);

    Route::apiResource('expenseitems', ExpenseItemController::class)->except(['update']);
    Route::patch('expenseitems/{uuid}', [ExpenseItemController::class, 'update']);
    Route::put('expenseitems/{uuid}', [ExpenseItemController::class, 'replace']);

    Route::apiResource('sites', SiteController::class)->except(['update']);
    Route::patch('sites/{uuid}', [SiteController::class, 'update']);
    Route::put('sites/{uuid}', [SiteController::class, 'replace']);

    Route::apiResource('pesowifiareas', PesoWifiAreaController::class)->except(['update']);
    Route::patch('pesowifiareas/{uuid}', [PesoWifiAreaController::class, 'update']);
    Route::put('pesowifiareas/{uuid}', [PesoWifiAreaController::class, 'replace']);

    Route::apiResource('pesowificlients', PesoWifiClientController::class)->except(['update']);
    Route::patch('pesowificlients/{uuid}', [PesoWifiClientController::class, 'update']);
    Route::put('pesowificlients/{uuid}', [PesoWifiClientController::class, 'replace']);

    Route::apiResource('pesowifiharvests', PesoWifiHarvestController::class)->except(['update']);
    Route::patch('pesowifiharvests/{uuid}', [PesoWifiHarvestController::class, 'update']);
    Route::put('pesowifiharvests/{uuid}', [PesoWifiHarvestController::class, 'replace']);
    Route::get('pesowifi/dashboard', [PesoWifiHarvestController::class, 'dashboard']);

    Route::apiResource('homepagesettings', HomepageSettingsController::class)->except(['update']);
    Route::patch('homepagesettings/{uuid}', [HomepageSettingsController::class, 'update']);
    Route::put('homepagesettings/{uuid}', [HomepageSettingsController::class, 'replace']);

    Route::apiResource('aboutussettings', AboutUsSettingsController::class)->except(['update']);
    Route::patch('aboutussettings/{uuid}', [AboutUsSettingsController::class, 'update']);
    Route::put('aboutussettings/{uuid}', [AboutUsSettingsController::class, 'replace']);

});
