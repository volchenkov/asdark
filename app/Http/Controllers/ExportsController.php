<?php

namespace App\Http\Controllers;

use App\Export;
use App\ExportLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class ExportsController extends BaseController
{

    public function list()
    {
        return view('exports-list', ['exports' => Export::with('user')->get()->sortByDEsc('id')]);
    }

    public function captcha(Request $request)
    {
        return view('exports-captcha', ['export' => Export::findOrFail($request->input('export_id'))]);
    }

    public function confirm(Request $request)
    {
        return view('exports-confirm', ['spreadsheetId' => $request->input('sid')]);
    }

    public function operations(Request $request)
    {
        return view('exports-operations', ['export' => Export::findOrFail($request->input('export_id'))]);
    }

    public function item(Request $request)
    {
        $export = Export::with('user')->findOrFail($request->input('export_id'));
        $data = [
            'export' => $export,
            'logs'   => ExportLog::where('export_id', $export->id)->get()
        ];

        $headers = [];
        if (in_array($export->status, [Export::STATUS_PENDING, Export::STATUS_PROCESSING])) {
            $headers = ['Refresh' => 10];
        }

        return response()->view('export', $data, 200, $headers);
    }

    public function cancel(Request $request)
    {
        $export = Export::find($request->input('id'));
        $export->status = Export::STATUS_CANCELED;
        $export->saveOrFail();

        return redirect()->action('ExportsController@list');
    }

    public function start(Request $request)
    {
        $export = new Export();
        $export->sid = $request->input('spreadsheetId');
        $export->status = Export::STATUS_PENDING;
        $export->user_id = Auth::user()->id;

        if ($captcha = $request->input('captcha')) {
            $export->captcha = $captcha;
        }
        if ($captchaCode = $request->input('captcha_code')) {
            $export->captcha_code = $captchaCode;
        }
        $export->saveOrFail();

        $request->session()->flash('msg', "Загрузка запланирована, номер #{$export->id}");

        return redirect()->action('ExportsController@item', ['export_id' => $export->id]);
    }

}
