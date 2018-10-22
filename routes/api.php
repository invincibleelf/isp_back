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

// api for the usage of different payment
Route::resource('payment','PaymentControllers\PaymentController');

Route::post('payment/HYLcomplete','PaymentControllers\PaymentController@HYLcomplete');


/***
 *
 * Routes for User Login and egistration
 *
 */

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
    'uses' => 'PasswordController@passwordResetEmail',
    'as' => 'password-reset-email',
    'middleware' => 'api'
]);

Route::post('/reset-password', [
    'uses' => 'PasswordController@resetPassword',
    'as' => 'reset-password',
    'middleware' => 'api'
]);

Route::post('/createCouncilor', [
    'uses' => 'AuthController@createCouncilor',
    'middleware' => ['api', 'roles'],
    'role' => "agent"
]);

Route::post('/createStudent', [
    'uses' => 'AuthController@createStudentByCouncilor',
    'middleware' => ['api', 'roles'],
    'role' => "councilor"
]);

Route::post('/change-password', [
    'uses' => 'PasswordController@changePassword',
    'middleware' => ['api', 'roles'],
    'role' => ['student', 'agent', 'councilor']
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


/***
 *
 * Routes for getting and updating Current Logged User
 *
 */


Route::get('/showProfile', [
    'uses' => 'UserController@showProfile',
    'middleware' => ['api', 'roles'],
    'role' => ['student', 'agent', 'councilor']
]);

Route::put('/updateProfile', [
    'uses' => 'UserController@updateProfile',
    'middleware' => ['api', 'roles'],
    'role' => ['student', 'agent', 'councilor']
]);


/***
 *
 * Routes for getting and updating Student Information
 *
 */
Route::get('/users/students', [
    'uses' => 'UserController@getStudents',
    'middleware' => ['api', 'roles'],
    'role' => 'councilor'
]);

Route::get('/users/student/{id}', [
    'uses' => 'UserController@getStudent',
    'middleware' => ['api', 'roles'],
    'role' => 'councilor'
]);

Route::put('/users/student/{id}', [
    'uses' => 'UserController@updateStudent',
    'middleware' => ['api', 'roles'],
    'role' => 'councilor'
]);

Route::delete('/users/student/{id}', [
    'uses' => 'UserController@deleteStudent',
    'middleware' => ['api', 'roles'],
    'role' => 'councilor'
]);

Route::post('/createStudent', [
    'uses' => 'AuthController@createStudentByCouncilor',
    'middleware' => ['api', 'roles'],
    'role' => "councilor"
]);


/***
 *
 * Routes for getting and updating Councilor Information
 *
 */

Route::get('/users/councilors', [
    'uses' => 'UserController@getCouncilors',
    'middleware' => ['api', 'roles'],
    'role' => 'agent'
]);

Route::get('/users/councilor/{id}', [
    'uses' => 'UserController@getCouncilor',
    'middleware' => ['api', 'roles'],
    'role' => 'agent'
]);

Route::put('/users/councilor/{id}', [
    'uses' => 'UserController@updateCouncilor',
    'middleware' => ['api', 'roles'],
    'role' => 'agent'
]);

Route::delete('/users/councilor/{id}', [
    'uses' => 'UserController@deleteCouncilor',
    'middleware' => ['api', 'roles'],
    'role' => 'agent'
]);


/***
 *
 * Routes for transfering students between councilors
 *
 */
Route::post('/transfer-student', [
    'uses' => 'UserController@transferStudents',
    'middleware' => ['api', 'roles'],
    'role' => "agent"
]);


