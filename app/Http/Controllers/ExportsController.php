<?php

namespace App\Http\Controllers;

use App\Export;
use App\Google\ApiClient as GoogleApiClient;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class ExportsController extends BaseController
{

    public function list()
    {
        return view('exports-list', ['exports' => Export::all()->sortByDEsc('id')]);
    }

    public function confirm(Request $request)
    {
        return view('exports-confirm', ['spreadsheetId' => $request->input('sid')]);
    }

    public function logs(Request $request)
    {
        $log = storage_path("logs/sheet-{$request->input('sid')}.log");
        $formatted = str_replace(['[]', "\n"], ['', '<br/>'], file_get_contents($log));

        return response($formatted, 200, ['Refresh' => '2']);
    }

    public function cancel(Request $request)
    {
        $export = Export::find($request->input('id'));
        $export->status = 'canceled';
        $export->saveOrFail();

        return redirect()->action('ExportsController@list');
    }

    public function start(Request $request)
    {
        $export = new Export();
        $export->sid = $request->input('spreadsheetId');
        $export->status = 'pending';

        $export->saveOrFail();

        return redirect()->action('ExportsController@started');
    }

    public function started()
    {
        return view('exports-started');
    }

}
