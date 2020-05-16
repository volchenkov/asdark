<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/vk_auth', 'VkOauthController@form');
Route::get('/vk_auth_callback', 'VkOauthController@callback');

Route::get('/cds_form', 'CdsController@form');
Route::get('/cds_generate', 'CdsController@generate');

Route::get('/exports_confirm', 'ExportsController@confirm');
Route::post('/exports_start', 'ExportsController@start');
Route::get('/exports_started', 'ExportsController@started');
Route::get('/exports', 'ExportsController@list');


Route::get('/ads_edit_form', 'AdsEditController@form');
Route::get('/ads_edit_generate', 'AdsEditController@generate');
