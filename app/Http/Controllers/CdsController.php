<?php

namespace App\Http\Controllers;

use App\Vk\ApiClient as VkApiClient;
use App\Google\ApiClient as GoogleApiClient;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class CdsController extends BaseController
{

    public function form()
    {
        $campaigns = VkApiClient::instance()->getCampaigns();
        usort($campaigns, fn ($a, $b) => strcmp($a["name"], $b["name"]));

        return view('cds-ads-generation-form', ['campaigns' => $campaigns]);
    }

    public function generate(Request $request)
    {
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $promo = $request->input('promo_name');
        $targetings = [
            ['name' => 'тар1', 'cities' => [1, 2], 'country' => 1],
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
                    'ad_autobidding'    => (int)filter_var($request->input('autobidding'), FILTER_VALIDATE_BOOLEAN),
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

        $title = "asdark - объявления для ВК {$now} {$promo}";
        $permission = new \Google_Service_Drive_Permission(['role' => 'writer', 'type' => 'anyone']);
        $spreadsheet = (new GoogleApiClient())->createSpreadSheet($title, $rows, $permission);

        return redirect()->action('CdsController@confirmExport', ['sid' => $spreadsheet->getSpreadsheetId()]);
    }

    public function confirmExport(Request $request)
    {
        return view('cds-confirm-export', ['spreadsheetId' => $request->input('sid')]);
    }

    public function startExport(Request $request)
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

        return redirect()->action('CdsController@exportStarted', ['spreadsheetId' => $operation['spreadsheetId']]);
    }

    public function exportStarted(Request $request)
    {
        return view('cds-export-started', ['spreadsheetId' => $request->input('spreadsheetId')]);
    }

}
