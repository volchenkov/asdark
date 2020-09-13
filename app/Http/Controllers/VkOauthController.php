<?php

namespace App\Http\Controllers;

use App\Connection;
use App\Vk\ApiClient;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use \GuzzleHttp\Client;

class VkOauthController extends BaseController
{

    public function currentState(Request $request)
    {
        return view('vk-auth-current-state', [
            'connection' => Connection::where('system', 'vk')->first(),
            'notify'     => boolval($request->input('notify'))
        ]);
    }

    public function form()
    {
        $query = [
            'client_id'     => env('VK_CLIENT_ID'),
            'redirect_uri'  => env('APP_URL') . '/vk_auth_callback',
            'display'       => 'page',
            'scope'         => 32768 + 65536, // ads + offline
            'response_type' => 'code',
            'state'         => env('VK_AUTH_STATE'),
            'revoke'        => 1
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

        $data = json_decode($rsp->getBody()->getContents(), true);
        if ($rsp->getStatusCode() >= 400 || !$data) {
            return view('vk-auth-token-failed', ['message' => $rsp->getBody()->getContents()]);
        }

        $conn = Connection::firstOrNew(['system' => 'vk']);
        $conn->data = [
            'access_token' => $data['access_token'],
            'expires_in'   => $data['expires_in'],
            'user_id'      => $data['user_id']
        ];
        $conn->save();

        try {
            $vk = new ApiClient();
            $accounts = $vk->getAccounts();
        } catch (\Exception $e) {
            return view('vk-auth-token-failed', ['message' => $e->getMessage()]);
        }

        $activeAccounts = array_filter($accounts, fn($a) => $a['account_status'] == 1);

        return view('vk-auth-choose-account', ['accounts' => array_values($activeAccounts)]);
    }

    public function save(Request $request)
    {
        $accounts = json_decode($request->input('accounts', '[]'), true);
        $accounts = array_combine(array_column($accounts, 'account_id'), $accounts);

        $account = $accounts[$request->input('account_id')] ?? [];

        $conn = Connection::where('system', 'vk')->firstOrFail();
        $conn->data = array_replace($conn->data, $account);
        $conn->save();

        return redirect()->action("VkOauthController@currentState");
    }

}
