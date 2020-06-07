<?php

namespace App\Http\Controllers;

use App\Connection;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use \GuzzleHttp\Client;

class VkOauthController extends BaseController
{

    public function form()
    {
        $query = [
            'client_id'     => env('VK_CLIENT_ID'),
            'redirect_uri'  => env('APP_URL') . '/vk_auth_callback',
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
            'redirect_uri'  => env('APP_URL') . '/vk_auth_callback',
            'code'          => $request->query('code')
        ];
        $rsp = $http->get('https://oauth.vk.com/access_token', ['query' => $query]);

        $ok = $rsp->getStatusCode() < 400;
        $data = $ok ? json_decode($rsp->getBody()->getContents(), true) : [];

        return view('vk-auth-result', [
            'ok'          => $ok,
            'vkResponse'  => $rsp->getBody()->getContents(),
            'accessToken' => $data['access_token'] ?? null,
            'expiresIn'   => $data['expires_in'] ?? null,
            'userId'      => $data['user_id'] ?? null,
        ]);
    }

    public function save(Request $request)
    {
        $conn = Connection::firstOrNew(['system' => 'vk']);
        $conn->data = [
            'access_token' => $request->input('access_token'),
            'expires_in'   => $request->input('expires_in'),
            'user_id'      => $request->input('vk_user_id'),
            'account'      => $request->input('account_id')
        ];
        $conn->save();

        return view('vk-auth-success');
    }

}
