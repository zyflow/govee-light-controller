<?php

use App\Http\Controllers\GoveeController;
use App\Http\Controllers\ManagementController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('lights', [GoveeController::class, 'index']);
Route::get('set-sunset-time', [GoveeController::class, 'setSunetTime']); # once daily
Route::get('check-lights', [GoveeController::class, 'checkLights']);   # every 5 minutes
Route::get('flash', [GoveeController::class, 'flashTurnOn']);   # every 5 minutes
Route::get('turn-off', [GoveeController::class, 'turnOff']);
//Route::get('deep-sleep', [\App\Http\Controllers\DeepSleepController::class, 'index']);
Route::get('schedule', [ManagementController::class, 'index']);

