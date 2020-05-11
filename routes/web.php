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
Route::get('/cds_confirm_export', 'CdsController@confirmExport');
Route::post('/cds_start_export', 'CdsController@startExport');
Route::get('/cds_export_started', 'CdsController@exportStarted');

