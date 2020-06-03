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
            'timeout'  => 30.0
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
        /** @todo fix 100 post limits https://vk.com/dev/wall.getById */
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
                case AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE:
                    return $ad['post']['attachments'][0]['link']['title'] ?? null;
                case AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE:
                    return $ad['post']['attachments'][0]['link']['button']['action']['type'] ?? null;
                case AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID:
                    return $ad['post']['attachments'][0]['link']['video']['id'] ?? null;
                case AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID:
                    return $ad['post']['attachments'][0]['link']['video']['owner_id'] ?? null;
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
                $link = $ad['layout']['link_url'] ?? '';
                $re = '/^http(s)?:\/\/vk.com\/wall/';
                if (preg_match($re, $link)) {
                    $adPostIds[$ad['id']] = preg_replace($re, '', $link);
                }
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


    /**
     * @param array $feed
     * @param int $adsPerExecution
     * @return array
     */
    public function updateAds(array $feed, int $adsPerExecution = 3): array
    {
        $adIds = array_filter(array_unique(array_column($feed, AdsFeed::COL_AD_ID)));
        $currentState = $this->getFeed($adIds, array_keys(AdsFeed::FIELDS));

        $errors = array_fill_keys($adIds,null);
        $commands = $this->getUpdateCommands($feed, $currentState);
        foreach (array_chunk($commands, $adsPerExecution, true) as $chunk) {
            try {
                $rsp = $this->post('execute', [
                    'code' => $this->formatCode(call_user_func_array('array_merge', $chunk))
                ]);

                if (!array_key_exists('ads', $rsp) || !array_key_exists('posts', $rsp)) {
                    throw new \RuntimeException('Failed to update ads: ' . json_encode($rsp));
                }

                foreach ($chunk as $adId => $_) {
                    $errors[$adId] = '';
                }

                foreach ($rsp['ads'] as $r) {
                    if (isset($r['error_desc'])) {
                        $errors[$r['id']] .= "Не удалось обновить объявление: {$r['error_code']} {$r['error_desc']}. ";
                    }
                }

                foreach ($rsp['posts'] as $r) {
                    if (!isset($r['ok']) || $r['ok'] != 1) {
                        $errors[$r['adId']] .= "Не удалось обновить пост. ";
                    }
                }
            } catch (CaptchaException $e) {
                foreach ($chunk as $adId => $_) {
                    $errors[$adId] .= "Нужна капча - {$e->img}";

                    break;
                }
            } catch (\Exception $e) {
                foreach ($chunk as $adId => $_) {
                    $errors[$adId] .= "Не удалось обновить обновление: {$e->getMessage()}";
                }
            }
        }

        return $errors;
    }

    private function formatCode(array $commands): string
    {
        $forType = fn($type) => fn($command) => $command['type'] == $type;

        $code = "var a = '{$this->account}';\n";
        $code .= "var result = {'ads': [], 'posts': []};\n";

        $adsUpdates = array_filter($commands, $forType('updateAd'));
        foreach (array_chunk($adsUpdates, 5) as $updates) {
            $data = json_encode(array_column($updates, 'item'), JSON_UNESCAPED_UNICODE);
            $code .= "result.ads = API.ads.updateAds({'data': '{$data}', 'account_id': a});\n";
        }

        $postUpdates = array_filter($commands, $forType('updatePost'));
        foreach ($postUpdates as $update) {
            $data = json_encode($update['item'], JSON_UNESCAPED_UNICODE);
            $code .= "result.posts.push({'ok': API.wall.editAdsStealth({$data}), 'adId': '{$update['adId']}'});\n";
        }

        $code .= 'return result;';

        return $code;
    }

    private function getUpdateCommands(array $feed, array $currentState): array
    {
        $feedCols = array_keys($feed[0]);

        $commands = [];
        if (AdsFeed::dependsOn('ad', $feedCols)) {
            foreach($feed as $ad) {
                $adId = $ad[AdsFeed::COL_AD_ID];
                $i = ['ad_id' => $adId];
                if (isset($ad[AdsFeed::COL_AD_NAME]) && $adName = $ad[AdsFeed::COL_AD_NAME]) {
                    $i['name'] = $adName;
                }
                if (isset($ad[AdsFeed::COL_AD_LINK_URL]) && $adUrl = $ad[AdsFeed::COL_AD_LINK_URL]) {
                    $i['link_url'] = $adUrl;
                }
                if (!isset($commands[$adId])) {
                    $commands[$adId] = [];
                }
                $commands[$adId][] = [
                    'type' => 'updateAd',
                    'item' => $i
                ];
            }
        }

        if (AdsFeed::dependsOn('post', $feedCols)) {
            foreach ($feed as $ad) {
                $adId = $ad[AdsFeed::COL_AD_ID];

                $ad = array_replace($currentState[$adId], $ad);
                $p = [
                    'owner_id' => $ad[AdsFeed::COL_POST_OWNER_ID],
                    'post_id'  => $ad[AdsFeed::COL_POST_ID],
                ];
                if (isset($ad[AdsFeed::COL_POST_TEXT])) {
                    $p['message'] = $ad[AdsFeed::COL_POST_TEXT];
                }
                if (isset($ad[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE])) {
                    $p['link_button'] = $ad[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE];
                }
                if (isset($ad[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE])) {
                    $p['link_title'] = $ad[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE];
                }
                if (isset($ad[AdsFeed::COL_POST_ATTACHMENT_LINK_URL])) {
                    $p['attachments'] = $ad[AdsFeed::COL_POST_ATTACHMENT_LINK_URL];

                    if (isset($ad[AdsFeed::COL_POST_LINK_IMAGE])) {
                        $p['link_image'] = $ad[AdsFeed::COL_POST_LINK_IMAGE];
                    }
                    if (isset($ad[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID])) {
                        $p['link_video'] = "{$ad[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID]}_{$ad[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID]}";
                    }
                }

                if (!isset($commands[$adId])) {
                    $commands[$adId] = [];
                }
                $commands[$adId][] = [
                    'type' => 'updatePost',
                    'item' => $p,
                    'adId' => $adId
                ];
            }
        }

        return $commands;
    }

    /**
     * @param string $method
     * @param array $body
     * @param array $queryParams
     * @return mixed
     * @throws CaptchaException
     */
    private function post(string $method, array $body = [], array $queryParams = [])
    {
        $rsp = $this->http->post($method, ['form_params' => $body, 'query' => $this->addDefaultParams($queryParams)]);
        $data = \json_decode($rsp->getBody()->getContents(), true);

        if (!is_array($data)) {
            throw new \RuntimeException("Failed to decode response: {$method} - " . (string)$rsp->getBody());
        }
        if (isset($data['error']) && $err = $data['error']) {
            $msg = $err['error_msg'] ?? null;
            if ($msg == 'Captcha needed') {
                $e = new CaptchaException('Captcha needed');
                $e->sid = $err['captcha_sid'];
                $e->img = $err['captcha_img'];

                throw $e;
            }
        }
        if (!array_key_exists('response', $data)) {
            throw new \RuntimeException("Unexpected response: {$method} " . (string)$rsp->getBody());
        }

        sleep(2);

        return $data['response'];
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
