<?php

namespace App\Console\Commands;

use App\Export;
use Illuminate\Console\Command;
use \App\Vk\ApiClient as VkApiClient;
use \App\Vk\AdsFeed;
use \App\Google\ApiClient as GoogleApiClient;

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

    const FEED_SHEET_TITLE = 'Sheet1';

    private GoogleApiClient $google;
    private VkApiClient $vk;

    const STATUS_TEXT = [
        VkApiClient::UPDATE_STATUS_DONE                => 'done',
        VkApiClient::UPDATE_STATUS_PARTIAL_FAILURE     => 'done_with_errors',
        VkApiClient::UPDATE_STATUS_PARTIAL_INTERRUPTED => 'interrupted',
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->google = new GoogleApiClient();
        $this->vk = new VkApiClient();
    }

    public function handle()
    {
        $export = Export::where('status', 'pending')->first();
        if (!$export) {
            $this->line('Nothing to do');
            return;
        }

        try {
            $export->status = 'processing';
            $export->save();
            $result = $this->exportAds($export->sid);
            $status = self::STATUS_TEXT[$result];
            $failure = null;
        } catch (\Throwable $e) {
            error_log('Failed export ads: ' . $e->getMessage());
            $status = 'failed';
            $failure = $e->getMessage();
        } finally {
            $export->status = $status;
            $export->failure = $failure;
            $export->finish_time = new \DateTime('now');

            $export->save();
        }

        return;
    }

    private function exportAds($spreadsheetId): int
    {
        $remoteFeed = $this->google->getCells($spreadsheetId, self::FEED_SHEET_TITLE);

        if (count($remoteFeed) == 0) {
            $this->line('Empty feed');
            return VkApiClient::UPDATE_STATUS_DONE;
        }

        $headers = array_keys($remoteFeed[0]);
        if (!in_array(AdsFeed::COL_AD_ID, $headers)) {
            throw new \RuntimeException(sprintf("Feed column '%s' required for ads update", AdsFeed::COL_AD_ID));
        }
        if (in_array(AdsFeed::COL_CLIENT_ID, $headers)) {
            $this->vk->setClientId($remoteFeed[0][AdsFeed::COL_CLIENT_ID]);
        }

        $adkCols = [
            AdsFeed::COL_ADK_STATUS,
            AdsFeed::COL_ADK_ERR,
            AdsFeed::COL_ADK_CAPTCHA,
            AdsFeed::COL_ADK_CAPTCHA_CODE
        ];

        $feed = [];
        foreach ($remoteFeed as $row) {
            foreach ($adkCols as $col) {
                $row[$col] = $row[$col] ?? null;
            }
            $feed[$row[AdsFeed::COL_AD_ID]] = $row;
        }

        $incompleteAds = array_filter($feed, fn ($i) => $i[AdsFeed::COL_ADK_STATUS] !== 'done');
        if (count($incompleteAds) === 0) {
            $this->line('No incompleted ads');
            return VkApiClient::UPDATE_STATUS_DONE;
        }
        list($status, $updatedFeed) = $this->vk->updateAds($incompleteAds);

        $feed = array_values(array_replace($feed, $updatedFeed));

        $headers = array_keys($feed[0]);
        $this->google->writeCells($spreadsheetId, self::FEED_SHEET_TITLE . '!1:1', [$headers]);

        $A1 = GoogleApiClient::getA1Cols($headers);

        $result = [];
        foreach ($feed as $row) {
            $result[] = array_intersect_key($row, array_fill_keys($adkCols, null));
        }
        $range = self::FEED_SHEET_TITLE . '!' . "{$A1[$adkCols[0]]}2:{$A1[$adkCols[count($adkCols) - 1]]}" . (1 + count($result));
        $this->google->writeCells($spreadsheetId, $range, $result);

        return $status;
    }

}
