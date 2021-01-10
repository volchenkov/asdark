<?php

use Illuminate\Support\Facades\Auth;
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

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/google_redirect', 'GoogleOauthController@redirectToProvider');
Route::get('/google_callback', 'GoogleOauthController@handleProviderCallback');

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/vk_auth', 'VkOauthController@form');
    Route::get('/vk_auth_callback', 'VkOauthController@callback');
    Route::post('/vk_auth_save', 'VkOauthController@save');
    Route::get('/vk_auth_current_state', 'VkOauthController@currentState')->name('vkAuth.state');

    Route::middleware([\App\Http\Middleware\CheckVkConnection::class])->group(function() {
        Route::get('/ads_edit_form', 'AdsEditController@form')->name('adsEdit.start');
        Route::get('/ads_edit_generate', 'AdsEditController@generate');
        Route::get('/ads_edit_get_campaigns', 'AdsEditController@getCampaigns');
    });
    Route::get('/exports_confirm', 'ExportsController@confirm');
    Route::post('/exports_start', 'ExportsController@start')->name('export.start');
    Route::get('/exports_cancel', 'ExportsController@cancel')->name('export.cancel');
    Route::match(['post', 'get'], '/exports_rerun', 'ExportsController@rerun')->name('export.rerun');
    Route::get('/exports_captcha', 'ExportsController@captcha')->name('export.captcha');
    Route::get('/exports', 'ExportsController@list');
    Route::get('/export', 'ExportsController@item')->name('export.logs');
    Route::get('/exports_operations', 'ExportsController@operations')->name('export.operations');
    Route::get('/help', 'HelpController@index');
    Route::get('admin/exports', 'AdminController@exports');

    Route::get('/logout', function () {
        Auth::logout();
        return redirect('/login');
    });

});

