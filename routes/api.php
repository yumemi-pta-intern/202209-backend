<?php

use App\Http\Controllers\LikeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MessageController;

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

Route::post('/signup', [UserController::class, 'signup']);
Route::get('/timeline', [MessageController::class, 'index']);
Route::post('/message', [MessageController::class, 'create']);
Route::get('/message/{message_id}', [MessageController::class, 'show']);

Route::post('/message/{message_id}/like', [LikeController::class, 'create']);
Route::delete('/message/{message_id}/like', [LikeController::class, 'delete']);