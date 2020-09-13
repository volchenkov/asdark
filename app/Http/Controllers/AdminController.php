<?php

namespace App\Http\Controllers;

use App\Export;
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

}
