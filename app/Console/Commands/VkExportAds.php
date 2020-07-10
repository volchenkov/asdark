<?php

namespace App\Console\Commands;

use App\Export;
use App\ExportLog;
use App\Vk\CaptchaException;
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
        $export = Export::where('status', Export::STATUS_PENDING)->first();
        if (!$export) {
            $this->line('Nothing to do');
            return;
        }

        try {
            $export->status = Export::STATUS_PROCESSING;
            $export->save();
            $fails = $this->exportAds($export);
            if (is_null($fails)) {
                $status = Export::STATUS_INTERRUPTED;
            } elseif ($fails > 0) {
                $status = Export::STATUS_PARTIAL_FAILURE;
            } else {
                $status = Export::STATUS_DONE;
            }
            $failure = null;
            $this->log($export->id, 'Загрузка завершилась');
        } catch (\Throwable $e) {
            $this->log($export->id, "Обновление прервано из-за ошибки: {$e->getMessage()}", ExportLog::LEVEL_ERROR);
            $status = Export::STATUS_FAILED;
            $failure = $e->getMessage();
        } finally {
            $export->status = $status;
            $export->failure = $failure;
            $export->finish_time = new \DateTime('now');

            $export->save();
        }

        return;
    }

    private function exportAds(Export $export): ?int
    {
        $this->log($export->id, 'Загрузка началась');
        $remoteFeed = $this->google->getCells($export->sid, self::FEED_SHEET_TITLE);

        if (count($remoteFeed) == 0) {
            $this->log($export->id, 'Файл загрузки пуст');
            return 0;
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
            $this->log($export->id, 'Нет объявлений для обновления');

            return 0;
        }

        $currentStateFeed = $this->vk->getFeed(array_keys($incompleteAds), array_keys(AdsFeed::FIELDS));

        $fails = 0;
        $remaining = count($incompleteAds);
        foreach (array_chunk($incompleteAds, 5, true) as $chunk) {
            $this->log($export->id, "Обновляются объявления ".implode(', ', array_column($chunk, AdsFeed::COL_AD_ID)));
            try {
                $errors = $this->vk->updateAds($chunk, $currentStateFeed);

                foreach ($errors as $adId => $error) {
                    $adResult = [
                        AdsFeed::COL_ADK_STATUS       => 'done',
                        AdsFeed::COL_ADK_ERR          => '',
                        AdsFeed::COL_ADK_CAPTCHA      => '',
                        AdsFeed::COL_ADK_CAPTCHA_CODE => ''
                    ];
                    if (!is_null($error)) {
                        $fails++;
                        $adResult = [
                            AdsFeed::COL_ADK_STATUS => 'failed',
                            AdsFeed::COL_ADK_ERR    => $error,
                        ];
                    }
                    $feed[$adId] = array_replace($feed[$adId], $adResult);
                }

                $remaining -= count($chunk);
                if ($remaining) {
                    $sleep = random_int(60, 80);
                    $this->log($export->id, "Осталость {$remaining} объявлений. Ждем {$sleep} секунд из-за капчи.");
                    sleep($sleep);
                }
            } catch (CaptchaException $e) {
                $this->log($export->id, 'Выполнение прервано из-за капчи', ExportLog::LEVEL_WARNING);
                foreach ($chunk as $adId => $_) {
                    $feed[$adId] = array_replace($feed[$adId], [
                        AdsFeed::COL_ADK_STATUS       => 'failed',
                        AdsFeed::COL_ADK_ERR          => "Для продолжения нужна капча",
                        AdsFeed::COL_ADK_CAPTCHA      => $e->img,
                        AdsFeed::COL_ADK_CAPTCHA_CODE => ''
                    ]);

                    /** @TODO refactor this: хотфикс для заполнения фида, в случае капчи */
                    $feed = array_values($feed);
                    $headers = array_keys($feed[0]);
                    $this->google->writeCells($export->sid, self::FEED_SHEET_TITLE . '!1:1', [$headers]);

                    $A1 = GoogleApiClient::getA1Cols($headers);

                    $result = [];
                    foreach ($feed as $row) {
                        $result[] = array_intersect_key($row, array_fill_keys($adkCols, null));
                    }
                    $range = self::FEED_SHEET_TITLE . '!' . "{$A1[$adkCols[0]]}2:{$A1[$adkCols[count($adkCols) - 1]]}" . (1 + count($result));
                    $this->google->writeCells($export->sid, $range, $result);

                    return null;
                }
            } catch (\Exception $e) {
                $this->log($export->id, 'Во время обновления произошла ошибка', ExportLog::LEVEL_WARNING);
                foreach ($chunk as $adId => $_) {
                    $fails++;
                    $feed[$adId] = array_replace($feed[$adId], [
                        AdsFeed::COL_ADK_STATUS       => 'failed',
                        AdsFeed::COL_ADK_ERR          => "Не удалось обновить объявление: {$e->getMessage()}",
                        AdsFeed::COL_ADK_CAPTCHA      => '',
                        AdsFeed::COL_ADK_CAPTCHA_CODE => ''
                    ]);
                }
            }
        }
        $feed = array_values($feed);

        $headers = array_keys($feed[0]);
        $this->google->writeCells($export->sid, self::FEED_SHEET_TITLE . '!1:1', [$headers]);

        $A1 = GoogleApiClient::getA1Cols($headers);

        $result = [];
        foreach ($feed as $row) {
            $result[] = array_intersect_key($row, array_fill_keys($adkCols, null));
        }
        $range = self::FEED_SHEET_TITLE . '!' . "{$A1[$adkCols[0]]}2:{$A1[$adkCols[count($adkCols) - 1]]}" . (1 + count($result));
        $this->google->writeCells($export->sid, $range, $result);

        return $fails;
    }

    private function log(int $exportId, string $message, string $level = ExportLog::LEVEL_INFO)
    {
        $log = new ExportLog();
        $log->level = $level;
        $log->message = $message;
        $log->export_id = $exportId;

        try {
            $log->save();
        } catch (\Exception $e) {
            error_log('Failed to write log: ' . $e->getMessage());
        }
    }

}
