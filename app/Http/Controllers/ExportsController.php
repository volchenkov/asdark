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

    public function start(Request $request)
    {
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $operation = [
            'spreadsheetId' => $request->input('spreadsheetId'),
            'created_at'    => $now,
            'updated_at'    => $now,
            'status'        => 'new',
        ];
        $google = new GoogleApiClient();
        $google->appendRow(getenv('OPERATIONS_SPREADSHEET_ID'), $operation);

        return redirect()->action('ExportsController@started', ['spreadsheetId' => $operation['spreadsheetId']]);
    }

    public function started(Request $request)
    {
        return view('exports-started', ['spreadsheetId' => $request->input('spreadsheetId')]);
    }

}
