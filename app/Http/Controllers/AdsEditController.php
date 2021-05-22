<?php

namespace App\Http\Controllers;

use App\Vk\AdsDownloader;
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
            ->get('ads.getCampaigns');

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

    public function generate(Request $request, GoogleApiClient $google, AdsDownloader $adsDownloader)
    {
        $campaignIds = explode(',', $request->input('campaign_ids', ''));
        $clientId = $request->input('client_id') ?: null;

        $fields = [
            AdsFeed::COL_CAMPAIGN_ID,
            AdsFeed::COL_CAMPAIGN_NAME,
            AdsFeed::COL_AD_ID
        ];
        $fields = array_merge($fields, array_keys(AdsFeed::getEditableFields()));
        if ($clientId) {
            array_unshift($fields, AdsFeed::COL_CLIENT_ID);
        }

        $feed = $adsDownloader->getFeed($clientId, $fields, ['campaign_ids' => $campaignIds]);
        usort($feed, fn ($a, $b) => $a[AdsFeed::COL_CAMPAIGN_ID] <=> $b[AdsFeed::COL_CAMPAIGN_ID]);

        $headers = array_keys(array_values($feed)[0]);
        $rows = array_map('array_values', $feed);
        array_unshift($rows, $headers);

        $sheetTitle = "asdark - редактирование объявлений ВК ".(new \DateTime())->format('Y-m-d H:i:s');
        $sheetPermission = new \Google_Service_Drive_Permission([
            'role'         => 'writer',
            'type'         => 'user',
            'emailAddress' => Auth::user()->email
        ]);
        $spreadsheet = $google->createSpreadSheet($sheetTitle, $rows, $sheetPermission);

        return $spreadsheet->getSpreadsheetId();
    }
}
