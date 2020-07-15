<?php

namespace App;


use App\Vk\AdsFeed;
use App\Vk\ApiClient;

class ExportPlanner
{

    private ApiClient $vk;
    private int $exportId;

    public function __construct(ApiClient $vk, int $exportId)
    {
        $this->vk = $vk;
        $this->exportId = $exportId;
    }

    public function plan(array $feed)
    {
        $adIds = array_column($feed, AdsFeed::COL_AD_ID);
        $currentStateFeed = $this->vk->getFeed($adIds, array_keys(AdsFeed::FIELDS));

        if ($clientId = $this->getClientId($feed)) {
            $this->vk->setClientId($clientId);
        }

        $operations = $this->diff($currentStateFeed, $feed);
        foreach ($operations as $data) {
            $operation = new ExportOperation($data);
            $operation->save();
        }

        return $operations;
    }

    private function diff(array $currentStateFeed, array $newStateFeed)
    {
        $operations = [];
        foreach ($newStateFeed as $ad) {
            $adId = $ad[AdsFeed::COL_AD_ID];
            $currentAd = $currentStateFeed[$adId];

            $needUpdate = function ($field) use ($ad, $currentAd) {
                return array_key_exists($field, $ad) && $ad[$field] != $currentAd[$field];
            };

            $editableAdFields = [
                AdsFeed::COL_AD_NAME,
                AdsFeed::COL_AD_LINK_URL,
                AdsFeed::COL_AD_TITLE,
                AdsFeed::COL_AD_DESCRIPTION,
                AdsFeed::COL_AD_LINK_TITLE,
            ];
            // поля объявления, которые нужно обновить
            $newAdState = [];
            foreach ($editableAdFields as $field) {
                if ($needUpdate($field)) {
                    $newAdState[$field] = $ad[$field];
                }
            }
            if ($newAdState) {
                $operations[] = [
                    'type'       => 'update_ad',
                    'ad_id'      => $adId,
                    'export_id'  => $this->exportId,
                    'state_from' => $currentAd,
                    'state_to'   => $newAdState,
                    'status'     => ExportOperation::STATUS_PENDING
                ];
            }

            $editablePostFields = [
                AdsFeed::COL_POST_TEXT,
                AdsFeed::COL_POST_ATTACHMENT_LINK_URL,
                AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE,
                AdsFeed::COL_POST_LINK_IMAGE,
                AdsFeed::COL_POST_ATTACHMENT_LINK_VIDEO_ID,
            ];
            // поля поста, которые нужно обновить
            $newPostState = [];
            foreach ($editablePostFields as $field) {
                if ($needUpdate($field)) {
                    $newPostState[$field] = $ad[$field];
                }
            }
            if ($newPostState) {
                $operations[] = [
                    'type'       => 'update_post',
                    'ad_id'      => $adId,
                    'export_id'  => $this->exportId,
                    'state_from' => $currentAd,
                    'state_to'   => $newPostState,
                    'status'     => ExportOperation::STATUS_PENDING
                ];
            }
        }

        return $operations;
    }

    private function getClientId(array $feed): ?int
    {
        return isset($feed[0]) && in_array(AdsFeed::COL_CLIENT_ID, array_keys($feed[0]))
            ? $feed[0][AdsFeed::COL_CLIENT_ID]
            : null;
    }
}
