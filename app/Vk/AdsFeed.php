<?php


namespace App\Vk;

class AdsFeed
{

    const COL_CAMPAIGN_ID = 'campaign_id';
    const COL_CAMPAIGN_NAME = 'campaign_name';
    const COL_AD_ID = 'ad_id';
    const COL_AD_NAME = 'ad_name';
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
    const COL_POST_ID = 'post_id';
    const COL_AD_LINK_URL = 'ad_link_url';
    const COL_AD_TARGETING_COUNTRY = 'targeting_country';
    const COL_AD_TARGETING_CITIES = 'targeting_cities';
    const COL_POST_ATTACHMENT_LINK_VIDEO_ID = 'post_attachments_link_video_id';
    const COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID = 'post_attachments_link_video_owner_id';


    const FIELDS = [
        self::COL_CAMPAIGN_ID                             => [
            'editable' => false,
            'entity'   => 'campaign',
            'desc'     => 'ID кампании'
        ],
        self::COL_CAMPAIGN_NAME                           => [
            'editable' => false,
            'entity'   => 'campaign',
            'desc'     => 'Имя кампании'
        ],
        self::COL_AD_ID                                   => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'ID объявления'
        ],
        self::COL_AD_NAME                                 => [
            'editable' => true,
            'entity'   => 'ad',
            'desc'     => 'Имя объявления'
        ],
        self::COL_AD_LINK_URL                             => [
            'editable' => true,
            'entity'   => 'ad',
            'desc'     => 'Ссылка на рекламируемый объект'
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
        self::COL_POST_ATTACHMENT_LINK_TITLE                => [
            'editable' => false,
            'entity'   => 'post',
            'desc'     => 'Заголовок поста'
        ],
        self::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE => [
            'editable' => false,
            'entity'   => 'post',
            'desc'     => 'Текст кнопки пост'
        ],
        self::COL_AD_TARGETING_COUNTRY => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'ID страны таргетинга'
        ],
        self::COL_AD_TARGETING_CITIES => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'ID городов таргетинга через запятую'
        ],
        self::COL_AUTOBIDDING => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Автоуправление ценой (1 - включено, 0 - выключено)'
        ],
        self::COL_AD_FORMAT => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Формат объявления'
        ],
        self::COL_GOAL_TYPE => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Цель'
        ],
        self::COL_COST_TYPE => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Форма оплаты'
        ],
        self::COL_AD_OCPM => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Оптимизированная цена за 1к показов, руб'
        ],
        self::COL_AD_CATEGORY1 => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Категория объявления'
        ],
        self::COL_AD_DAY_LIMIT => [
            'editable' => false,
            'entity'   => 'ad',
            'desc'     => 'Дней лимит трат на объявление, руб'
        ],
        self::COL_POST_ATTACHMENT_LINK_VIDEO_ID => [
            'editable' => false,
            'entity'   => 'post',
            'desc'     => 'ID видео поста'
        ],
        self::COL_POST_ATTACHMENT_LINK_VIDEO_OWNER_ID => [
            'editable' => false,
            'entity'   => 'post',
            'desc'     => 'ID владельца (группы) видео поста'
        ]
    ];

    public static function dependsOn(string $entity, array $fields): bool
    {
        $entityFields = array_filter(self::FIELDS, fn ($field)  => $field['entity'] === $entity);

        return count(array_intersect($fields, array_keys($entityFields))) > 0;
    }

}
