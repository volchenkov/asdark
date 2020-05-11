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

    private GoogleApiClient $google;
    private VkApiClient $vk;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->google = new GoogleApiClient();
        $this->vk = VkApiClient::instance();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $operation = $this->google->getPendingOperation();
        if (!$operation) {
            $this->line('Nothing to do');
            return;
        }
        try {
            $this->google->updateOperationStatus($operation['id'], 'processing');
            $errors = $this->exportAds($operation);
            $this->google->updateOperationStatus($operation['id'], $errors ? 'done_with_errors' : 'done');
        } catch (\Throwable $e) {
            $this->google->updateOperationStatus($operation['id'], 'failed', $e->getMessage());
        }

        return;
    }

    private function exportAds(array $operation): int
    {
        $spreadsheetId = $operation['spreadsheetId'] ?? null;
        if (!$spreadsheetId) {
            throw new \RuntimeException('Spreadsheet ID is undefined');
        }

        $sheetTitle = 'Sheet1';
        $ads = $this->google->getCells($spreadsheetId, $sheetTitle);

        if (count($ads) == 0) {
            $this->line('No ads to export');
            return 0;
        }

        // add result columns if not exists
        $headers = array_keys(array_replace($ads[0], $this->makeAdResults('whatever')));
        $this->google->writeCells($spreadsheetId, $sheetTitle . '!1:1', [$headers]);

        $errors = 0;
        foreach ($ads as $i => $data) {
            $createdAlready = ($data['asdark:export_status'] ?? null) === 'created';
            if ($createdAlready) {
                continue;
            }

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


            try {
                $adId = $this->vk->createAd($ad);
                $status = 'created';
                $error = null;
            } catch (\Exception $e) {
                $adId = null;
                $status = 'failed';
                $error = $e->getMessage();

                $errors++;
                error_log('Failed to handle ad row: ' . $e->getMessage());
            } finally {
                $result = array_replace($data, $this->makeAdResults($status, $adId, $error));
                try {
                    $row = $i + 2;
                    $range = "{$sheetTitle}!{$row}:{$row}";
                    $this->google->writeCells($spreadsheetId, $range , [array_values($result)]);
                } catch (\Exception $e) {
                    error_log('Failed to update ad row: ' . $e->getMessage());
                }
            }
        }

        return $errors;
    }

    private function makeAdResults(string $status, ?int $adId = null, ?string $error = null)
    {
        return [
            'ad_id'                => (string)$adId,
            'asdark:export_status' => $status,
            'asdark:export_error'  => (string)$error
        ];
    }

}
