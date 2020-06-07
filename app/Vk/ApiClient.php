<?php

namespace App\Vk;

use App\Connection;
use \GuzzleHttp\Client;

/**
 *
 */
class ApiClient
{

    const UPDATE_STATUS_DONE = 0;
    const UPDATE_STATUS_PARTIAL_FAILURE = 1;
    const UPDATE_STATUS_PARTIAL_INTERRUPTED = 2;

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

    private ?string $clientId = null;
    private Connection $connection;

    private function api(): Client
    {
        return new Client([
            'base_uri' => 'https://api.vk.com/method/',
            'timeout'  => 60.0
        ]);
    }

    public function setClientId(?int $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
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

    public function getClients():array
    {
        return $this->get('ads.getClients');
    }

    public function getFeed(array $adIds, array $fields): array
    {
        $getFieldValue = function (array $ad, string $field) {
            switch ($field) {
                case AdsFeed::COL_AD_ID:
                    return $ad['id'];
                case AdsFeed::COL_AD_NAME:
                    return $ad['name'];
                case AdsFeed::COL_AD_TITLE:
                    return $ad['layout']['title'];
                case AdsFeed::COL_AD_LINK_URL:
                    return $ad['layout']['link_url'];
                case AdsFeed::COL_AD_DESCRIPTION:
                    return $ad['layout']['description'];
                case AdsFeed::COL_AD_LINK_TITLE:
                    return $ad['layout']['link_title'];
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
                case AdsFeed::COL_CLIENT_ID:
                    return $this->clientId;
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

    /**
     * @param array $feed indexed by ad id
     * @param int $adsPerExecution
     * @return array[]
     * @throws \Exception
     */
    public function updateAds(array $feed, int $adsPerExecution = 5): array
    {
        $adIds = array_keys($feed);
        $currentState = $this->getFeed($adIds, array_keys(AdsFeed::FIELDS));

        $fails = 0;
        $remaining = count($feed);
        foreach (array_chunk($feed, $adsPerExecution, true) as $chunk) {
            try {
                $commands = $this->getUpdateCommands($chunk, $currentState);
                $params = ['code' => $this->formatCode($commands)];
                foreach (array_keys($chunk) as $adId) {
                    $captcha = $feed[$adId][AdsFeed::COL_ADK_CAPTCHA];
                    if ($captcha && strpos($captcha, 'sid=') !== false) {
                        $query = [];
                        parse_str(parse_url($captcha, PHP_URL_QUERY), $query);
                        $sid = $query['sid'];

                        if ($code = $feed[$adId][AdsFeed::COL_ADK_CAPTCHA_CODE]) {
                            $params = array_replace($params, [
                                'captcha_sid' => $sid,
                                'captcha_key' => $code
                            ]);
                        }
                    }
                }
                $rsp = $this->post('execute', $params);

                if (!array_key_exists('ads', $rsp) || !array_key_exists('posts', $rsp)) {
                    throw new \RuntimeException('Failed to update ads: ' . json_encode($rsp));
                }

                foreach ($chunk as $adId => $_) {
                    $feed[$adId] = array_replace($feed[$adId], [
                        AdsFeed::COL_ADK_STATUS       => 'done',
                        AdsFeed::COL_ADK_ERR          => '',
                        AdsFeed::COL_ADK_CAPTCHA      => '',
                        AdsFeed::COL_ADK_CAPTCHA_CODE => ''
                    ]);
                }

                foreach ($rsp['ads'] as $r) {
                    if (isset($r['error_desc'])) {
                        $fails++;
                        $feed[$r['id']] = array_replace($feed[$r['id']], [
                            AdsFeed::COL_ADK_STATUS => 'failed',
                            AdsFeed::COL_ADK_ERR    => "Не удалось обновить объявление: {$r['error_code']} {$r['error_desc']}. ",
                        ]);
                    }
                }

                foreach ($rsp['posts'] as $r) {
                    if (!isset($r['ok']) || $r['ok'] != 1) {
                        $fails++;
                        $feed[$r['adId']] = array_replace($feed[$r['adId']], [
                            AdsFeed::COL_ADK_STATUS => 'failed',
                            AdsFeed::COL_ADK_ERR    => $feed[$r['adId']][AdsFeed::COL_ADK_ERR] . "Не удалось обновить пост. ",
                        ]);
                    }
                }

            } catch (CaptchaException $e) {
                foreach ($chunk as $adId => $_) {
                    $feed[$adId] = array_replace($feed[$adId], [
                        AdsFeed::COL_ADK_STATUS       => 'failed',
                        AdsFeed::COL_ADK_ERR          => "Для продолжения нужна капча",
                        AdsFeed::COL_ADK_CAPTCHA      => $e->img,
                        AdsFeed::COL_ADK_CAPTCHA_CODE => ''
                    ]);

                    return [self::UPDATE_STATUS_PARTIAL_INTERRUPTED, $feed];
                }
            } catch (\Exception $e) {
                foreach ($chunk as $adId => $_) {
                    $fails++;
                    $feed[$adId] = array_replace($feed[$adId], [
                        AdsFeed::COL_ADK_STATUS       => 'failed',
                        AdsFeed::COL_ADK_ERR          => "Не удалось обновить обновление: {$e->getMessage()}",
                        AdsFeed::COL_ADK_CAPTCHA      => '',
                        AdsFeed::COL_ADK_CAPTCHA_CODE => ''
                    ]);
                }
            }

            // anti captcha
            $remaining -= count($chunk);
            if ($remaining) {
                $sleep = random_int(120, 130);
                echo "sleep now {$sleep}\n";
                sleep($sleep);
            }
        }

        return [$fails ? self::UPDATE_STATUS_PARTIAL_FAILURE : self::UPDATE_STATUS_DONE, $feed];
    }

    private function formatCode(array $commands): string
    {
        $forType = fn ($type) => fn ($command) => $command['type'] == $type;

        $code = "var a = '{$this->getConnection()->data['account']}';\n";
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
        $commands = [];
        foreach ($feed as $ad) {
            $adId = $ad[AdsFeed::COL_AD_ID];
            $currentAd = $currentState[$adId];

            $needUpdate = function ($field) use($ad, $currentAd) {
                return array_key_exists($field, $ad) && $ad[$field] != $currentAd[$field];
            };

            $u = [];
            if ($needUpdate(AdsFeed::COL_AD_NAME)) {
                $u['name'] = $ad[AdsFeed::COL_AD_NAME];
            }
            if ($needUpdate(AdsFeed::COL_AD_LINK_URL)) {
                $u['link_url'] = $ad[AdsFeed::COL_AD_LINK_URL];
            }
            if ($needUpdate(AdsFeed::COL_AD_TITLE)) {
                $u['title'] = $ad[AdsFeed::COL_AD_TITLE];
            }
            if ($needUpdate(AdsFeed::COL_AD_DESCRIPTION)) {
                $u['description'] = $ad[AdsFeed::COL_AD_DESCRIPTION];
            }
            if ($needUpdate(AdsFeed::COL_AD_LINK_TITLE)) {
                $u['link_title'] = $ad[AdsFeed::COL_AD_LINK_TITLE];
            }
            if ($u) {
                $commands[] = [
                    'type' => 'updateAd',
                    'item' => array_replace(['ad_id' => $adId], $u)
                ];
            }

            $p = [];
            if ($needUpdate(AdsFeed::COL_POST_TEXT)) {
                $p['message'] = $ad[AdsFeed::COL_POST_TEXT];
            }
            if ($needUpdate(AdsFeed::COL_POST_ATTACHMENT_LINK_URL)) {
                $p['attachments'] = $ad[AdsFeed::COL_POST_ATTACHMENT_LINK_URL];
            }
            if ($needUpdate(AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE)) {
                $p['link_button'] = $ad[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE];
            }
            if ($needUpdate(AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE)) {
                $p['link_title'] = $ad[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE];
            }
            if ($needUpdate(AdsFeed::COL_POST_LINK_IMAGE)) {
                $p['link_image'] = $ad[AdsFeed::COL_POST_LINK_IMAGE];
            }
            if ($needUpdate(AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID)) {
                $p['link_video'] = "{$currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID]}_{$ad[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID]}";

                // параметр link_video может быть указан только вместе с параметрами link_button, link_title.
                // если они не были заданы для изменения, использовать существующее значение
                if (!isset($p['link_button'])) {
                    $p['link_button'] = $currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE];
                }
                if (!isset($p['link_title'])) {
                    $p['link_title'] = $currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE];
                }
            }

            if ($p) {
                $fields = [
                    'owner_id' => $currentAd[AdsFeed::COL_POST_OWNER_ID],
                    'post_id'  => $currentAd[AdsFeed::COL_POST_ID]
                ];
                if ($currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID]) {
                    $fields['link_video'] = "{$currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID]}_{$currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID]}";
                }
                if ($currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE]) {
                    $fields['link_title'] = $currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE];
                }
                if ($currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE]) {
                    $fields['link_button'] = $currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE];
                }
                if ($currentAd[AdsFeed::COL_POST_LINK_IMAGE]) {
                    $fields['link_image'] = $currentAd[AdsFeed::COL_POST_LINK_IMAGE];
                }
                if ($currentAd[AdsFeed::COL_POST_TEXT]) {
                    $fields['message'] = $currentAd[AdsFeed::COL_POST_TEXT];
                }
                if ($currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_URL]) {
                    $fields['attachments'] = $currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_URL];
                }
                $commands[] = [
                    'type' => 'updatePost',
                    'adId' => $adId,
                    'item' => array_replace($fields, $p),
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
        $rsp = $this->api()->post($method, ['form_params' => $body, 'query' => $this->addDefaultParams($queryParams)]);
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

        return $data['response'];
    }

    private function get(string $method, array $queryParams = [])
    {
        $rsp = $this->api()->get($method, ['query' => $this->addDefaultParams($queryParams)]);

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
            'access_token' => $this->getConnection()->data['access_token'],
            'v'            => self::VERSION,
            'account_id'   => $this->getConnection()->data['account']
        ];

        if ($this->clientId) {
            $defaults['client_id'] = $this->clientId;
        }

        return array_replace($defaults, $params);
    }

    private function getConnection():Connection
    {
        if (!isset($this->connection)) {
            $this->connection = Connection::where('system', 'vk')->firstOrFail();
        }

        return $this->connection;
    }

}
