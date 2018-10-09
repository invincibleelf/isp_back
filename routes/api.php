<?php

use Illuminate\Http\Request;

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
//Route::middleware('api')->post('/register' , 'APIControllers\AuthController@register');
//Route::middleware('api')->post('/login' , 'APIControllers\AuthController@login');
//Route::middleware('api')->post('/password-reset-email' , 'APIControllers\AuthController@passwordResetEmail');
//Route::middleware('api')->post('/reset-password' , 'APIControllers\AuthController@resetPassword');
////Route::middleware('api')->get('/test', 'APIControllers\AuthController@test');
//Route::group(['middleware' => ['auth:api']], function () {
//    //Route::any('/your_route', 'APIControllers\YourController@index');
//  Route::get('/test', 'APIControllers\AuthController@test');
//});


Route::post('/register', [
    'uses' => 'AuthController@register',
    'as' => 'register',
    'middleware' => 'api'
]);


Route::post('/login', [
    'uses' => 'AuthController@login',
    'as' => 'register',
    'middleware' => 'api'
]);

Route::post('/password-reset-email', [
    'uses' => 'AuthController@passwordResetEmail',
    'as' => 'password-reset-email',
    'middleware' => 'api'
]);

Route::post('/reset-password', [
    'uses' => 'AuthController@resetPassword',
    'as' => 'reset-password',
    'middleware' => 'api'
]);

Route::post('/show', [
    'uses' => 'AuthController@show',
    'as' => 'show',
    'middleware' => ['api', 'roles'],
    'role' => 'student'
]);

Route::post('/details', [
    'uses' => 'AuthController@details',
    'as' => 'details',
    'middleware' => ['api', 'roles'],
    'role' => ['student', 'agent']
]);


//Route::post('/user/{id}', [
//    'uses' => 'UserController@showProfile',
//    'middleware' => ['api', 'roles'],
//    'role' => 'student'
//]);

Route::get('/showProfile', [
    'uses' => 'UserController@showProfile',
    'middleware' => ['api', 'roles'],
    'role' => ['student', 'agent', 'councilor']
]);
