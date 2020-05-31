<?php

namespace App\Vk;

use \GuzzleHttp\Client;

/**
 *
 */
class ApiClient
{

    const AD_FORMAT_TEXT = 1;
    const AD_FORMAT_BIG_ING = 2;
    const AD_FORMAT_PROMO = 4;
    const AD_FORMAT_SPEC_FOR_GROUPS = 8;
    const AD_FORMAT_GROUP_POST = 9;
    const AD_FORMAT_ADAPTIVE = 11;

    const AD_COST_TYPE_CLICKS = 0;
    const AD_COST_TYPE_VIEWS = 1;
    const AD_COST_TYPE_OPTIMIZED_VIEWS = 3;

    const VERSION = '5.103';

    private string $account;
    private string $accessToken;
    private ?string $clientId;
    private Client $http;

    private function __construct(string $account, string $accessToken, ?string $clientId = null)
    {
        $this->account = $account;
        $this->accessToken = $accessToken;
        $this->clientId = $clientId;
        $this->http = new Client([
            'base_uri' => 'https://api.vk.com/method/',
            'timeout'  => 10.0
        ]);
    }

    public static function instance()
    {
        $creds = json_decode(file_get_contents(getenv('VK_CREDENTIALS_FILE')), true);
        if (!$creds) {
            throw new \RuntimeException('Invalid VK credentials');
        }

        return new self($creds['account'], $creds['access_token'], $creds['client_id']);
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function getAccount(): ?string
    {
        return $this->account;
    }

    public function getCampaigns()
    {
        return $this->get('ads.getCampaigns');
    }

    public function getAds(array $campaignIds)
    {
        return $this->get('ads.getAds', ['campaign_ids' => json_encode($campaignIds)]);
    }

    public function getAdsTargeting(array $adIds): array
    {
        return $this->get('ads.getAdsTargeting', ['ad_ids' => json_encode($adIds)]);
    }

    public function getAdsLayout(array $adIds): array
    {
        return $this->get('ads.getAdsLayout', ['ad_ids' => json_encode($adIds)]);
    }

    public function getWallPosts(array $posts): array
    {
        return $this->get('wall.getById', ['posts' => implode(',', $posts)]);
    }

    public function getFeed(array $adIds, array $fields): array
    {
        $getFieldValue = function (array $ad, string $field) {
            switch ($field) {
                case AdsFeed::COL_AD_ID:
                    return $ad['id'];
                case AdsFeed::COL_AD_NAME:
                    return $ad['name'];
                case AdsFeed::COL_AD_LINK_URL:
                    return $ad['layout']['link_url'];
                case AdsFeed::COL_CAMPAIGN_ID:
                    return $ad['campaign_id'];
                case AdsFeed::COL_CAMPAIGN_NAME:
                    return $ad['campaign']['name'] ?? null;
                case AdsFeed::COL_POST_TEXT:
                    return $ad['post']['text'] ?? null;
                case AdsFeed::COL_POST_LINK_IMAGE:
                    return $ad['post']['attachments'][0]['link']['photo']['sizes'][0]['url'] ?? null;
                case AdsFeed::COL_POST_ATTACHMENT_LINK_URL:
                    return $ad['post']['attachments'][0]['link']['url'] ?? null;
                case AdsFeed::COL_POST_ID:
                    return $ad['post']['id'] ?? null;
                case AdsFeed::COL_POST_OWNER_ID:
                    return $ad['post']['owner_id'] ?? null;
                case AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE:
                    return $ad['post']['attachments'][0]['link']['button']['action']['type'] ?? null;
                default:
                    return null;
            }
        };

        $ads = [];
        foreach ($this->get('ads.getAds', ['ad_ids' => json_encode($adIds)]) as $ad) {
            $ads[$ad['id']] = $ad;
        }
        if (!$ads) {
            return [];
        }

        $layouts = $this->getAdsLayout(array_keys($ads));
        foreach ($layouts as $layout) {
            $ads[$layout['id']]['layout'] = $layout;
        }

        if (AdsFeed::dependsOn('campaign', $fields)) {
            $campaigns = [];
            foreach ($this->getCampaigns() as $campaign) {
                $campaigns[$campaign['id']] = $campaign;
            }

            foreach ($ads as $adId => $ad) {
                $ads[$adId]['campaign'] = $campaigns[$ad['campaign_id']]; // @todo: null когда удалена?
            }
        }


        if (AdsFeed::dependsOn('post', $fields)) {
            $adPostIds = [];
            foreach ($ads as $ad) {
                $adPostIds[$ad['id']] = preg_replace('/^http(s)?:\/\/vk.com\/wall/', '',
                    $ad['layout']['link_url'] ?? '');
            }

            $postIds = array_filter(array_unique(array_values($adPostIds)));
            if ($postIds) {
                $posts = $this->getWallPosts($postIds);

                $postIdAds = array_flip($adPostIds);
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
                $row[$field] = $getFieldValue($ad, $field);
            }
            $rows[$ad['id']] = $row;
        }

        return $rows;
    }

    public function createAd(array $ad): int
    {
        $fields = [
            'ad_format'    => $ad[AdsFeed::COL_AD_FORMAT],
            'autobidding'  => $ad[AdsFeed::COL_AUTOBIDDING],
            'campaign_id'  => $ad[AdsFeed::COL_CAMPAIGN_ID],
            'name'         => $ad[AdsFeed::COL_AD_NAME],
            'cost_type'    => $ad[AdsFeed::COL_COST_TYPE],
            'goal_type'    => $ad[AdsFeed::COL_GOAL_TYPE],
            'category1_id' => $ad[AdsFeed::COL_AD_CATEGORY1],
            'country'      => $ad[AdsFeed::COL_AD_TARGETING_COUNTRY],
            'cities'       => $ad[AdsFeed::COL_AD_TARGETING_CITIES],
            'day_limit'    => $ad[AdsFeed::COL_AD_DAY_LIMIT],
        ];
        if ($ad[AdsFeed::COL_COST_TYPE] == self::AD_COST_TYPE_OPTIMIZED_VIEWS) {
            $fields['ocpm'] = $ad[AdsFeed::COL_AD_OCPM];
        }
        if (AdsFeed::dependsOn('post', array_keys($ad))) {
            $postId = $this->createWallPost($ad);
            sleep(4); // to prevent 603  error "Invalid community post"
            $fields['link_url'] = "http://vk.com/wall{$ad[AdsFeed::COL_POST_OWNER_ID]}_{$postId}";
        }
        $result = $this->get('ads.createAds', ['data' => json_encode([$fields])]);

        if (isset($result['error_desc'])) {
            // appears occasionally, they say due to servers out of sync
            if (strpos($result['error_desc'], 'Invalid community post')) {
                sleep(4);
                $result = $this->get('ads.createAds', ['data' => json_encode([$fields])]);
            }
        }

        $adId = $result[0]['id'] ?? null;
        if (!$adId) {
            throw new \RuntimeException('Failed to create ad: ' . json_encode($result));
        }

        return intval($adId);
    }

    private function createWallPost(array $post): int
    {
        $fields = [
            'attachments' => $post[AdsFeed::COL_POST_ATTACHMENT_LINK_URL],
            'owner_id'    => $post[AdsFeed::COL_POST_OWNER_ID],
            'message'     => $post[AdsFeed::COL_POST_TEXT],
            'signed'      => 0,
            'guid'        => uniqid('stealth_post'),
            'link_button' => $post[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE],
            'link_image'  => $post[AdsFeed::COL_POST_LINK_IMAGE]
        ];

        $rsp = $this->get('wall.postAdsStealth', $fields);
        if (!isset($rsp['post_id'])) {
            throw new \RuntimeException("Failed to create post: " . json_encode($rsp));
        }

        return $rsp['post_id'];
    }

    public function updateAds(array $feed): array
    {
        if (!$feed) {
            return [];
        }
        $feedColumns = array_keys($feed[0]);

        if (!in_array(AdsFeed::COL_AD_ID, $feedColumns)) {
            throw new \RuntimeException(sprintf("Feed column '%s' required for ads update", AdsFeed::COL_AD_ID));
        }

        $adIds = array_filter(array_unique(array_column($feed, AdsFeed::COL_AD_ID)));
        $currentState = $this->getFeed($adIds, array_keys(AdsFeed::FIELDS));

        /** @todo handle 25 operations limit  */
        $code = '';
        $code .= "var a = '{$this->account}';";
        $code .= "var result = {'ads': null, 'posts': []};";

        if (AdsFeed::dependsOn('ad', $feedColumns)) {
            $items = [];
            foreach ($feed as $ad) {
                $adFields = [
                    'ad_id' => $ad[AdsFeed::COL_AD_ID],
                ];
                if (isset($ad[AdsFeed::COL_AD_NAME]) && $adName = $ad[AdsFeed::COL_AD_NAME]) {
                    $adFields['name'] = $adName;
                }
                if (isset($ad[AdsFeed::COL_AD_LINK_URL]) && $adUrl = $ad[AdsFeed::COL_AD_LINK_URL]) {
                    $adFields['link_url'] = $adUrl;
                }
                $items[] = $adFields;
            }
            $code .= "
                result.ads = API.ads.updateAds({
                    'data': '" . json_encode($items, JSON_UNESCAPED_UNICODE) . "',
                    'account_id': a
                });
            ";
        }

        if (AdsFeed::dependsOn('post', $feedColumns)) {
            $posts = [];
            foreach ($feed as $ad) {
                $ad = array_replace($currentState[$ad[AdsFeed::COL_AD_ID]], $ad);
                $postFields = [
                    'owner_id' => $ad[AdsFeed::COL_POST_OWNER_ID],
                    'post_id'  => $ad[AdsFeed::COL_POST_ID],
                ];
                if (isset($ad[AdsFeed::COL_POST_TEXT])) {
                    $postFields['message'] = $ad[AdsFeed::COL_POST_TEXT];
                }
                if (isset($ad[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE])) {
                    $postFields['link_button'] = $ad[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE];
                }
                if (isset($ad[AdsFeed::COL_POST_LINK_IMAGE])) {
                    $postFields['attachments'] = $ad[AdsFeed::COL_POST_ATTACHMENT_LINK_URL];
                    $postFields['link_image'] = $ad[AdsFeed::COL_POST_LINK_IMAGE];
                }
                $posts[] = $postFields;
            }

            foreach ($posts as $post) {
                $params = json_encode(array_replace($post, ['account_id' => 'a']), JSON_UNESCAPED_UNICODE);
                $code .= "result.posts.push(API.wall.editAdsStealth({$params}));";
            }
        }

        $code .= 'return result;';
        $rsp = $this->get('execute', ['code' => $code]);

        if (!array_key_exists('ads', $rsp) || !array_key_exists('posts', $rsp)) {
            throw new \RuntimeException('Failed to update ads: ' . json_encode($rsp));
        }

        $errors = [];
        foreach ($feed as $i => $item) {
            $error = null;
            if (isset($rsp['ads'][$i]['error_desc'])) {
                $error .= "Не удалось обновить объявление: {$rsp['ads'][$i]['error_code']} {$rsp['ads'][$i]['error_desc']}. ";
            }
            if (isset($rsp['posts'][$i]) && $rsp['posts'][$i] != 1) {
                $error .= "Не удалось обновить пост: {$rsp['posts'][$i]}. ";
            }
            $errors[$item[AdsFeed::COL_AD_ID]] = $error;
        }

        return $errors;
    }

    private function editWallPost($post)
    {
        $fields = [
            'owner_id' => $post[AdsFeed::COL_POST_OWNER_ID],
            'post_id'  => $post[AdsFeed::COL_POST_ID],
        ];
        if (isset($post[AdsFeed::COL_POST_TEXT])) {
            $fields['message'] = $post[AdsFeed::COL_POST_TEXT];
        }
        if (isset($post[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE])) {
            $fields['link_button'] = $post[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE];
        }
        if (isset($post[AdsFeed::COL_POST_LINK_IMAGE])) {
            $fields['attachments'] = $post[AdsFeed::COL_POST_ATTACHMENT_LINK_URL];
            $fields['link_image'] = $post[AdsFeed::COL_POST_LINK_IMAGE];
        }
        $this->get('wall.editAdsStealth', $fields);
    }

    private function get(string $method, array $queryParams = [])
    {
        $rsp = $this->http->get($method, ['query' => $this->addDefaultParams($queryParams)]);

        $data = \json_decode($rsp->getBody()->getContents(), true);

        if (is_null($data) || !isset($data['response'])) {
            throw new \RuntimeException("Failed to decode response: {$method} " . (string)$rsp->getBody());
        }

        sleep(1);
        return $data['response'];
    }

    private function addDefaultParams(array $params): array
    {
        $defaults = [
            'access_token' => $this->accessToken,
            'v'            => self::VERSION,
        ];
        if ($this->account) {
            $defaults['account_id'] = $this->account;
        }
        if ($this->clientId) {
            $defaults['client_id'] = $this->clientId;
        }

        return array_replace($defaults, $params);
    }

}
