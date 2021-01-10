<?php

namespace App\Vk;

use App\Connection;
use App\ExportOperation;
use \GuzzleHttp\Client;
use Illuminate\Support\Collection;

/**
 *
 */
class ApiClient
{

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

    public function getAccounts(): array
    {
        return $this->get('ads.getAccounts');
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

    public function getClients(): ?array
    {
        if (!$this->getConnection()->isAgency()) {
            return null;
        }

        return $this->get('ads.getClients');
    }

    public function getFeed(array $adIds, array $fields): array
    {
        $adIds = array_unique($adIds);
        $ads = [];
        // предупредить 414 ответ из-за превышения допустимого размера запроса. Эмпирически ~200 id проходит
        foreach (array_chunk($adIds, 200) as $adIdsChunk) {
            $adsChunk = $this->get('ads.getAds', ['ad_ids' => json_encode($adIdsChunk)]);
            foreach ($adsChunk as $ad) {
                $ads[$ad['id']] = $ad;
            }
            if ($adsChunk) {
                $layouts = $this->getAdsLayout(array_column($adsChunk, 'id'));
                foreach ($layouts as $layout) {
                    $ads[$layout['id']]['layout'] = $layout;
                }
            }
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

            $postIdAds = array_flip($adPostIds);
            // ограничение API - по 100 за раз
            // см. https://vk.com/dev/wall.getById
            foreach (array_chunk($postIds, 100) as $postIdsChunk) {
                $posts = $this->getWallPosts($postIdsChunk);

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

    /**
     * @param Collection $operations
     * @param string|null $captcha
     * @param string|null $captchaKey
     * @return Collection
     * @throws CaptchaException
     */
    public function batchUpdate(Collection $operations, $captcha = null, $captchaKey = null): Collection
    {
        $operations = $this->uploadImages($operations);
        $uploadedImagesOperations = $operations->whereNotIn('status', [ExportOperation::STATUS_FAILED]);

        $code = $this->formatCode($uploadedImagesOperations);
        $rsp = $this->execute($code, $captcha, $captchaKey);

        if (!array_key_exists('ads', $rsp) || !array_key_exists('posts', $rsp)) {
            throw new \RuntimeException('Failed to update ads: ' . json_encode($rsp));
        }

        foreach ($uploadedImagesOperations->where('type', ExportOperation::TYPE_UPDATE_AD) as $op) {
            $op->status = ExportOperation::STATUS_FAILED;
            $op->error = null;
            if (is_array($rsp['ads'])) {
                foreach ($rsp['ads'] as $adResult) {
                    if ($adResult['id'] == $op->ad_id) {
                        $failed = isset($adResult['error_desc']);
                        $op->status = $failed ? ExportOperation::STATUS_FAILED : ExportOperation::STATUS_DONE;
                        $op->error = $failed ? json_encode($adResult) : null;
                    }
                }
            }
        }

        foreach ($uploadedImagesOperations->where('type', ExportOperation::TYPE_UPDATE_POST) as $op) {
            $op->status = ExportOperation::STATUS_FAILED;
            $op->error = 'Не удалось обновить пост';
            foreach ($rsp['posts'] as $postResult) {
                if ($postResult['adId'] == $op->ad_id) {
                    if (isset($postResult['ok']) && $postResult['ok'] == 1) {
                        $op->status = ExportOperation::STATUS_DONE;
                        $op->error = null;
                    }
                }
            }
        }

        foreach ($uploadedImagesOperations->where('type', ExportOperation::TYPE_UPDATE_CARD) as $op) {
            $op->status = ExportOperation::STATUS_FAILED;
            $op->error = 'Не удалось обновить карточку';
            foreach ($rsp['cards'] as $cardResult) {
                if ($cardResult['operationId'] == $op->id && $cardResult['ok'] !== false) {
                    $op->status = ExportOperation::STATUS_DONE;
                    $op->error = null;
                }
            }
        }

        return $operations;
    }

    private function formatCode(Collection $operations): string
    {
        $code = "var a = '{$this->getConnection()->data['account_id']}';\n";
        $code .= "var result = {'ads': [], 'posts': [], 'cards': []};\n";

        $adsUpdates = $operations->where('type', ExportOperation::TYPE_UPDATE_AD)->all();
        if ($adsUpdates) {
            $adsData = [];
            foreach ($adsUpdates as $operation) {
                $adsData[] = $this->getAdFields($operation);
            }
            $encoded = json_encode($adsData, JSON_UNESCAPED_UNICODE);
            $code .= "result.ads = API.ads.updateAds({'data': '{$encoded}', 'account_id': a});\n";
        }

        $postUpdates = $operations->where('type', ExportOperation::TYPE_UPDATE_POST)->all();
        foreach ($postUpdates as $operation) {
            $data = json_encode($this->getPostFields($operation), JSON_UNESCAPED_UNICODE);
            $code .= "result.posts.push({'ok': API.wall.editAdsStealth({$data}), 'adId': '{$operation->ad_id}'});\n";
        }

        $cardsUpdates = $operations->where('type', ExportOperation::TYPE_UPDATE_CARD)->all();
        foreach ($cardsUpdates as $operation) {
            $data = json_encode($this->getCardFields($operation), JSON_UNESCAPED_UNICODE);
            $code .= "result.cards.push({'ok': API.prettyCards.edit({$data}), 'operationId': '{$operation->id}'});\n";
        }

        $code .= 'return result;';

        return $code;
    }

    private function execute(string $code, $captcha = null, $captchaKey = null)
    {
        $payload = ['code' => $code];

        if ($captcha && $captchaKey) {
            $payload['captcha_sid'] = $this->fetchCaptchaSid($captcha);
            $payload['captcha_key'] = $captchaKey;
        }

        return $this->post('execute', $payload);
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

        if (is_null($data)) {
            throw new \RuntimeException("No response data: {$method} " . (string)$rsp->getBody());
        }

        if (isset($data['error']) && is_array($data['error'])) {
            $error = $data['error'];

            if (isset($error['error_code']) && $error['error_code'] == 9) {
                throw new FloodControlException($error['error_msg'] ?? 'Unexpected error');
            }

            throw new ErrorResponseException($error['error_msg'] ?? 'Unexpected error');
        }

        if (!isset($data['response'])) {
            throw new \RuntimeException("Failed to decode response: {$method}" . (string)$rsp->getBody());
        }

        sleep(1);

        return $data['response'];
    }

    private function addDefaultParams(array $params): array
    {
        $conn = $this->getConnection();
        $defaults = [
            'access_token' => $conn->data['access_token'],
            'v'            => self::VERSION
        ];

        if (isset($conn->data['account_id'])) {
            $defaults['account_id'] = $conn->data['account_id'];
        }

        if ($this->clientId) {
            $defaults['client_id'] = $this->clientId;
        }

        return array_replace($defaults, $params);
    }

    private function getConnection(): Connection
    {
        if (!isset($this->connection)) {
            $this->connection = Connection::where('system', 'vk')->firstOrFail();
        }

        return $this->connection;
    }

    private function getAdFields(ExportOperation $operation): array
    {
        $ad = [
            'ad_id' => $operation->ad_id
        ];

        $newAd = $operation->state_to;
        if (isset($newAd[AdsFeed::COL_AD_NAME])) {
            $ad['name'] = $newAd[AdsFeed::COL_AD_NAME];
        }
        if (isset($newAd[AdsFeed::COL_AD_LINK_URL])) {
            $ad['link_url'] = $newAd[AdsFeed::COL_AD_LINK_URL];
        }
        if (isset($newAd[AdsFeed::COL_AD_TITLE])) {
            $ad['title'] = $newAd[AdsFeed::COL_AD_TITLE];
        }
        if (isset($newAd[AdsFeed::COL_AD_DESCRIPTION])) {
            $ad['description'] = $newAd[AdsFeed::COL_AD_DESCRIPTION];
        }
        if (isset($newAd[AdsFeed::COL_AD_LINK_TITLE])) {
            $ad['link_title'] = $newAd[AdsFeed::COL_AD_LINK_TITLE];
        }
        if (isset($newAd[AdsFeed::COL_AD_PHOTO])) {
            $ad['photo'] = $operation->runtime['photo_upload'];
        }
        if (isset($newAd[AdsFeed::COL_AD_ICON])) {
            $ad['photo_icon'] = $operation->runtime['icon_upload'];
        }

        return $ad;
    }

    private function getCardFields(ExportOperation $operation): array
    {
        $new = $operation->state_to;
        $current = $operation->state_from;

        $ownerId = $cardId = null;
        foreach (array_keys($new) as $field) {
            switch (AdsFeed::FIELDS[$field]['entity']) {
                case 'card1':
                    $ownerId = $current[AdsFeed::COL_CARD_1_OWNER_ID];
                    $cardId = $current[AdsFeed::COL_CARD_1_ID];
                    break;
                case 'card2':
                    $ownerId = $current[AdsFeed::COL_CARD_2_OWNER_ID];
                    $cardId = $current[AdsFeed::COL_CARD_2_ID];
                    break;
                case 'card3':
                    $ownerId = $current[AdsFeed::COL_CARD_3_OWNER_ID];
                    $cardId = $current[AdsFeed::COL_CARD_3_ID];
                    break;
                case 'card4':
                    $ownerId = $current[AdsFeed::COL_CARD_4_OWNER_ID];
                    $cardId = $current[AdsFeed::COL_CARD_4_ID];
                    break;
                case 'card5':
                    $ownerId = $current[AdsFeed::COL_CARD_5_OWNER_ID];
                    $cardId = $current[AdsFeed::COL_CARD_5_ID];
                    break;
            }
        }
        if (!$ownerId || !$cardId) {
            throw new \UnexpectedValueException('Required param is undefined: owner_id or card_id');
        }

        $card = [
            'owner_id' => $ownerId,
            'card_id'  => $cardId
        ];
        $cardTitleFeedFields = [
            AdsFeed::COL_CARD_1_TITLE,
            AdsFeed::COL_CARD_2_TITLE,
            AdsFeed::COL_CARD_3_TITLE,
            AdsFeed::COL_CARD_4_TITLE,
            AdsFeed::COL_CARD_5_TITLE,
        ];
        foreach ($cardTitleFeedFields as $cardTitleFeedField) {
            if (isset($new[$cardTitleFeedField])) {
                $card['title'] = $new[$cardTitleFeedField];
            }
        }

        $cardLinkFeedFields = [
            AdsFeed::COL_CARD_1_LINK_URL,
            AdsFeed::COL_CARD_2_LINK_URL,
            AdsFeed::COL_CARD_3_LINK_URL,
            AdsFeed::COL_CARD_4_LINK_URL,
            AdsFeed::COL_CARD_5_LINK_URL,
        ];
        foreach ($cardLinkFeedFields as $cardLinkFeedField) {
            if (isset($new[$cardLinkFeedField])) {
                $card['link'] = $new[$cardLinkFeedField];
            }
        }

        return $card;
    }

    private function getPostFields(ExportOperation $operation): array
    {
        $new = $operation->state_to;
        $current = $operation->state_from;
        $post = [
            'owner_id' => $current[AdsFeed::COL_POST_OWNER_ID],
            'post_id'  => $current[AdsFeed::COL_POST_ID]
        ];

        if ($current[AdsFeed::COL_POST_TEXT]) {
            $post['message'] = $current[AdsFeed::COL_POST_TEXT];
        }
        if ($current[AdsFeed::COL_POST_ATTACHMENT_LINK_URL]) {
            $post['attachments'] = $current[AdsFeed::COL_POST_ATTACHMENT_LINK_URL];
        }
        if ($current[AdsFeed::COL_POST_ATTACHMENT_CARDS]) {
            $post['attachments'] = $current[AdsFeed::COL_POST_ATTACHMENT_CARDS];
        }
        if ($current[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE]) {
            $post['link_title'] = $current[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE];
        }
        if ($current[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID]) {
            $post['link_video'] = "{$current[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID]}_{$current[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID]}";
        }

        // В ВК разделены понятия типа кнопки и текста кнопки, однако при загрузке
        // принимается лишь один параметр link_button - строковая константа из списка,
        // по которой, в совокупности с тем, куда ведет ссылка кнопки, будет определ и текст и тип.
        // Т.к. из API возвращается и тип и текст, а для обратной загрузки доступна только одна константа
        // - определяем ее по тексту и месту назначения ссылки
        if ($current[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_TITLE]) {
            $post['link_button'] = $this->getLinkButtonByTitle(
                $current[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_TITLE],
                $current[AdsFeed::COL_POST_ATTACHMENT_LINK_URL]
            );
        }
        if ($current[AdsFeed::COL_POST_LINK_IMAGE]) {
            $post['link_image'] = $current[AdsFeed::COL_POST_LINK_IMAGE];
        }

        // поля ниже - редактируемые
        if (isset($new[AdsFeed::COL_POST_TEXT])) {
            $post['message'] = $new[AdsFeed::COL_POST_TEXT];
        }
        if (isset($new[AdsFeed::COL_POST_ATTACHMENT_LINK_URL])) {
            $post['attachments'] = $new[AdsFeed::COL_POST_ATTACHMENT_LINK_URL];
        }
        if (isset($new[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE])) {
            $post['link_title'] = $new[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE];
        }
        if (isset($new[AdsFeed::COL_POST_LINK_IMAGE])) {
            $post['link_image'] = $new[AdsFeed::COL_POST_LINK_IMAGE];
        }
        if (isset($new[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID])) {
            $post['link_video'] = "{$current[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID]}_{$new[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID]}";
        }
        if (isset($new[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_TITLE])) {
            $post['link_button'] = $this->getLinkButtonByTitle(
                $current[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_TITLE],
                $new[AdsFeed::COL_POST_ATTACHMENT_LINK_URL]
            );
        }

        return $post;
    }

    /*
     * Преобразование возвращенного из API заголовка кнопки в соответственное значение, пригодное для обратной загрузки
     * @see https://vk.com/dev/wall.postAdsStealth
     */
    private function getLinkButtonByTitle(string $title, string $link): string
    {
        $buttons = [
            'Запустить'            => 'app_join',
            'Перейти'              => 'open_url',
            'Открыть'              => 'open',
            'Подробнее'            => 'more',
            'Позвонить'            => 'call',
            'Забронировать'        => 'book',
            'Записаться'           => 'enroll',
            'Зарегистрироваться'   => 'register',
            'Купить'               => 'buy',
            'Купить билет'         => 'buy_ticket',
            'Заказать'             => 'order',
            'Создать'              => 'create',
            'Установить'           => 'install',
            'Заполнить'            => 'fill',
            'Подписаться'          => 'join_public',
            'Я пойду'              => 'join_event',
            'Вступить'             => 'join',
            'Связаться'            => 'im',                 // Сообщества, публичные страницы, события
            //  'Связаться'        => 'contact',            // Внешние сайты
            'Начать'               => 'begin',
            'Получить'             => 'get',
            'Смотреть'             => 'watch',
            'Скачать'              => 'download',
            'Участвовать'          => 'participate',
            'Играть'               => 'app_game_join',       // Игры
            // 'Играть'            => 'play',                // Внешние сайты
            'Подать заявку'        => 'apply',
            'Получить предложение' => 'get_an_offer',
            'Написать'             => 'im2',                 // Сообщества, публичные страницы, события
            //  'Написать'         => 'to_write',            // Внешние сайты
            'Откликнуться'         => 'reply',
        ];

        if (!isset($buttons[$title])) {
            throw new \UnexpectedValueException("Unexpected button title '{$title}'");
        }

        // обработка конфликтов названий кнопок для разных типов ссылок
        $isExternalURL = strpos($link, 'vk.') !== false;

        // конфликтующие значение для внешних сайтов
        $conflicts = [
            'Связаться' => 'contact',
            'Играть'    => 'play',
            'Написать'  => 'to_write',
        ];
        if (in_array($title, $conflicts) && $isExternalURL) {
            return $conflicts[$title];
        }

        return $buttons[$title];
    }

    private function fetchCaptchaSid(string $captcha): ?string
    {
        if ($captcha && strpos($captcha, 'sid=') !== false) {
            $query = [];
            parse_str(parse_url($captcha, PHP_URL_QUERY), $query);

            return $query['sid'];
        }

        return null;
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
            case AdsFeed::COL_POST_ATTACHMENT_CARDS:
                $cards = $ad['post']['attachments'][0]['pretty_cards']['cards'] ?? [];
                return $cards ? 'pretty_card'.implode(',pretty_card', array_column($cards, 'card_id')) : null;
            case AdsFeed::COL_CLIENT_ID:
                return $this->clientId;
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

    /**
     * @see https://vk.com/dev/upload_photo_ads
     * @param Collection $operations
     * @return Collection
     */
    private function uploadImages(Collection $operations): Collection
    {
        /** @var ExportOperation $op */
        foreach ($operations->where('type', ExportOperation::TYPE_UPDATE_AD) as $op) {
            try {
                $query = ['ad_format' => $op->state_from[AdsFeed::COL_AD_FORMAT]];
                if (isset($op->state_to[AdsFeed::COL_AD_PHOTO])) {
                    $op->runtime['photo_upload'] = $this->transferImage(
                        $op->state_to[AdsFeed::COL_AD_PHOTO],
                        $this->get('ads.getUploadURL', $query)
                    );
                }
                if (isset($op->state_to[AdsFeed::COL_AD_ICON])) {
                    $op->runtime['icon_upload'] = $this->transferImage(
                        $op->state_to[AdsFeed::COL_AD_ICON],
                        $this->get('ads.getUploadURL', $query + ['icon' => '1'])
                    );
                }
            } catch (\Exception $e) {
                $op->status = ExportOperation::STATUS_FAILED;
                $op->error = $e->getMessage();
            }
        }

        return $operations;
    }

    private function transferImage(string $urlFrom, string $urlTo)
    {
        $img = file_get_contents($urlFrom);
        if ($img === false) {
            throw new \Exception("Не удалось получить файл изображения ({$urlFrom})");
        }

        $ext = pathinfo($urlFrom)['extension'] ?? null;
        if (is_null($ext)) {
            throw new \Exception("Не удалось определить тип изображения ({$urlFrom})");
        }
        $filename = tempnam(sys_get_temp_dir(), 'asdark_img').'.'.$ext;
        $written = file_put_contents($filename, $img);
        if ($written === false) {
            throw new \Exception("Не удалось загрузить файл изображения ({$urlFrom})");
        }

        $client = new Client(['timeout'  => 60.0]);
        try {
            $response = $client->post($urlTo, [
                'multipart' => [
                    [
                        'name'         => 'file',
                        'contents'     => fopen($filename, 'r'),
                    ]
                ]
            ]);
            $data = \json_decode($response->getBody()->getContents(), true);
            if (!is_array($data)) {
                throw new \Exception("Не удалось загрузить изображение в ВК ({$urlFrom}): ошибка ВК");
            }
            $err = $data['errcode'] ?? null;
            if (!is_null($err)) {
                throw new \Exception("Не удалось загрузить изображение в ВК ({$urlFrom}): ".json_encode($data));
            }
            $photo = $data['photo'] ?? null;
            if (is_null($photo)) {
                throw new \Exception("Не удалось загрузить изображение в ВК ({$urlFrom}): нет данных");
            }

            return $photo;
        } finally {
            unlink($filename);
        }
    }

}
