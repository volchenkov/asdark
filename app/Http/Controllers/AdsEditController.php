<?php

namespace App\Http\Controllers;

use App\Vk\ApiClient as VkApiClient;
use App\Vk\AdsFeed;
use App\Google\ApiClient as GoogleApiClient;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdsEditController extends BaseController
{

    public function getCampaigns(Request $request, VkApiClient $vk)
    {
        $clientId = $request->input('client_id');
        $campaigns = $vk
            ->setClientId($clientId)
            ->getCampaigns();

        return $campaigns;
    }

    public function form(Request $request, VkApiClient $vk)
    {
        $clients = $vk->getClients();

        if (is_null($clients)) {
            $clients = [[
                'id'   => 0,
                'name' => 'Клиент по умолчанию'
            ]];
        }
        usort($clients, fn ($a, $b) => strcmp($a["name"], $b["name"]));

        return view('ads-edit-form', ['clients' => $clients]);
    }

    public function generate(Request $request, VkApiClient $vk, GoogleApiClient $google)
    {
        $campaignIds = explode(',', $request->input('campaign_ids', ''));
        $clientId = $request->input('client_id') ?: null;

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

        return $spreadsheet->getSpreadsheetId();
    }
}
