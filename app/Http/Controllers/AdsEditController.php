<?php

namespace App\Http\Controllers;

use App\Vk\ApiClient as VkApiClient;
use App\Vk\AdsFeed;
use App\Google\ApiClient as GoogleApiClient;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class AdsEditController extends BaseController
{

    public function chooseClient(Request $request)
    {
        $vk = new VkApiClient();
        $clients = $vk->getClients();

        if (is_array($clients)) {
            usort($clients, fn ($a, $b) => strcmp($a["name"], $b["name"]));
            return view('ads-edit-choose-client', ['clients' => $clients]);
        } else {
            return redirect()->action('AdsEditController@form');
        }
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
        $needPosts = filter_var($request->input('need_posts'), FILTER_VALIDATE_BOOLEAN);

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

        $getPostFields = function($entity) {
            return array_keys(array_filter(AdsFeed::getEntityFields($entity), fn($f) => $f['editable']));
        };

        $fields = array_merge($fields, $getPostFields('ad'));
        if ($needPosts) {
            $fields = array_merge($fields, $getPostFields('post'));
        }

        $feed = $vk->getFeed(array_column($ads, 'id'), $fields);

        usort($feed, fn($a, $b) => $a[AdsFeed::COL_CAMPAIGN_ID] <=> $b[AdsFeed::COL_CAMPAIGN_ID]);

        $headers = array_keys(array_values($feed)[0]);
        $rows = array_map('array_values', $feed);
        array_unshift($rows, $headers);

        $google = new GoogleApiClient();
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $spreadsheet = $google->createSpreadSheet(
            "asdark - редактирование объявлений ВК {$now}",
            $rows,
            new \Google_Service_Drive_Permission(['role' => 'writer', 'type' => 'anyone'])
        );

        return redirect()->action('ExportsController@confirm', ['sid' => $spreadsheet->getSpreadsheetId()]);
    }
}
