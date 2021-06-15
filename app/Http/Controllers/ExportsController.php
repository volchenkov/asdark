<?php

namespace App\Http\Controllers;

use App\Export;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class ExportsController extends BaseController
{

    public function list()
    {
        $exports = Export::with('user')
            ->where('user_id', Auth::user()->id)
            ->orderByDesc('id')
            ->limit(51)
            ->get();

        return view('exports', ['exports' => $exports]);
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
        return view('export-operations', ['export' => Export::findOrFail($request->input('export_id'))]);
    }

    public function item(Request $request)
    {
        $export = Export::with('user')->findOrFail($request->input('export_id'));

        $headers = [];
        if (in_array($export->status, [Export::STATUS_PENDING, Export::STATUS_PROCESSING])) {
            $headers = ['Refresh' => 10];
        }

        return response()->view('export-logs', ['export' => $export], 200, $headers);
    }

    public function cancel(Request $request)
    {
        $export = Export::findOrFail($request->input('id'));
        $export->status = Export::STATUS_CANCELED;
        $export->saveOrFail();

        $request->session()->flash('msg', "Загрузка отменена");

        return redirect()->action('ExportsController@list');
    }

    public function rerun(Request $request)
    {
        $export = Export::findOrFail($request->input('id'));
        $export->status = Export::STATUS_PENDING;
        $export->captcha = $request->input('captcha', null);
        $export->captcha_code = $request->input('captcha_code', null);
        $export->failure = null;

        $export->saveOrFail();

        $request->session()->flash('msg', "Загрузка обновлена");

        return redirect()->action('ExportsController@item', ['export_id' => $export->id]);
    }

    public function start(Request $request)
    {
        $export = new Export();
        $export->sid = $request->input('spreadsheetId');
        $export->status = Export::STATUS_PENDING;
        $export->user_id = Auth::user()->id;

        if ($clientId = $request->input('clientId')) {
            $export->client_id = $clientId;
            $export->client_name = $request->input('clientName');
        }

        $export->saveOrFail();

        $request->session()->flash('msg', "Загрузка запланирована, номер #{$export->id}");

        return redirect()->action('ExportsController@item', ['export_id' => $export->id]);
    }

}
