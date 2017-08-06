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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'auth.basic'], function () {
    Route::get('/files', 'FilesController@index');
    Route::get('/files/{id}', 'FilesController@show');
    Route::post('/files', 'FilesController@store');
    Route::put('/files/{id}', 'FilesController@update');
    Route::delete('/files/{id}', 'FilesController@destroy');
    Route::delete('/files', 'FilesController@recycle');
});
