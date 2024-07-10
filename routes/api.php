<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// validate student
Route::post('/validate-student', 'App\Http\Controllers\UserController@validateStudent');

//verify payment
Route::post('/verify-payment', 'App\Http\Controllers\UserController@verifyPayment');


 //webhook
 Route::post('/paystack/webhook', 'App\Http\Controllers\UserController@paystack_webhook')->name('paystack_webhook');

 //call back
 Route::get('/payment/callback', 'App\Http\Controllers\UserController@paystack_callback')->name('paystack_callback');

 //initialize payment
 Route::post('initialize-payment', 'App\Http\Controllers\UserController@initialize_payment')->name('pay');

 // test
    Route::get('/test', 'App\Http\Controllers\UserController@test');