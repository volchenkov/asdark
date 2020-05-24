<?php

namespace App\Http\Controllers;

use App\Vk\ApiClient as VkApiClient;
use App\Vk\AdsFeed;
use App\Google\ApiClient as GoogleApiClient;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class AdsEditController extends BaseController
{

    public function form()
    {
        $campaigns = VkApiClient::instance()->getCampaigns();
        usort($campaigns, fn ($a, $b) => strcmp($a["name"], $b["name"]));

        return view('ads-edit-generation-form', [
            'campaigns'      => $campaigns,
            'fields'         => array_filter(AdsFeed::FIELDS, fn($field) => $field['editable'])
        ]);
    }

    public function generate(Request $request)
    {
        $campaignIds = $request->input('campaign_ids');
        $adFields = $request->input('ad_fields');

        $vk = VkApiClient::instance();

        $ads = $vk->getAds($campaignIds);

        $defaultCols = [
            AdsFeed::COL_CAMPAIGN_ID,
            AdsFeed::COL_CAMPAIGN_NAME,
            AdsFeed::COL_AD_ID,
            AdsFeed::COL_AD_NAME,
        ];
        $feed = $vk->getFeed(array_column($ads, 'id'), array_unique(array_merge($defaultCols, $adFields)));
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
