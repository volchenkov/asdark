<?php

namespace App\Vk;

/**
 *
 */
class AdsDownloader
{

    private ApiClient $vk;

    public function __construct(ApiClient $vkApiClient)
    {
        $this->vk = $vkApiClient;
    }

    public function getFeed(?int $clientId, array $fields, array $filter): array
    {
        $this->vk->setClientId($clientId);

        $filterKey = array_keys($filter)[0]; // campaign_ids | ad_ids
        $filterIds = array_unique($filter[$filterKey]);

        $ads = [];

        // предупредить 414 ответ из-за превышения допустимого размера запроса. Эмпирически ~200 id проходит
        foreach (array_chunk($filterIds, 200) as $idsChunk) {
            $adsChunk = $this->vk->get('ads.getAds', [$filterKey => json_encode($idsChunk)]);
            foreach ($adsChunk as $ad) {
                $ads[$ad['id']] = $ad;
            }
        }

        // предупредить 414 ответ из-за превышения допустимого размера запроса. Эмпирически ~200 id проходит
        foreach (array_chunk(array_keys($ads), 200) as $adIdsChunk) {
            $layouts = $this->vk->get('ads.getAdsLayout', ['ad_ids' => json_encode($adIdsChunk)]);
            foreach ($layouts as $layout) {
                $ads[$layout['id']]['layout'] = $layout;
            }
        }

        if (AdsFeed::dependsOn('campaign', $fields)) {
            $campaigns = [];
            foreach ($this->vk->get('ads.getCampaigns') as $campaign) {
                $campaigns[$campaign['id']] = $campaign;
            }

            foreach ($ads as $adId => $ad) {
                $ads[$adId]['campaign'] = $campaigns[$ad['campaign_id']]; // @todo: null когда удалена?
            }
        }

        if (AdsFeed::dependsOn('post', $fields)) {
            $adPostIds = [];
            foreach ($ads as $ad) {
                $link = $ad['layout']['link_url'] ?? '';
                $re = '/^http(s)?:\/\/vk.com\/wall/';
                if (preg_match($re, $link)) {
                    $adPostIds[$ad['id']] = preg_replace($re, '', $link);
                }
            }

            $postIds = array_filter(array_unique(array_values($adPostIds)));

            $postIdAds = array_flip($adPostIds);
            // ограничение API - по 100 за раз
            // см. https://vk.com/dev/wall.getById
            foreach (array_chunk($postIds, 100) as $postIdsChunk) {
                $posts = $this->vk->get('wall.getById', ['posts' => implode(',', $postIdsChunk)]);

                foreach ($posts as $post) {
                    $postId = "{$post['from_id']}_{$post['id']}";
                    $adId = $postIdAds[$postId];

                    $ads[$adId]['post'] = $post;
                }
            }
        }
        $rows = [];
        foreach ($ads as $ad) {
            $row = [];
            foreach ($fields as $field) {
                $row[$field] = $this->fetchField($ad, $field);
            }
            $rows[$ad['id']] = $row;
        }

        return $rows;
    }

    private function fetchField(array $ad, string $field)
    {
        switch ($field) {
            case AdsFeed::COL_AD_ID:
                return $ad['id'];
            case AdsFeed::COL_AD_NAME:
                return $ad['name'];
            case AdsFeed::COL_AD_FORMAT:
                return $ad['ad_format'];
            case AdsFeed::COL_AD_TITLE:
                return $ad['layout']['title'] ?? null;
            case AdsFeed::COL_AD_LINK_URL:
                return $ad['layout']['link_url'] ?? null;
            case AdsFeed::COL_AD_DESCRIPTION:
                return $ad['layout']['description'] ?? null;
            case AdsFeed::COL_AD_LINK_TITLE:
                return $ad['layout']['link_title'] ?? null;
            case AdsFeed::COL_AD_PHOTO:
                return $ad['layout']['image_src'] ?? null;
            case AdsFeed::COL_AD_ICON:
                return $ad['layout']['icon_src'] ?? null;

            case AdsFeed::COL_CAMPAIGN_ID:
                return $ad['campaign_id'];
            case AdsFeed::COL_CAMPAIGN_NAME:
                return $ad['campaign']['name'] ?? null;

            case AdsFeed::COL_POST_TEXT:
                return $ad['post']['text'] ?? null;
            case AdsFeed::COL_POST_LINK_IMAGE:
                return $this->getBiggestPic($ad['post']['attachments'][0]['link']['photo']['sizes'] ?? null);
            case AdsFeed::COL_POST_ATTACHMENT_LINK_URL:
                return $ad['post']['attachments'][0]['link']['url'] ?? null;
            case AdsFeed::COL_POST_ID:
                return $ad['post']['id'] ?? null;
            case AdsFeed::COL_POST_OWNER_ID:
                return $ad['post']['owner_id'] ?? null;
            case AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE:
                return $ad['post']['attachments'][0]['link']['title'] ?? null;
            case AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE:
                return $ad['post']['attachments'][0]['link']['button']['action']['type'] ?? null;
            case AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_TITLE:
                return $ad['post']['attachments'][0]['link']['button']['title'] ?? null;
            case AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID:
                return $ad['post']['attachments'][0]['link']['video']['id'] ?? null;
            case AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID:
                return $ad['post']['attachments'][0]['link']['video']['owner_id'] ?? null;

            case AdsFeed::COL_CARD_1_TITLE:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][0]['title'] ?? null;
            case AdsFeed::COL_CARD_1_LINK_URL:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][0]['link_url'] ?? null;
            case AdsFeed::COL_CARD_1_OWNER_ID:
                $cardId = $ad['post']['attachments'][0]['pretty_cards']['cards'][0]['card_id'] ?? null;
                return $cardId ? explode('_', $cardId)[0] : null;
            case AdsFeed::COL_CARD_1_ID:
                $cardId = $ad['post']['attachments'][0]['pretty_cards']['cards'][0]['card_id'] ?? null;
                return $cardId ? explode('_', $cardId)[1] : null;
            case AdsFeed::COL_CARD_1_PHOTO:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][0]['photo'] ?? null;

            case AdsFeed::COL_CARD_2_TITLE:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][1]['title'] ?? null;
            case AdsFeed::COL_CARD_2_LINK_URL:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][1]['link_url'] ?? null;
            case AdsFeed::COL_CARD_2_OWNER_ID:
                $cardId = $ad['post']['attachments'][0]['pretty_cards']['cards'][1]['card_id'] ?? null;
                return $cardId ? explode('_', $cardId)[0] : null;
            case AdsFeed::COL_CARD_2_ID:
                $cardId = $ad['post']['attachments'][0]['pretty_cards']['cards'][1]['card_id'] ?? null;
                return $cardId ? explode('_', $cardId)[1] : null;
            case AdsFeed::COL_CARD_2_PHOTO:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][1]['photo'] ?? null;

            case AdsFeed::COL_CARD_3_TITLE:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][2]['title'] ?? null;
            case AdsFeed::COL_CARD_3_LINK_URL:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][2]['link_url'] ?? null;
            case AdsFeed::COL_CARD_3_OWNER_ID:
                $cardId = $ad['post']['attachments'][0]['pretty_cards']['cards'][2]['card_id'] ?? null;
                return $cardId ? explode('_', $cardId)[0] : null;
            case AdsFeed::COL_CARD_3_ID:
                $cardId = $ad['post']['attachments'][0]['pretty_cards']['cards'][2]['card_id'] ?? null;
                return $cardId ? explode('_', $cardId)[1] : null;
            case AdsFeed::COL_CARD_3_PHOTO:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][2]['photo'] ?? null;

            case AdsFeed::COL_CARD_4_TITLE:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][3]['title'] ?? null;
            case AdsFeed::COL_CARD_4_LINK_URL:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][3]['link_url'] ?? null;
            case AdsFeed::COL_CARD_4_OWNER_ID:
                $cardId = $ad['post']['attachments'][0]['pretty_cards']['cards'][3]['card_id'] ?? null;
                return $cardId ? explode('_', $cardId)[0] : null;
            case AdsFeed::COL_CARD_4_ID:
                $cardId = $ad['post']['attachments'][0]['pretty_cards']['cards'][3]['card_id'] ?? null;
                return $cardId ? explode('_', $cardId)[1] : null;
            case AdsFeed::COL_CARD_4_PHOTO:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][3]['photo'] ?? null;

            case AdsFeed::COL_CARD_5_TITLE:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][4]['title'] ?? null;
            case AdsFeed::COL_CARD_5_LINK_URL:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][4]['link_url'] ?? null;
            case AdsFeed::COL_CARD_5_OWNER_ID:
                $cardId = $ad['post']['attachments'][0]['pretty_cards']['cards'][4]['card_id'] ?? null;
                return $cardId ? explode('_', $cardId)[0] : null;
            case AdsFeed::COL_CARD_5_ID:
                $cardId = $ad['post']['attachments'][0]['pretty_cards']['cards'][4]['card_id'] ?? null;
                return $cardId ? explode('_', $cardId)[1] : null;
            case AdsFeed::COL_CARD_5_PHOTO:
                return $ad['post']['attachments'][0]['pretty_cards']['cards'][4]['photo'] ?? null;

            case AdsFeed::COL_POST_ATTACHMENT_CARDS:
                $cards = $ad['post']['attachments'][0]['pretty_cards']['cards'] ?? [];
                return $cards ? 'pretty_card'.implode(',pretty_card', array_column($cards, 'card_id')) : null;
            case AdsFeed::COL_CLIENT_ID:
                return $this->vk->getClientId();
            case AdsFeed::COL_STATS_URL:
                return $ad['layout']['stats_url'] ?? null;
            default:
                return null;
        }
    }

    private function getBiggestPic($sizes): ?string
    {
        if (!is_array($sizes) || !$sizes) {
            return null;
        }
        usort($sizes, fn ($a, $b) => $b['width'] <=> $a['width']);

        return $sizes[0]['url'] ?? null;
    }

}
