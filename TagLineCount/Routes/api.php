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

Route::middleware('auth:api')->get('/taglinecount', function (Request $request) {
    return $request->user();
});
// Route::apiResource('taglinecounts', \Modules\TagLineCount\Http\Controllers\TagLineCountController::class);
Route::middleware('auth:api')->middleware('jwt')->apiResource('taglines', \Modules\TagLineCount\Http\Controllers\TagLineController::class);