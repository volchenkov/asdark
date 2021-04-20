<?php

namespace App\Vk;

use App\ExportOperation;
use \GuzzleHttp\Client;
use Illuminate\Support\Collection;

/**
 *
 */
class AdsUploader
{

    private ApiClient $vk;

    public function __construct(ApiClient $vkApiClient)
    {
        $this->vk = $vkApiClient;
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
        $rsp = $this->vk->execute($code, $captcha, $captchaKey);

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
        $code = "var a = '{$this->vk->getConnection()->getAccountId()}';\n";
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
        if (isset($newAd[AdsFeed::COL_STATS_URL])) {
            $ad['stats_url'] = $newAd[AdsFeed::COL_STATS_URL];
        }

        return $ad;
    }

    private function getCardFields(ExportOperation $operation): array
    {
        $new = $operation->state_to;
        $current = $operation->state_from;

        $ownerId = $cardId = $cardNum = null;
        foreach (array_keys($new) as $field) {
            switch (AdsFeed::FIELDS[$field]['entity']) {
                case 'card1':
                    $ownerId = $current[AdsFeed::COL_CARD_1_OWNER_ID];
                    $cardId = $current[AdsFeed::COL_CARD_1_ID];
                    $cardNum = 1;
                    break;
                case 'card2':
                    $ownerId = $current[AdsFeed::COL_CARD_2_OWNER_ID];
                    $cardId = $current[AdsFeed::COL_CARD_2_ID];
                    $cardNum = 2;
                    break;
                case 'card3':
                    $ownerId = $current[AdsFeed::COL_CARD_3_OWNER_ID];
                    $cardId = $current[AdsFeed::COL_CARD_3_ID];
                    $cardNum = 3;
                    break;
                case 'card4':
                    $ownerId = $current[AdsFeed::COL_CARD_4_OWNER_ID];
                    $cardId = $current[AdsFeed::COL_CARD_4_ID];
                    $cardNum = 4;
                    break;
                case 'card5':
                    $ownerId = $current[AdsFeed::COL_CARD_5_OWNER_ID];
                    $cardId = $current[AdsFeed::COL_CARD_5_ID];
                    $cardNum = 5;
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
        $titleField = $cardTitleFeedFields[$cardNum - 1];
        if (isset($new[$titleField])) {
            $card['title'] = $new[$titleField];
        }

        $cardLinkFeedFields = [
            AdsFeed::COL_CARD_1_LINK_URL,
            AdsFeed::COL_CARD_2_LINK_URL,
            AdsFeed::COL_CARD_3_LINK_URL,
            AdsFeed::COL_CARD_4_LINK_URL,
            AdsFeed::COL_CARD_5_LINK_URL,
        ];
        $linkField = $cardLinkFeedFields[$cardNum - 1];
        if (isset($new[$linkField])) {
            $card['link'] = $new[$linkField];
        }

        $cardPhotoFeedFields = [
            AdsFeed::COL_CARD_1_PHOTO => ExportOperation::RNT_CARD_1_PHOTO_UPLOAD,
            AdsFeed::COL_CARD_2_PHOTO => ExportOperation::RNT_CARD_2_PHOTO_UPLOAD,
            AdsFeed::COL_CARD_3_PHOTO => ExportOperation::RNT_CARD_3_PHOTO_UPLOAD,
            AdsFeed::COL_CARD_4_PHOTO => ExportOperation::RNT_CARD_4_PHOTO_UPLOAD,
            AdsFeed::COL_CARD_5_PHOTO => ExportOperation::RNT_CARD_5_PHOTO_UPLOAD,
        ];
        $photoField = array_keys($cardPhotoFeedFields)[$cardNum - 1];
        if (isset($new[$photoField])) {
            $card['photo'] = $operation->runtime[$cardPhotoFeedFields[$photoField]];
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
                        $this->vk->get('ads.getUploadURL', $query)
                    );
                }
                if (isset($op->state_to[AdsFeed::COL_AD_ICON])) {
                    $op->runtime['icon_upload'] = $this->transferImage(
                        $op->state_to[AdsFeed::COL_AD_ICON],
                        $this->vk->get('ads.getUploadURL', $query + ['icon' => '1'])
                    );
                }
            } catch (\Exception $e) {
                $op->status = ExportOperation::STATUS_FAILED;
                $op->error = $e->getMessage();
            }
        }

        /** @var ExportOperation $op */
        foreach ($operations->where('type', ExportOperation::TYPE_UPDATE_CARD) as $op) {
            try {
                if (isset($op->state_to[AdsFeed::COL_CARD_1_PHOTO])) {
                    $op->runtime[ExportOperation::RNT_CARD_1_PHOTO_UPLOAD] = $this->transferImage(
                        $op->state_to[AdsFeed::COL_CARD_1_PHOTO],
                        $this->vk->get('prettyCards.getUploadURL')
                    );
                }
                if (isset($op->state_to[AdsFeed::COL_CARD_2_PHOTO])) {
                    $op->runtime[ExportOperation::RNT_CARD_2_PHOTO_UPLOAD] = $this->transferImage(
                        $op->state_to[AdsFeed::COL_CARD_2_PHOTO],
                        $this->vk->get('prettyCards.getUploadURL')
                    );
                }
                if (isset($op->state_to[AdsFeed::COL_CARD_3_PHOTO])) {
                    $op->runtime[ExportOperation::RNT_CARD_3_PHOTO_UPLOAD] = $this->transferImage(
                        $op->state_to[AdsFeed::COL_CARD_3_PHOTO],
                        $this->vk->get('prettyCards.getUploadURL')
                    );
                }
                if (isset($op->state_to[AdsFeed::COL_CARD_4_PHOTO])) {
                    $op->runtime[ExportOperation::RNT_CARD_4_PHOTO_UPLOAD] = $this->transferImage(
                        $op->state_to[AdsFeed::COL_CARD_4_PHOTO],
                        $this->vk->get('prettyCards.getUploadURL')
                    );
                }
                if (isset($op->state_to[AdsFeed::COL_CARD_5_PHOTO])) {
                    $op->runtime[ExportOperation::RNT_CARD_5_PHOTO_UPLOAD] = $this->transferImage(
                        $op->state_to[AdsFeed::COL_CARD_5_PHOTO],
                        $this->vk->get('prettyCards.getUploadURL')
                    );
                }
            } catch (\Exception $e) {
                $op->status = ExportOperation::STATUS_FAILED;
                $op->error = $e->getMessage();
            }
        }

        return $operations;
    }

    /**
     * @param string $urlFrom
     * @param string $urlTo
     * @return mixed|null
     * @throws \Exception
     */
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
