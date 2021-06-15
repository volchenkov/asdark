<?php


namespace App\Vk;

/**
 * Отражение многоуровневой вложенной структуры объявлений из вк в двумерную, удобную для представления в таблице
 */
class AdsFeed
{

    const COL_CAMPAIGN_ID = 'campaign_id';
    const COL_CAMPAIGN_NAME = 'campaign_name';
    const COL_AD_ID = 'ad_id';
    const COL_AD_NAME = 'ad_name';
    const COL_AD_TITLE = 'ad_title';
    const COL_AD_DESCRIPTION = 'ad_description';
    const COL_AD_LINK_TITLE = 'ad_link_title';
    const COL_AD_FORMAT = 'ad_format';
    const COL_AUTOBIDDING = 'ad_autobidding';
    const COL_GOAL_TYPE = 'goal_type';
    const COL_COST_TYPE = 'cost_type';
    const COL_AD_OCPM = 'ocpm';
    const COL_AD_CATEGORY1 = 'category1_id';
    const COL_AD_DAY_LIMIT = 'day_limit';
    const COL_POST_TEXT = 'post_text';
    const COL_POST_LINK_IMAGE = 'post_link_image';
    const COL_POST_OWNER_ID = 'post_owner_id';
    const COL_POST_ATTACHMENT_LINK_URL = 'post_attachment_link_url';
    const COL_POST_ATTACHMENT_LINK_TITLE = 'post_attachment_link_title';
    const COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE = 'post_attachment_link_button_action_type';
    const COL_POST_ATTACHMENT_LINK_BUTTON_TITLE = 'post_attachment_link_button_action_title';
    const COL_POST_ID = 'post_id';
    const COL_AD_LINK_URL = 'ad_link_url';
    const COL_AD_TARGETING_COUNTRY = 'targeting_country';
    const COL_AD_TARGETING_CITIES = 'targeting_cities';
    const COL_POST_ATTACHMENT_LINK_VIDEO_ID = 'post_attachments_link_video_id';
    const COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID = 'post_attachments_link_video_owner_id';
    const COL_POST_ATTACHMENT_CARDS = 'post_attachments_cards';
    const COL_CARD_1_TITLE = 'card_1_title';
    const COL_CARD_1_LINK_URL = 'card_1_link_url';
    const COL_CARD_1_OWNER_ID = 'card_1_owner_id';
    const COL_CARD_1_ID = 'card_1_id';
    const COL_CARD_1_PHOTO = 'card_1_photo';
    const COL_CARD_2_TITLE = 'card_2_title';
    const COL_CARD_2_LINK_URL = 'card_2_link_url';
    const COL_CARD_2_OWNER_ID = 'card_2_owner_id';
    const COL_CARD_2_ID = 'card_2_id';
    const COL_CARD_2_PHOTO = 'card_2_photo';
    const COL_CARD_3_TITLE = 'card_3_title';
    const COL_CARD_3_LINK_URL = 'card_3_link_url';
    const COL_CARD_3_OWNER_ID = 'card_3_owner_id';
    const COL_CARD_3_ID = 'card_3_id';
    const COL_CARD_3_PHOTO = 'card_3_photo';
    const COL_CARD_4_TITLE = 'card_4_title';
    const COL_CARD_4_LINK_URL = 'card_4_link_url';
    const COL_CARD_4_OWNER_ID = 'card_4_owner_id';
    const COL_CARD_4_ID = 'card_4_id';
    const COL_CARD_4_PHOTO = 'card_4_photo';
    const COL_CARD_5_TITLE = 'card_5_title';
    const COL_CARD_5_LINK_URL = 'card_5_link_url';
    const COL_CARD_5_OWNER_ID = 'card_5_owner_id';
    const COL_CARD_5_ID = 'card_5_id';
    const COL_CARD_5_PHOTO = 'card_5_photo';
    const COL_AD_PHOTO = 'ad_photo';
    const COL_AD_ICON = 'ad_icon';
    const COL_STATS_URL = 'ad_stats_url';

    const CARDS_ENTITIES = [
        'card1',
        'card2',
        'card3',
        'card4',
        'card5',
    ];

    const FIELDS = [
        self::COL_CAMPAIGN_ID     => [
            'editable' => false,
            'entity'   => 'campaign',
            'desc'     => 'ID кампании'
        ],
        self::COL_CAMPAIGN_NAME         => [
            'editable' => false,
            'entity'   => 'campaign',
            'desc'     => 'Имя кампании'
        ],
        self::COL_AD_ID           => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'ID объявления'
        ],
        self::COL_AD_NAME                                 => [
            'editable' => true,
            'entity'   => 'ad',
            'desc'     => 'Имя объявления'
        ],
        self::COL_AD_TITLE                                => [
            'editable' => true,
            'entity'   => 'ad',
            'desc'     => 'Заголовок объявления'
        ],
        self::COL_AD_DESCRIPTION                          => [
            'editable' => true,
            'entity'   => 'ad',
            'desc'     => 'Описание объявления'
        ],
        self::COL_AD_LINK_TITLE                           => [
            'editable' => true,
            'entity'   => 'ad',
            'desc'     => 'Заголовок ссылки (рядом с кнопкой) объявления'
        ],
        self::COL_AD_LINK_URL                             => [
            'editable' => true,
            'entity'   => 'ad',
            'desc'     => 'Ссылка на рекламируемый объект'
        ],
        self::COL_AD_PHOTO => [
            'editable' => true,
            'entity'   => 'ad',
            'desc'     => 'Основное изображение объявления'
        ],
        self::COL_AD_ICON => [
            'editable' => true,
            'entity'   => 'ad',
            'desc'     => 'Логотип объявления'
        ],
        self::COL_POST_TEXT                               => [
            'editable' => true,
            'entity'   => 'post',
            'desc'     => 'Текст рекламного поста'
        ],
        self::COL_POST_LINK_IMAGE                         => [
            'editable' => true,
            'entity'   => 'post',
            'desc'     => 'Картинка рекламного поста'
        ],
        self::COL_POST_OWNER_ID                           => [
            'editable' => false,
            'entity'   => 'post',
            'desc'     => 'ID владельца поста, рекламируемой группы'
        ],
        self::COL_POST_ID                                 => [
            'editable' => false,
            'entity'   => 'post',
            'desc'     => 'ID рекламного поста внутри группы'
        ],
        self::COL_POST_ATTACHMENT_LINK_URL                => [
            'editable' => true,
            'entity'   => 'post',
            'desc'     => 'Ссылка в посте'
        ],
        self::COL_POST_ATTACHMENT_LINK_TITLE              => [
            'editable' => true,
            'entity'   => 'post',
            'desc'     => 'Заголовок поста'
        ],
        self::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE => [
            'editable' => false,
            'entity'   => 'post',
            'desc'     => 'Тип кнопки поста'
        ],
        self::COL_POST_ATTACHMENT_LINK_BUTTON_TITLE       => [
            'editable' => false,
            'entity'   => 'post',
            'desc'     => 'Текст кнопки поста'
        ],
        self::COL_AD_TARGETING_COUNTRY                    => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'ID страны таргетинга'
        ],
        self::COL_AD_TARGETING_CITIES                     => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'ID городов таргетинга через запятую'
        ],
        self::COL_AUTOBIDDING                             => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Автоуправление ценой (1 - включено, 0 - выключено)'
        ],
        self::COL_AD_FORMAT                               => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Формат объявления'
        ],
        self::COL_GOAL_TYPE                               => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Цель'
        ],
        self::COL_COST_TYPE                               => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Форма оплаты'
        ],
        self::COL_AD_OCPM                                 => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Оптимизированная цена за 1к показов, руб'
        ],
        self::COL_AD_CATEGORY1                            => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Категория объявления'
        ],
        self::COL_AD_DAY_LIMIT                        => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Дней лимит трат на объявление, руб'
        ],
        self::COL_POST_ATTACHMENT_LINK_VIDEO_ID       => [
            'editable' => true,
            'entity'   => 'post',
            'desc'     => 'ID видео поста'
        ],
        self::COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID => [
            'editable' => false,
            'entity'   => 'post',
            'desc'     => 'ID владельца (группы) видео поста'
        ],
        self::COL_CARD_1_TITLE    => [
            'editable' => true,
            'entity'   => 'card1',
            'desc'     => 'Заголовок первой карточки карусели'
        ],
        self::COL_CARD_1_LINK_URL => [
            'editable' => true,
            'entity'   => 'card1',
            'desc'     => 'Ссылка первой карточки карусели'
        ],
        self::COL_CARD_1_OWNER_ID => [
            'editable' => false,
            'entity'   => 'card1',
            'desc'     => 'ID владельца первой карточки карусели'
        ],
        self::COL_CARD_1_ID       => [
            'editable' => false,
            'entity'   => 'card1',
            'desc'     => 'ID первой карточки карусели'
        ],
        self::COL_CARD_1_PHOTO    => [
            'editable' => true,
            'entity'   => 'card1',
            'desc'     => 'Картинка первой карточки карусели'
        ],
        self::COL_CARD_2_TITLE    => [
            'editable' => true,
            'entity'   => 'card2',
            'desc'     => 'Заголовок второй карточки карусели'
        ],
        self::COL_CARD_2_LINK_URL => [
            'editable' => true,
            'entity'   => 'card2',
            'desc'     => 'Ссылка второй карточки карусели'
        ],
        self::COL_CARD_2_OWNER_ID => [
            'editable' => false,
            'entity'   => 'card2',
            'desc'     => 'ID владельца второй карточки карусели'
        ],
        self::COL_CARD_2_ID       => [
            'editable' => false,
            'entity'   => 'card2',
            'desc'     => 'ID второй карточки карусели'
        ],
        self::COL_CARD_2_PHOTO    => [
            'editable' => true,
            'entity'   => 'card2',
            'desc'     => 'Картинка второй карточки карусели'
        ],
        self::COL_CARD_3_TITLE    => [
            'editable' => true,
            'entity'   => 'card3',
            'desc'     => 'Заголовок третьей карточки карусели'
        ],
        self::COL_CARD_3_LINK_URL => [
            'editable' => true,
            'entity'   => 'card3',
            'desc'     => 'Ссылка третьей карточки карусели'
        ],
        self::COL_CARD_3_OWNER_ID => [
            'editable' => false,
            'entity'   => 'card3',
            'desc'     => 'ID владельца третьей карточки карусели'
        ],
        self::COL_CARD_3_ID       => [
            'editable' => false,
            'entity'   => 'card3',
            'desc'     => 'ID третьей карточки карусели'
        ],
        self::COL_CARD_3_PHOTO    => [
            'editable' => true,
            'entity'   => 'card3',
            'desc'     => 'Картинка третьей карточки карусели'
        ],
        self::COL_CARD_4_TITLE    => [
            'editable' => true,
            'entity'   => 'card4',
            'desc'     => 'Заголовок четвертой карточки карусели'
        ],
        self::COL_CARD_4_LINK_URL => [
            'editable' => true,
            'entity'   => 'card4',
            'desc'     => 'Ссылка четвертой карточки карусели'
        ],
        self::COL_CARD_4_OWNER_ID => [
            'editable' => false,
            'entity'   => 'card4',
            'desc'     => 'ID владельца четвертой карточки карусели'
        ],
        self::COL_CARD_4_ID       => [
            'editable' => false,
            'entity'   => 'card4',
            'desc'     => 'ID четвертой карточки карусели'
        ],
        self::COL_CARD_4_PHOTO    => [
            'editable' => true,
            'entity'   => 'card4',
            'desc'     => 'Картинка четвертой карточки карусели'
        ],
        self::COL_CARD_5_TITLE    => [
            'editable' => true,
            'entity'   => 'card5',
            'desc'     => 'Заголовок пятой карточки карусели'
        ],
        self::COL_CARD_5_LINK_URL => [
            'editable' => true,
            'entity'   => 'card5',
            'desc'     => 'Ссылка пятой карточки карусели'
        ],
        self::COL_CARD_5_OWNER_ID       => [
            'editable' => false,
            'entity'   => 'card5',
            'desc'     => 'ID владельца пятой карточки карусели'
        ],
        self::COL_CARD_5_ID             => [
            'editable' => false,
            'entity'   => 'card5',
            'desc'     => 'ID пятой карточки карусели'
        ],
        self::COL_CARD_5_PHOTO          => [
            'editable' => true,
            'entity'   => 'card5',
            'desc'     => 'Картинка пятой карточки карусели'
        ],
        self::COL_POST_ATTACHMENT_CARDS => [
            'editable' => false,
            'entity'   => 'post',
            'desc'     => 'Список ID карточек карусели через запятую'
        ],
        self::COL_STATS_URL => [
            'editable' => true,
            'entity'   => 'ad',
            'desc'     => 'Пиксель внешней статистики'
        ],
    ];

    public static function dependsOn(string $entity, array $fields): bool
    {
        return count(array_intersect($fields, array_keys(self::getEntityFields($entity)))) > 0;
    }

    public static function getEntityFields(string $entity): array
    {
        return array_filter(self::FIELDS, fn ($field) => $field['entity'] === $entity);
    }

    public static function getEditableFields(string $entity = null): array
    {
        $fields = $entity ? self::getEntityFields($entity) : self::FIELDS;

        return array_filter($fields, fn ($field) => $field['editable']);
    }

}
