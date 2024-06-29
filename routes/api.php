<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// validate student
Route::post('/validate-student', 'App\Http\Controllers\UserController@validateStudent');