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
Route::resource('payment', 'PaymentControllers\PaymentController');

Route::post('payment/HYLcomplete', 'PaymentControllers\PaymentController@HYLcomplete');


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

/***
 *
 * Routes for managing password
 *
 */
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

Route::post('/change-password', [
    'uses' => 'PasswordController@changePassword',
    'middleware' => ['api', 'roles'],
    'role' => ['student', 'agent', 'councilor']
]);

/***
 *
 * Routes for getting and updating Current Logged User
 *
 */

Route::get('/showProfile', [
    'uses' => 'User\UserController@showProfile',
    'middleware' => ['api', 'roles'],
    'role' => ['student', 'agent', 'councilor','payer']
]);

Route::put('/updateProfile', [
    'uses' => 'User\UserController@updateProfile',
    'middleware' => ['api', 'roles'],
    'role' => ['student', 'agent', 'councilor']
]);


// TODO Refactor API Routes like this

/***
 *
 * Routes for getting and updating Payer Information
 *
 */
Route::apiResource('users/payer', 'User\PayerController')->middleware('roles:student');


/***
 *
 * Routes for getting and updating Student Information
 *
 */


Route::apiResource('users/student', 'User\StudentController')->middleware('roles:councilor');

/***
 *
 * Routes for getting and updating Councilor Information
 *
 */

Route::apiResource('users/councilor', 'User\CouncilorController')->middleware('roles:agent');


// Routes for transfering students between councilors
Route::post('transfer-student', 'User\CouncilorController@transferStudents')->middleware('api', 'roles:agent');


/***
 *
 * Routes for getting and updating Merchant Information
 *
 */

Route::apiResource('merchant', 'MerchantController')->only(['index', 'show', 'update']);


/***
 *
 * Routes for getting Transactions Information
 *
 */

Route::apiResource('transaction', 'TransactionController')->middleware('roles');

Route::get('transaction/student/{id}', 'TransactionController@transactionsByStudent')->middleware('api', 'roles:agent,councilor');


/*
 * Routes for Managing Favourite Transactions
 *
 *
 *
 */
Route::apiResource('favourite/transaction','FavouriteController')->only('index','store','destroy')->middleware('roles:student');


