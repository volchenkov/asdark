<?php

namespace App\Http\Controllers;

use App\Google\ApiClient as GoogleApiClient;
use Illuminate\Routing\Controller as BaseController;

class ExportsController extends BaseController
{

    public function list()
    {
        return view('exports-list', ['exports' => array_reverse((new GoogleApiClient())->getOperations())]);
    }

}
