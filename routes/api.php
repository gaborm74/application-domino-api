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


Route::post('/register', 'UserController@register');
Route::post('/login', 'UserController@login');

Route::middleware('auth:api')->group(function () {
	Route::get('/user', function (Request $request) {
		return $request->user();
	});
	Route::post('/logout', 'UserController@logout');

	Route::post('/game/setup', 'DominoGameController@setup');
	Route::post('/game/start', 'DominoGameController@start');
	
	Route::get('/game/status', 'DominoGameController@status');
	Route::get('/game/result', 'DominoGameController@result');
	Route::get('/game/list', 'DominoGameController@list');

});
