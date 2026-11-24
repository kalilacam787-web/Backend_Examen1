<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CocheController;
use App\Http\Controllers\Api\RevisionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('/status', [ApiController::class, 'status']);

Route::apiResource('clientes', ClienteController::class);
Route::apiResource('coches', CocheController::class)->parameters(['coches' => 'coche']);
Route::get('/coches/{coche}/revisiones', [RevisionController::class, 'forCoche']);
Route::apiResource('revisiones', RevisionController::class)->parameters(['revisiones' => 'revision']);
