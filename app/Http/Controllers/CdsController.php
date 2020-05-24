<?php

namespace App\Http\Controllers;

use App\Vk\AdsFeed;
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
        $google = new GoogleApiClient();
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $promo = $request->input('promo_name');

        $targetings = [];
        $targetingsFeed = $google->getCells($request->input('targetings_sid'), 'Sheet1');
        foreach ($targetingsFeed as $targeting) {
            $requiredFields = ['name', 'cities', 'country'];
            if ($diff = array_diff($requiredFields, array_keys($targeting))) {
                $msg = "<p>Required targeting fields missed:<b>" . implode(',', $diff) . "</b>.</p>";
                $msg .= "<p>Please, add columns to targetings table and try again</p>";

                return response($msg);
            }

            $targetings[] = $targeting;
        }

        $ads = [];
        foreach ($request->input('campaign_ids') as $campaignId) {
            foreach ($targetings as $targeting) {
                $name = str_replace(
                    ['{promo}', '{targeting_name}'],
                    [$promo, $targeting['name']],
                    $request->input('ad_name')
                );
                $ads[] = [
                    AdsFeed::COL_AD_FORMAT                               => $request->input('ad_format'),
                    AdsFeed::COL_CAMPAIGN_ID                             => $campaignId,
                    AdsFeed::COL_AD_NAME                                 => $name,
                    AdsFeed::COL_GOAL_TYPE                               => $request->input('goal_type'),
                    AdsFeed::COL_COST_TYPE                               => $request->input('cost_type'),
                    AdsFeed::COL_AUTOBIDDING                             => (int)filter_var($request->input('autobidding'), FILTER_VALIDATE_BOOLEAN),
                    AdsFeed::COL_AD_DAY_LIMIT                            => $request->input('day_limit'),
                    AdsFeed::COL_AD_OCPM                                 => $request->input('ocpm'),
                    AdsFeed::COL_AD_CATEGORY1                            => $request->input('category1_id'),
                    AdsFeed::COL_AD_TARGETING_CITIES                     => $targeting['cities'],
                    AdsFeed::COL_AD_TARGETING_COUNTRY                    => $targeting['country'],
                    AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE => $request->input('link_button'),
                    AdsFeed::COL_POST_LINK_IMAGE                         => $request->input('creative'),
                    AdsFeed::COL_POST_TEXT                               => $request->input('message'),
                    AdsFeed::COL_POST_ATTACHMENT_LINK_URL                => $request->input('form_uri'),
                    AdsFeed::COL_POST_OWNER_ID                           => $request->input('post_owner_id')
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

        $spreadsheet = $google->createSpreadSheet(
            "asdark - объявления для ВК {$now} {$promo}",
            $rows,
            new \Google_Service_Drive_Permission(['role' => 'writer', 'type' => 'anyone'])
        );

        return redirect()->action('ExportsController@confirm', ['sid' => $spreadsheet->getSpreadsheetId()]);
    }

}
