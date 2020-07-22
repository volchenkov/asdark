<?php

namespace App\Http\Controllers;

use App\Connection;
use App\Vk\ApiClient as VkApiClient;
use App\Vk\AdsFeed;
use App\Google\ApiClient as GoogleApiClient;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdsEditController extends BaseController
{

    public function index()
    {
        $vkConnection = Connection::where('system', 'vk')->firstOrFail();
        if ($vkConnection->isAgency()) {
            return redirect()->action('AdsEditController@chooseClient');
        }

        return redirect()->action('AdsEditController@form');
    }

    public function chooseClient(Request $request)
    {
        $vk = new VkApiClient();
        $clients = $vk->getClients();
        if (!is_array($clients)) {
            return 'Выбор клиентов доступен только для агентского кабинета ВК';
        }
        usort($clients, fn ($a, $b) => strcmp($a["name"], $b["name"]));

        return view('ads-edit-choose-client', ['clients' => $clients]);
    }

    public function form(Request $request)
    {
        $clientId = $request->input('client_id');
        $vk = new VkApiClient();
        $campaigns = $vk
            ->setClientId($clientId)
            ->getCampaigns();

        usort($campaigns, fn ($a, $b) => strcmp($a["name"], $b["name"]));

        return view('ads-edit-generation-form', [
            'clientId'  => $clientId,
            'campaigns' => $campaigns
        ]);
    }

    public function generate(Request $request)
    {
        $campaignIds = $request->input('campaign_ids');
        $clientId = $request->input('client_id');

        $vk = new VkApiClient();
        $vk->setClientId($clientId);

        $ads = $vk->getAds($campaignIds);

        $fields = [
            AdsFeed::COL_CAMPAIGN_ID,
            AdsFeed::COL_CAMPAIGN_NAME,
            AdsFeed::COL_AD_ID
        ];
        if ($clientId) {
            array_unshift($fields, AdsFeed::COL_CLIENT_ID);
        }

        $fields = array_merge($fields, array_keys(AdsFeed::getEditableFields()));
        $feed = $vk->getFeed(array_column($ads, 'id'), $fields);

        usort($feed, fn ($a, $b) => $a[AdsFeed::COL_CAMPAIGN_ID] <=> $b[AdsFeed::COL_CAMPAIGN_ID]);

        $headers = array_keys(array_values($feed)[0]);
        $rows = array_map('array_values', $feed);
        array_unshift($rows, $headers);

        $google = new GoogleApiClient();
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $spreadsheet = $google->createSpreadSheet(
            "asdark - редактирование объявлений ВК {$now}",
            $rows,
            new \Google_Service_Drive_Permission([
                'role'         => 'writer',
                'type'         => 'user',
                'emailAddress' => Auth::user()->email
            ])
        );

        return redirect()->action('ExportsController@confirm', ['sid' => $spreadsheet->getSpreadsheetId()]);
    }
}
