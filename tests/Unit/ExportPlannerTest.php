<?php

namespace Tests\Unit;

use App\ExportOperation;
use App\ExportPlanner;
use App\Vk\AdsFeed;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;

class ExportPlannerTest extends TestCase
{

    public function testEmptyResultOnEmptyFeeds(): void
    {
        /** @var ExportPlanner $planner */
        $planner = resolve(ExportPlanner::class);

        $this->assertEquals(new Collection(), $planner->plan([], []));
    }

    public function testPlanOnAdFieldsEdition(): void
    {
        /** @var ExportPlanner $planner */
        $planner = resolve(ExportPlanner::class);
        $currentStateFeed = $this->getFakeCurrentStateFeed();

        $editedFieldsAd11 = [
            AdsFeed::COL_AD_DESCRIPTION => "description_edited",
            AdsFeed::COL_AD_LINK_TITLE  => "ad_link_title_edited",
        ];

        $newStateAd13 = [
            AdsFeed::COL_AD_NAME => "ad_name_edited",
        ];
        $newStateFeed = [
            array_replace($currentStateFeed[11], $editedFieldsAd11),
            array_replace($currentStateFeed[13], $newStateAd13)
        ];

        $expectedOperations = new Collection();
        $expectedOperations
            ->push(new ExportOperation([
                'type'       => ExportOperation::TYPE_UPDATE_AD,
                'ad_id'      => 11,
                'state_from' => $currentStateFeed[11],
                'state_to'   => $editedFieldsAd11,
                'status'     => ExportOperation::STATUS_PENDING
            ]))
            ->push(new ExportOperation([
                'type'       => ExportOperation::TYPE_UPDATE_AD,
                'ad_id'      => 13,
                'state_from' => $currentStateFeed[13],
                'state_to'   => $newStateAd13,
                'status'     => ExportOperation::STATUS_PENDING
            ]));
        $this->assertEquals($expectedOperations, $planner->plan($currentStateFeed, $newStateFeed));
    }

    public function testPlanOnPostFieldsEdition(): void
    {
        /** @var ExportPlanner $planner */
        $planner = resolve(ExportPlanner::class);

        $currentStateFeed = $this->getFakeCurrentStateFeed();
        $editableFields = array_keys(AdsFeed::getEditableFields('post'));

        $editedFieldsAd11 = [
            AdsFeed::COL_POST_ATTACHMENT_LINK_URL   => "url_value_edited",
            AdsFeed::COL_POST_ATTACHMENT_LINK_TITLE => "title_value_edited",
        ];

        $newStateAd13 = [
            $editableFields[0] => "{$editableFields[0]}_value_edited",
        ];
        $newStateFeed = [
            array_replace($currentStateFeed[11], $editedFieldsAd11),
            array_replace($currentStateFeed[13], $newStateAd13)
        ];

        $expectedOperations = new Collection();
        $expectedOperations
            ->push(new ExportOperation([
                'type'       => ExportOperation::TYPE_UPDATE_POST,
                'ad_id'      => 11,
                'state_from' => $currentStateFeed[11],
                'state_to'   => $editedFieldsAd11,
                'status'     => ExportOperation::STATUS_PENDING
            ]))
            ->push(new ExportOperation([
                'type'       => ExportOperation::TYPE_UPDATE_POST,
                'ad_id'      => 13,
                'state_from' => $currentStateFeed[13],
                'state_to'   => $newStateAd13,
                'status'     => ExportOperation::STATUS_PENDING
            ]));
        $this->assertEquals($expectedOperations, $planner->plan($currentStateFeed, $newStateFeed));
    }

    public function testPlanOnCardFieldsEdition(): void
    {
        /** @var ExportPlanner $planner */
        $planner = resolve(ExportPlanner::class);

        $currentStateFeed = $this->getFakeCurrentStateFeed();

        $editedFieldsAd11 = [
            AdsFeed::COL_CARD_2_LINK_URL => "card_2_link_url_edited",
            AdsFeed::COL_CARD_3_TITLE    => "card_3_title_edited",
        ];

        $editedStateAd13 = [
            AdsFeed::COL_CARD_5_LINK_URL => "card_5_link_url_edited",
            AdsFeed::COL_CARD_1_TITLE    => "card_1_title_edited",
        ];
        $newStateFeed = [
            array_replace($currentStateFeed[11], $editedFieldsAd11),
            array_replace($currentStateFeed[13], $editedStateAd13)
        ];

        $expectedOperations = new Collection();
        $expectedOperations
            ->push(new ExportOperation([
                'type'       => ExportOperation::TYPE_UPDATE_CARD,
                'ad_id'      => 11,
                'state_from' => $currentStateFeed[11],
                'state_to'   => [AdsFeed::COL_CARD_2_LINK_URL => 'card_2_link_url_edited'],
                'status'     => ExportOperation::STATUS_PENDING
            ]))
            ->push(new ExportOperation([
                'type'       => ExportOperation::TYPE_UPDATE_CARD,
                'ad_id'      => 11,
                'state_from' => $currentStateFeed[11],
                'state_to'   => [AdsFeed::COL_CARD_3_TITLE => 'card_3_title_edited'],
                'status'     => ExportOperation::STATUS_PENDING
            ]))
            ->push(new ExportOperation([
                'type'       => ExportOperation::TYPE_UPDATE_CARD,
                'ad_id'      => 13,
                'state_from' => $currentStateFeed[13],
                'state_to'   => [AdsFeed::COL_CARD_1_TITLE => "card_1_title_edited"],
                'status'     => ExportOperation::STATUS_PENDING
            ]))
            ->push(new ExportOperation([
                'type'       => ExportOperation::TYPE_UPDATE_CARD,
                'ad_id'      => 13,
                'state_from' => $currentStateFeed[13],
                'state_to'   => [AdsFeed::COL_CARD_5_LINK_URL => "card_5_link_url_edited"],
                'status'     => ExportOperation::STATUS_PENDING
            ]));
        $this->assertEquals($expectedOperations, $planner->plan($currentStateFeed, $newStateFeed));
    }


    private function getFakeCurrentStateFeed(): array
    {
        $editableFields = array_keys(AdsFeed::getEditableFields());

        $currentStateFeed = [];
        foreach (range(10, 15) as $adId) {
            $feedItem = [AdsFeed::COL_AD_ID => $adId];
            foreach ($editableFields as $field) {
                $feedItem[$field] = "{$field}_value";
            }
            $currentStateFeed[$adId] = $feedItem;
        }

        return $currentStateFeed;
    }

}
