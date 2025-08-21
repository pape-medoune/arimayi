<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\HealthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Invoice Module API Routes
|--------------------------------------------------------------------------
*/

// Routes pour la gestion des clients
Route::apiResource('clients', ClientController::class);

// Routes pour la gestion des factures
Route::apiResource('invoices', InvoiceController::class);

// Routes supplémentaires pour les statistiques
Route::get('clients/{client}/invoices', [ClientController::class, 'getClientInvoices']);
Route::get('invoices/stats', [InvoiceController::class, 'getInvoiceStats']);

// Route de vérification de l'état de l'API
Route::get('health', [HealthController::class, 'check']);
