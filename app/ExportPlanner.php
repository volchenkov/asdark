<?php

namespace App;

use App\Vk\AdsFeed;
use Illuminate\Support\Collection;

class ExportPlanner
{

    /**
     * @param array $currentStateFeed INDEXED by ad ID
     * @param array $newStateFeed
     * @return Collection<ExportOperation>
     */
    public function plan(array $currentStateFeed, array $newStateFeed): Collection
    {
        $operations = new Collection();
        foreach ($newStateFeed as $ad) {
            $adId = $ad[AdsFeed::COL_AD_ID];
            $currentAd = $currentStateFeed[$adId];

            $needUpdate = function ($field) use ($ad, $currentAd) {
                return array_key_exists($field, $ad) && $ad[$field] != $currentAd[$field];
            };

            $editableAdFields = array_keys(AdsFeed::getEditableFields('ad'));
            // поля объявления, которые нужно обновить
            $newAdState = [];
            foreach ($editableAdFields as $field) {
                if ($needUpdate($field)) {
                    $newAdState[$field] = $ad[$field];
                }
            }
            if ($newAdState) {
                $operations->push(new ExportOperation([
                    'type'       => ExportOperation::TYPE_UPDATE_AD,
                    'ad_id'      => $adId,
                    'state_from' => $currentAd,
                    'state_to'   => $newAdState,
                    'status'     => ExportOperation::STATUS_PENDING
                ]));
            }

            $editablePostFields = array_keys(AdsFeed::getEditableFields('post'));
            // поля поста, которые нужно обновить
            $newPostState = [];
            foreach ($editablePostFields as $field) {
                if ($needUpdate($field)) {
                    $newPostState[$field] = $ad[$field];
                }
            }
            if ($newPostState) {
                $operations->push(new ExportOperation([
                    'type'       => ExportOperation::TYPE_UPDATE_POST,
                    'ad_id'      => $adId,
                    'state_from' => $currentAd,
                    'state_to'   => $newPostState,
                    'status'     => ExportOperation::STATUS_PENDING
                ]));
            }

            foreach (AdsFeed::CARDS_ENTITIES as $entity) {
                $newCardState = [];
                foreach (array_keys(AdsFeed::getEditableFields($entity)) as $field) {
                    if ($needUpdate($field)) {
                        $newCardState[$field] = $ad[$field];
                    }
                }
                if ($newCardState) {
                    $operations->push(new ExportOperation([
                        'type'       => ExportOperation::TYPE_UPDATE_CARD,
                        'ad_id'      => $adId,
                        'state_from' => $currentAd,
                        'state_to'   => $newCardState,
                        'status'     => ExportOperation::STATUS_PENDING
                    ]));
                }
            }
        }

        return $operations;
    }

}
