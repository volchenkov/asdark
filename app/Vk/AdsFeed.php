<?php


namespace App\Vk;

class AdsFeed
{

    const COL_CAMPAIGN_ID = 'campaign_id';
    const COL_CAMPAIGN_NAME = 'campaign_name';
    const COL_AD_ID = 'ad_id';
    const COL_AD_NAME = 'ad_name';
    const COL_POST_TEXT = 'post_text';
    const COL_POST_LINK_IMAGE = 'post_link_image';
    const COL_POST_OWNER_ID = 'post_owner_id';
    const COL_POST_ATTACHMENT_LINK_URL = 'post_attachment_link_url';
    const COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE = 'post_attachment_link_button_action_type';
    const COL_POST_ID = 'post_id';
    const COL_AD_LINK_URL = 'ad_link_url';

    const FIELDS = [
        self::COL_CAMPAIGN_ID                             => [
            'editable' => false,
            'desc'     => 'ID кампании'
        ],
        self::COL_CAMPAIGN_NAME                           => [
            'editable' => false,
            'desc'     => 'Имя кампании'
        ],
        self::COL_AD_ID                                   => [
            'editable' => false,
            'desc'     => 'ID объявления'
        ],
        self::COL_AD_NAME                                 => [
            'editable' => false,
            'desc'     => 'Имя объявления'
        ],
        self::COL_AD_LINK_URL                             => [
            'editable' => false,
            'desc'     => 'Ссылка на рекламируемый объект в формате'
        ],
        self::COL_POST_TEXT                               => [
            'editable' => true,
            'desc'     => 'Текст рекламного поста'
        ],
        self::COL_POST_LINK_IMAGE                         => [
            'editable' => true,
            'desc'     => 'Картинка рекламного поста'
        ],
        self::COL_POST_OWNER_ID                           => [
            'editable' => false,
            'desc'     => 'ID владельца поста, рекламируемой группы'
        ],
        self::COL_POST_ID                                 => [
            'editable' => false,
            'desc'     => 'ID рекламного поста внутри группы'
        ],
        self::COL_POST_ATTACHMENT_LINK_URL                => [
            'editable' => false,
            'desc'     => 'Ссылка на форму заявок'
        ],
        self::COL_POST_ATTACHMENT_LINK_BUTTON_ACTION_TYPE => [
            'editable' => false,
            'desc'     => 'Текст кнопки пост'
        ],
    ];

}
