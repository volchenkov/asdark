<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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

Route::get('/vk_auth', function () {
    $query = [
        'client_id'     => env('VK_CLIENT_ID'),
        'redirect_uri'  => env('VK_AUTH_CALLBACK_URL'),
        'display'       => 'page',
        'scope'         => '65536',
        'response_type' => 'code',
        'state'         => env('VK_AUTH_STATE'),
    ];

    return redirect('https://oauth.vk.com/authorize?' . http_build_query($query));
});

Route::get('/vk_auth_callback', function (Request $request) {
    if ($error = $request->query('error')) {
        return response("{$error}: {$request->query('error_description')}", 400);
    }
    $code = $request->query('code');

    $query = [
        'client_id'     => env('VK_CLIENT_ID'),
        'client_secret' => env('VK_CLIENT_SECRET'),
        'redirect_uri'  => env('VK_AUTH_CALLBACK_URL'),
        'code'          => $code
    ];
    $r = (new GuzzleHttp\Client())->get('https://oauth.vk.com/access_token', ['query' => $query]);

    $writen = file_put_contents('vk_credentials.json', (string) $r->getBody());
    if ($writen = false || $status = $r->getStatusCode() >= 400) {
        return response("NOTOK: {$r->getStatusCode()}", 500);
    }

    return response('OK', 200);
});

Route::get('/vk_credentials', function(Request $request) {
    $secret = $request->query('secret');

    if (!$secret || $secret !== env('VK_CREDENTIALS_SECRET')) {
        abort(403, 'Access denied');
    }

    return file_get_contents('vk_credentials.json');
});
