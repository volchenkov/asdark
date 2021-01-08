<?php

namespace App\Http\Controllers;

use App\Export;
use App\Vk\ApiClient as VkApiClient;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class AdminController extends BaseController
{

    public function exports()
    {
        $exports = Export::with('user')
            ->orderByDesc('id')
            ->limit(51)
            ->get();

        return view('admin/exports', ['exports' => $exports]);
    }

    public function vkExecuteForm()
    {
        return view('admin/vk-execution-form', ['response' => null, 'code' => null]);
    }

    public function vkExecuteRequest(Request $request, VkApiClient $vk)
    {
        $code = $request->input('code');
        try {
            $rsp = $vk->execute($code, null, null);
        } catch (\Exception $e) {
            $rsp = [
                'error'   => $e->getCode(),
                'message' => $e->getMessage()
            ];
        }

        return view('admin/vk-execution-form', ['response' => $rsp, 'code' => $code]);
    }
}
