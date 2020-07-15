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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1'], function() {
	Route::resource('question', 'QuestionController', [
		'except' => ['edit', 'create']
	]);

	Route::get('delete_unanswered', [
        'uses' => 'QuestionController@delete_unanswered'
    ]);

	Route::resource('question/answer', 'AnswerController', [
		'only' => ['store', 'update', 'destroy']
	]);

	Route::post('user', [
        'uses' => 'AuthController@store'
    ]);

    Route::post('user/signin', [
        'uses' => 'AuthController@signin'
    ]);

    Route::post('user/reset_password', [
        'uses' => 'AuthController@reset_password'
    ]);
});