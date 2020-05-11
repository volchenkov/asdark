<?php

namespace App\Console\Commands;

use App\Vk\Ad;
use App\Vk\AdTargeting;
use App\Vk\WallPostStealth;
use Illuminate\Console\Command;
use \App\Vk\ApiClient as VkApiClient;
use \App\Google\ApiClient as GoogleApiClient;

/**
 * Expected feed fields:
 *
 * ad_format,
 * campaign_id,
 * ad_name,
 * ad_autobidding,
 * goal_type,
 * cost_type,
 * ocpm,
 * cpm,
 * cpc,
 * category1_id,
 * targeting_cities,
 * targeting_country,
 * post_link_button,
 * post_link_image,
 * post_message,
 * post_attachments,
 * post_owner_id
 */
class VkExportAds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vk:export-ads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export ads sheet to VK';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $google = new GoogleApiClient();
        $operation = $google->getPendingOperation();
        if (!$operation) {
            $this->line('Nothing to do');
            return;
        }
        $google->updateOperationStatus($operation['id'], 'processing');

        $spreadsheetId = $operation['spreadsheetId'] ?? null;
        if (!$spreadsheetId) {
            throw new \RuntimeException('Spreadsheet ID is undefined');
        }

        $feed = $google->getCells($spreadsheetId, 'Sheet1');

        $vk = VkApiClient::instance();

        foreach ($feed as $data) {
            try {
                $adTargeting = new AdTargeting();
                $adTargeting->country = $data['targeting_country'];
                $adTargeting->cities = explode(',', $data['targeting_cities']);

                $post = new WallPostStealth($data['post_owner_id']);
                $post->linkButton = $data['post_link_button'];
                $post->linkImage = $data['post_link_image'];
                $post->message = $data['post_message'];
                $post->guid = uniqid('stealth_post');
                $post->attachments = explode(',', $data['post_attachments']);

                $ad = new Ad($data['ad_format'], $data['campaign_id']);
                $ad->name = $data['ad_name'];
                $ad->autobidding = (int)$data['ad_autobidding'];
                $ad->goalType = $data['goal_type'];
                $ad->costType = $data['cost_type'];
                $ad->ocpm = $data['ocpm'];
                $ad->category1Id = $data['category1_id'];

                $ad->targeting = $adTargeting;
                $ad->post = $post;

                $ad->vkId = $vk->createAd($ad);
                echo "{$ad->vkId}\n";
            } catch (\Exception $e) {
                error_log('Failed to handle feed row: '.$e->getMessage());
            }
        }

        $google->updateOperationStatus($operation['id'], 'done');

        return;
    }

}
