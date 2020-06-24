<?php

namespace App\Http\Controllers;

use App\Vk\AdsFeed;
use Illuminate\Routing\Controller as BaseController;

class HelpController extends BaseController
{

    public function index()
    {
        return view('help', ['fields' => array_filter(AdsFeed::FIELDS, fn($f) => $f['editable'])]);
    }

}
