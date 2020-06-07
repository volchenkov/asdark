<?php

namespace App\Http\Controllers;

use App\Google\ApiClient as GoogleApiClient;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class ExportsController extends BaseController
{

    public function list()
    {
        return view('exports-list', ['exports' => array_reverse((new GoogleApiClient())->getOperations())]);
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

    public function start(Request $request)
    {
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $operation = [
            'spreadsheetId' => $request->input('spreadsheetId'),
            'created_at'    => $now,
            'updated_at'    => $now,
            'status'        => 'pending',
        ];
        $google = new GoogleApiClient();
        $google->appendRow(getenv('OPERATIONS_SPREADSHEET_ID'), $operation);

        return redirect()->action('ExportsController@started');
    }

    public function started()
    {
        return view('exports-started');
    }

}
