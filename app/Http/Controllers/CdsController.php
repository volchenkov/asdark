<?php

namespace App\Http\Controllers;

use App\Vk\ApiClient;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class CdsController extends BaseController
{

    public function form()
    {
        $campaigns = ApiClient::instance()->getCampaigns();
        usort($campaigns, fn($a, $b) => strcmp($a["name"], $b["name"]));

        return view('cds-ads-generation-form', ['campaigns' => $campaigns]);
    }

    public function generate(Request $request)
    {
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $promo = $request->input('promo_name');
        $targetings = [
            ['name' => 'тар1', 'cities' => [1,2], 'country' => 1],
            ['name' => 'тар2', 'cities' => [1], 'country' => 1],
            ['name' => 'тар3', 'cities' => [2], 'country' => 1],
        ];

        $ads = [];
        foreach ($targetings as $targeting) {
            foreach ($request->input('campaign_ids') as $campaignId) {
                $name = str_replace(
                    ['{promo}', '{targeting_name}'],
                    [$promo, $targeting['name']],
                    $request->input('ad_name')
                );
                $ads[] = [
                    'ad_format'         => $request->input('ad_format'),
                    'campaign_id'       => $campaignId,
                    'ad_name'           => $name,
                    'ad_autobidding'    => filter_var($request->input('autobidding'), FILTER_VALIDATE_BOOLEAN),
                    'goal_type'         => $request->input('goal_type'),
                    'cost_type'         => $request->input('cost_type'),
                    'ocpm'              => $request->input('ocpm'),
                    'cpm'               => '',
                    'cpc'               => '',
                    'category1_id'      => $request->input('category1_id'),
                    'targeting_cities'  => implode(',', $targeting['cities']),
                    'targeting_country' => $targeting['country'],
                    'post_link_button'  => $request->input('link_button'),
                    'post_link_image'   => $request->input('creative'),
                    'post_message'      => $request->input('message'),
                    'post_attachments'  => $request->input('form_uri'),
                    'post_owner_id'     => $request->input('post_owner_id')
                ];
            }
        }
        if (count($ads) == 0) {
            return response('Нет объявлений для загрузки');
        }

        $rows = [array_keys($ads[0])];
        foreach ($ads as $ad) {
            $rows[] = array_values($ad);
        }
        $spreadsheet = $this->uploadSheet("asdark - объявления для ВК {$now} {$promo}", $rows);

        return redirect()->action('CdsController@confirmExport', ['r' => $spreadsheet->getSpreadsheetUrl()]);
    }

    public function confirmExport(Request $request)
    {
        return view('cds-confirm-export', ['resource' => $request->input('r')]);
    }

    public function startExport(Request $request)
    {
        return view('cds-export-started', ['resource' => $request->input('spreadsheet')]);
    }

    private function uploadSheet(string $title, array $grid): \Google_Service_Sheets_Spreadsheet
    {
        $client = new \Google_Client();
        $client->setApplicationName('ASDARK');
        $client->setAuthConfig(getenv('GOOGLE_SERVICE_ACCOUNT_CREDENTIALS_FILE'));
        $client->addScope(\Google_Service_Sheets::SPREADSHEETS);
        $client->addScope(\Google_Service_Sheets::DRIVE);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $spreadsheet = new \Google_Service_Sheets_Spreadsheet(['properties' => ['title' => $title]]);

        $googleSheets = new \Google_Service_Sheets($client);
        $spreadsheetCreated = $googleSheets->spreadsheets->create($spreadsheet, [
            'fields' => implode(',', ['spreadsheetId', 'spreadsheetUrl'])
        ]);

        $valueRange = new \Google_Service_Sheets_ValueRange();
        $valueRange->setValues($grid);
        $valueRange->setRange('A1');
        $body = new \Google_Service_Sheets_BatchUpdateValuesRequest();
        $body->setData($valueRange);
        $body->setValueInputOption('RAW');
        $googleSheets->spreadsheets_values->batchUpdate($spreadsheetCreated->getSpreadsheetId(), $body);

        $permission = new \Google_Service_Drive_Permission();
        $permission->role = 'writer';
        $permission->type = 'anyone';

        $googleDrive = new \Google_Service_Drive($client);
        $googleDrive->permissions->create($spreadsheetCreated->getSpreadsheetId(), $permission);

        return $spreadsheetCreated;
    }

}
