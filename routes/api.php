<?php

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
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout'])->middleware('auth');
Route::get('/user/{user_id}', [UserController::class, 'getProfile'])->middleware('auth');
Route::put('/user/profile', [UserController::class, 'updateProfile'])->middleware('auth');
Route::put('/user/password', [UserController::class, 'updatePassword'])->middleware('auth');

Route::get('/timeline', [MessageController::class, 'index']);
Route::post('/message', [MessageController::class, 'create']);
Route::get('/message/{message_id}', [MessageController::class, 'show']);
