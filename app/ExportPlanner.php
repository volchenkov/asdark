<?php
//
//namespace App;
//
//
//use App\Vk\AdsFeed;
//use App\Vk\ApiClient;
//use function App\Vk\;
//
//class ExportPlanner
//{
//
//    private ApiClient $vk;
//
//    public function __construct()
//    {
//        $this->vk = new ApiClient();
//    }
//
//    public function plan(array $incompleteAdsFeed)
//    {
//        $adIds = array_keys($incompleteAdsFeed);
//        $currentStateFeed = $this->vk->getFeed($adIds, array_keys(AdsFeed::FIELDS));
//
//
//    }
//
//    private function diff(array $currentStateFeed, array $newStateFeed)
//    {
//        $commands = [];
//        foreach ($newStateFeed as $ad) {
//            $adId = $ad[AdsFeed::COL_AD_ID];
//            $currentAd = $currentStateFeed[$adId];
//
//            $needUpdate = function ($field) use ($ad, $currentAd) {
//                return array_key_exists($field, $ad) && $ad[$field] != $currentAd[$field];
//            };
//
//            // поля объявления, которые нужно обновить
//            $u = [];
//            if ($needUpdate(AdsFeed::COL_AD_NAME)) {
//                $u['name'] = $ad[AdsFeed::COL_AD_NAME];
//            }
//            if ($needUpdate(AdsFeed::COL_AD_LINK_URL)) {
//                $u['link_url'] = $ad[AdsFeed::COL_AD_LINK_URL];
//            }
//            if ($needUpdate(AdsFeed::COL_AD_TITLE)) {
//                $u['title'] = $ad[AdsFeed::COL_AD_TITLE];
//            }
//            if ($needUpdate(AdsFeed::COL_AD_DESCRIPTION)) {
//                $u['description'] = $ad[AdsFeed::COL_AD_DESCRIPTION];
//            }
//            if ($needUpdate(AdsFeed::COL_AD_LINK_TITLE)) {
//                $u['link_title'] = $ad[AdsFeed::COL_AD_LINK_TITLE];
//            }
//            if ($u) {
//                $commands[] = [
//                    'type' => 'updateAd',
//                    'item' => array_replace(['ad_id' => $adId], $u)
//                ];
//            }
//
//            // поля поста, которые нужно обновить
//            $p = [];
//            if ($needUpdate(AdsFeed::COL_POST_TEXT)) {
//                $p['message'] = $ad[AdsFeed::COL_POST_TEXT];
//            }
//            if ($needUpdate(AdsFeed::COL_POST_ATTACHMENT_LINK_URL)) {
//                $p['attachments'] = $ad[AdsFeed::COL_POST_ATTACHMENT_LINK_URL];
//            }
//            if ($needUpdate(AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE)) {
//                $p['link_title'] = $ad[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE];
//            }
//            if ($needUpdate(AdsFeed::COL_POST_LINK_IMAGE)) {
//                $p['link_image'] = $ad[AdsFeed::COL_POST_LINK_IMAGE];
//            }
//            if ($needUpdate(AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID)) {
//                $p['link_video'] = "{$currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID]}_{$ad[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID]}";
//            }
//
//            if ($p) {
//                $fields = [
//                    'owner_id' => $currentAd[AdsFeed::COL_POST_OWNER_ID],
//                    'post_id'  => $currentAd[AdsFeed::COL_POST_ID]
//                ];
//                if ($currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID]) {
//                    $fields['link_video'] = "{$currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID]}_{$currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID]}";
//                }
//                if ($currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE]) {
//                    $fields['link_title'] = $currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE];
//                }
//                // В ВК разделены понятия типа кнопки и текста кнопки, однако при загрузке
//                // принимается лишь один параметр link_button - строковая константа из списка,
//                // по которой, в совокупности с тем, куда ведет ссылка кнопки, будет определ и текст и тип.
//                // Т.к. из API возвращается и тип и текст, а для обратной загрузки доступна только одна константа
//                // - определяем ее по тексту и месту назначения ссылки
//                if ($currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_TITLE]) {
//                    $fields['link_button'] = $this->getLinkButtonByTitle(
//                        $currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_BUTTON_TITLE],
//                        $p['attachments'] ?? $currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_URL]
//                    );
//                }
//                if ($currentAd[AdsFeed::COL_POST_LINK_IMAGE]) {
//                    $fields['link_image'] = $currentAd[AdsFeed::COL_POST_LINK_IMAGE];
//                }
//                if ($currentAd[AdsFeed::COL_POST_TEXT]) {
//                    $fields['message'] = $currentAd[AdsFeed::COL_POST_TEXT];
//                }
//                if ($currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_URL]) {
//                    $fields['attachments'] = $currentAd[AdsFeed::COL_POST_ATTACHMENT_LINK_URL];
//                }
//                $commands[] = [
//                    'type' => 'updatePost',
//                    'adId' => $adId,
//                    'item' => array_replace($fields, $p),
//                ];
//            }
//        }
//
//        return $commands;
//    }
//
//    /*
//     * Преобразование возвращенного из API заголовка кнопки в соответственное значение, пригодное для обратной загрузки
//     * @see https://vk.com/dev/wall.postAdsStealth
//     */
//    private function getLinkButtonByTitle(string $title, string $link): string
//    {
//        $buttons = [
//            'Запустить'            => 'app_join',
//            'Перейти'              => 'open_url',
//            'Открыть'              => 'open',
//            'Подробнее'            => 'more',
//            'Позвонить'            => 'call',
//            'Забронировать'        => 'book',
//            'Записаться'           => 'enroll',
//            'Зарегистрироваться'   => 'register',
//            'Купить'               => 'buy',
//            'Купить билет'         => 'buy_ticket',
//            'Заказать'             => 'order',
//            'Создать'              => 'create',
//            'Установить'           => 'install',
//            'Заполнить'            => 'fill',
//            'Подписаться'          => 'join_public',
//            'Я пойду'              => 'join_event',
//            'Вступить'             => 'join',
//            'Связаться'            => 'im',                 // Сообщества, публичные страницы, события
//            //  'Связаться'        => 'contact',            // Внешние сайты
//            'Начать'               => 'begin',
//            'Получить'             => 'get',
//            'Смотреть'             => 'watch',
//            'Скачать'              => 'download',
//            'Участвовать'          => 'participate',
//            'Играть'               => 'app_game_join',       // Игры
//            // 'Играть'            => 'play',                // Внешние сайты
//            'Подать заявку'        => 'apply',
//            'Получить предложение' => 'get_an_offer',
//            'Написать'             => 'im2',                 // Сообщества, публичные страницы, события
//            //  'Написать'         => 'to_write',            // Внешние сайты
//            'Откликнуться'         => 'reply',
//        ];
//
//        if (!isset($buttons[$title])) {
//            throw new \UnexpectedValueException("Unexpected button title '{$title}'");
//        }
//
//        // обработка конфликтов названий кнопок для разных типов ссылок
//        $isExternalURL = strpos($link, 'vk.') !== false;
//
//        // конфликтующие значение для внешних сайтов
//        $conflicts = [
//            'Связаться' => 'contact',
//            'Играть'    => 'play',
//            'Написать'  => 'to_write',
//        ];
//        if (in_array($title, $conflicts) && $isExternalURL) {
//            return $conflicts[$title];
//        }
//
//        return $buttons[$title];
//    }
//}
