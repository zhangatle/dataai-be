<?php

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
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SearchController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(["auth:api", "cors"])->group(function () {
    Route::post('/suggest', [SearchController::class, 'suggest']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
