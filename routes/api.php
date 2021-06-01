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

Route::middleware(["auth:api", "cors"])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

});
Route::post('/login', [AuthController::class, 'login']);
