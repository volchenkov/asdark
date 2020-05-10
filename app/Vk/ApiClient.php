<?php

namespace App\Vk;

use \GuzzleHttp\Client;

/**
 *
 */
class ApiClient
{

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

    public function getCampaigns()
    {
        return $this->get('ads.getCampaigns');
    }

    public function getAds(array $campaignIds)
    {
        return $this->get('ads.getAds', ['campaign_ids' => json_encode($campaignIds)]);
    }

    public function getAdsTargeting(array $adIds)
    {
        return $this->get('ads.getAdsTargeting', ['ad_ids' => json_encode($adIds)]);
    }

    public function getAdsLayout(array $adIds)
    {
        return $this->get('ads.getAdsLayout', ['ad_ids' => json_encode($adIds)]);
    }

    public function getWallPosts($posts)
    {
        return $this->get('wall.getById', ['posts' => implode(',', $posts)]);
    }

    public function createAd(Ad $ad): int
    {
        $fields = [
            'ad_format'    => $ad->format,
            'autobidding'  => $ad->autobidding,
            'campaign_id'  => $ad->campaignId,
            'name'         => $ad->name,
            'cost_type'    => $ad->costType,
            'goal_type'    => $ad->goalType,
            'category1_id' => $ad->category1Id,
            'country'      => $ad->targeting->country,
            'cities'       => implode(',', $ad->targeting->cities),
        ];
        if ($ad->costType === Ad::COST_TYPE_CLICKS) {
            $fields['cpc'] = $ad->cpc;
        }
        if ($ad->costType === Ad::COST_TYPE_VIEWS) {
            $fields['cpm'] = $ad->cpm;
        }
        if ($ad->costType === Ad::COST_TYPE_OPTIMIZED_VIEWS) {
            $fields['ocpm'] = $ad->ocpm;
        }
        if (isset($ad->post)) {
            $postId = $this->createWallPost($ad->post);
            sleep(4); // to prevent 603  error "Invalid community post"
            $fields['link_url'] = "http://vk.com/wall{$ad->post->ownerId}_{$postId}";
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
            throw new \RuntimeException('Failed to create ad: '.json_encode($result));
        }

        return intval($adId);
    }

    public function createWallPost(WallPostStealth $post): int
    {
        $fields = [
            'attachments' => implode(',', $post->attachments),
            'owner_id'    => $post->ownerId,
            'message'     => $post->message,
            'signed'      => $post->signed,
            'guid'        => $post->guid,
            'link_button' => $post->linkButton,
        ];
        if (isset($post->linkTitle)) {
            $fields['link_title'] = $post->linkTitle;
        }
        if (isset($post->linkVideo)) {
            $fields['link_video'] = $post->linkVideo;
        }
        if (isset($post->linkImage)) {
            $fields['link_image'] = $post->linkImage;
        }

        $rsp = $this->get('wall.postAdsStealth', $fields);
        if (!isset($rsp['post_id'])) {
            throw new \RuntimeException("Failed to create post: ".json_encode($rsp));
        }

        return $rsp['post_id'];
    }

    private function get(string $method, array $queryParams = [])
    {
        $rsp = $this->http->get($method, ['query' => $this->addDefaultParams($queryParams)]);

        $data = \json_decode($rsp->getBody()->getContents(), true);

        if (is_null($data) || !isset($data['response'])) {
            throw new \RuntimeException("Failed to decode response: {$method} ".(string)$rsp->getBody());
        }

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
