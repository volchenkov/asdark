<?php

namespace App\Http\Controllers;

use App\Vk\ApiClient as VkApiClient;
use App\Google\ApiClient as GoogleApiClient;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class AdsEditController extends BaseController
{

    public function form()
    {
        $campaigns = VkApiClient::instance()->getCampaigns();
        usort($campaigns, fn ($a, $b) => strcmp($a["name"], $b["name"]));

        $editableFields = [
            'post_message'    => 'Текст рекламного поста',
            'post_link_image' => 'Картинка рекламного поста',
        ];

        return view('ads-edit-generation-form', [
            'campaigns'      => $campaigns,
            'editableFields' => $editableFields
        ]);
    }

    public function generate(Request $request)
    {
        $campaignIds = $request->input('campaign_ids');
        $adFields = $request->input('ad_fields');

        $vk = VkApiClient::instance();

        $ads = [];
        foreach ($vk->getAds($campaignIds) as $ad) {
            $ads[$ad['id']] = $ad;
        }

        if (!$ads) {
            return response('No ads found');
        }

        foreach ($vk->getAdsLayout(array_keys($ads)) as $layout) {
            $ads[$layout['id']]['layout'] = $layout;
        }

        if ($needPosts = true) {
            $re = '/^http(s)?:\/\/vk.com\/wall/';

            $adPostIds = [];
            foreach ($ads as $ad) {
                $adPostIds[$ad['id']] = preg_replace($re, '', $ad['layout']['link_url'] ?? '');
            }

            $postIds = array_filter(array_unique(array_values($adPostIds)));
            if ($postIds) {
                $posts = $vk->getWallPosts($postIds);

                $postIdAds = array_flip($adPostIds);
                foreach ($posts as $post) {
                    $postId = "{$post['from_id']}_{$post['id']}";
                    $adId = $postIdAds[$postId];
                    $ads[$adId]['post'] = $post;
                }
            }
        }

        $getValue = function (array $ad, string $field) {
            switch ($field) {
                case 'post_message':
                    return $ad['post']['text'] ?? null;
                case 'post_link_image':
                    return $ad['post']['attachments'][0]['link']['photo']['sizes'][0]['url'] ?? null;
                default:
                    return null;
            }
        };

        $editionFeed = [];
        foreach ($ads as $ad) {
            $row = [
                'campaign_id' => $ad['campaign_id'],
                'ad_id'       => $ad['id'],
            ];
            foreach ($adFields as $field) {
                $row[$field] = $getValue($ad, $field);
            }
            $editionFeed[] = $row;
        }

        usort($ads, fn($a, $b) => $a["campaign_id"] <=> $b["campaign_id"]);

        $headers = array_keys($editionFeed[0]);
        $rows = array_map('array_values', $editionFeed);
        array_unshift($rows, $headers);

        $google = new GoogleApiClient();
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $title = "asdark - редактирование объявлений ВК {$now}";
        $permission = new \Google_Service_Drive_Permission(['role' => 'writer', 'type' => 'anyone']);
        $spreadsheet = $google->createSpreadSheet($title, $rows, $permission);

        return redirect()->action('ExportsController@confirm', ['sid' => $spreadsheet->getSpreadsheetId()]);
    }
}
