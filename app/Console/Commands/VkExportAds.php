<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \App\Vk\ApiClient as VkApiClient;
use \App\Vk\AdsFeed;
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
 * day_limit
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
            error_log('Failed export ads: ' . $e->getMessage());
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
        $feed = $this->google->getCells($spreadsheetId, $sheetTitle);

        if (count($feed) == 0) {
            $this->line('No ads to export');
            return 0;
        }

        // add result columns if not exists
        $headers = array_keys(array_replace($feed[0], $this->makeAdResults('whatever')));
        $this->google->writeCells($spreadsheetId, $sheetTitle . '!1:1', [$headers]);

        $errors = $this->vk->updateAds($feed);

        $a1cols = GoogleApiClient::getA1Cols($headers);

        $result = [];
        foreach ($feed as $item) {
            $error = $errors[$item[AdsFeed::COL_AD_ID]] ?? '';
            $result[] = [$error ? 'failed' : 'done', $error];
        }

        $range = "{$sheetTitle}!{$a1cols['asdark:export_status']}2:{$a1cols['asdark:export_error']}".(1+count($feed));
        $this->google->writeCells($spreadsheetId, $range , $result);

        return count($errors);
    }

    private function makeAdResults(string $status, ?int $adId = null, ?string $error = null)
    {
        return [
            AdsFeed::COL_AD_ID     => $adId ?: '',
            'asdark:export_status' => $status,
            'asdark:export_error'  => (string)$error
        ];
    }

}
