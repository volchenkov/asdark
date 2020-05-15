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
        $google = new GoogleApiClient();
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $promo = $request->input('promo_name');

        $targetings = [];
        foreach ($google->getCells($request->input('targetings_sid'), 'Sheet1') as $targeting) {
            $requiredFields = ['name', 'cities', 'country'];
            if ($diff = array_diff($requiredFields, array_keys($targeting))) {
                $msg = "<p>Required targeting fields missed:<b>".implode(',', $diff)."</b>.</p>";
                $msg.= "<p>Please, add columns to targetings table and try again</p>";

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
                    'ad_format'         => $request->input('ad_format'),
                    'campaign_id'       => $campaignId,
                    'ad_name'           => $name,
                    'goal_type'         => $request->input('goal_type'),
                    'cost_type'         => $request->input('cost_type'),
                    'ad_autobidding'    => (int)filter_var($request->input('autobidding'), FILTER_VALIDATE_BOOLEAN),
                    'day_limit'         => $request->input('day_limit'),
                    'ocpm'              => $request->input('ocpm'),
                    'cpm'               => '',
                    'cpc'               => '',
                    'category1_id'      => $request->input('category1_id'),
                    'targeting_cities'  => $targeting['cities'],
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
        $spreadsheet = $google->createSpreadSheet($title, $rows, $permission);

        return redirect()->action('ExportsController@confirm', ['sid' => $spreadsheet->getSpreadsheetId()]);
    }

}
