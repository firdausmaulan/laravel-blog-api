<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogPostController;

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

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:api')->group(function () {
    Route::get('users', [AuthController::class, 'search']); // list users with pagination and search
    Route::get('user/{id}', [AuthController::class, 'detail']); // show user details
    Route::put('user/{id}', [AuthController::class, 'update']); // update user
    Route::delete('user/{id}', [AuthController::class, 'destroy']); // delete user
});

Route::middleware('auth:api')->group(function () {
    Route::get('posts', [BlogPostController::class, 'search']); // list posts with pagination and search
    Route::get('post/{id}', [BlogPostController::class, 'detail']); // show a single post
    Route::post('post', [BlogPostController::class, 'create']); // create a new post
    Route::put('post/{id}', [BlogPostController::class, 'update']); // update post
    Route::delete('post/{id}', [BlogPostController::class, 'destroy']); // delete post
});
