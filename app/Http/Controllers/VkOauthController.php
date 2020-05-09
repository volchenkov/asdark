<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use \GuzzleHttp\Client;

class VkOauthController extends BaseController
{
    public function form()
    {
        $query = [
            'client_id'     => env('VK_CLIENT_ID'),
            'redirect_uri'  => env('APP_URL').'/vk_auth_callback',
            'display'       => 'page',
            'scope'         => '65536',
            'response_type' => 'code',
            'state'         => env('VK_AUTH_STATE'),
        ];

        return redirect('https://oauth.vk.com/authorize?' . http_build_query($query));
    }

    public function callback(Request $request)
    {
        if ($error = $request->query('error')) {
            return response("{$error}: {$request->query('error_description')}", 400);
        }
        $http = new Client();
        $query = [
            'client_id'     => env('VK_CLIENT_ID'),
            'client_secret' => env('VK_CLIENT_SECRET'),
            'redirect_uri'  => env('APP_URL').'/vk_auth_callback',
            'code'          => $request->query('code')
        ];
        $rsp = $http->get('https://oauth.vk.com/access_token', ['query' => $query]);

        $ok = file_put_contents('vk_credentials.json', (string)$rsp->getBody());
        if ($ok == false || $status = $rsp->getStatusCode() >= 400) {
            return response("NOTOK: {$rsp->getStatusCode()} {$rsp->getBody()->getContents()}", 500);
        }

        return response('OK', 200);
    }

}
