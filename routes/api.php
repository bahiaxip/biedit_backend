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

Route::group(["middleware"=>"auth:api"],function(){

	//El sistema de autenticación de Laravel no permite utilizar el método updateUser() dentro de Auth/LoginController o Auth/RegisterController, no permite autenticar el token aun siendo enviado,por tanto se ha incluido en HomeController
	Route::post("update","HomeController@updateUser");

	
	Route::post("images","ImageController@store");
	Route::post("images/{image}","ImageController@destroy");
	Route::post("resize","ImageController@resizeImage");
	Route::post("crop","ImageController@cropImage");
});
Route::get("images/","ImageController@index");
Route::post("filter","ImageController@setFilter");
Route::post("polygon","ImageController@setPolygon");
Route::post("effect","ImageController@setEffect");
Route::post("composite","ImageController@compositeImage");
Route::post("watermark","ImageController@setWaterMark");
Route::post("watermark/create","ImageController@createWaterMark");
Route::post("compress","ImageController@setCompression");

Route::get("download","ImageController@download");
Route::get("get-image/{image}","ImageController@getImage");

Route::post("register",'Auth\RegisterController@register');
Route::post("login","Auth\LoginController@login");
Route::post("logout","Auth\LoginController@logout");
