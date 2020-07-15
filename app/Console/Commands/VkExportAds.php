<?php

namespace App\Console\Commands;

use App\Export;
use App\ExportLog;
use App\ExportOperation;
use App\ExportPlanner;
use App\Vk\CaptchaException;
use Illuminate\Console\Command;
use \App\Vk\ApiClient as VkApiClient;
use \App\Vk\AdsFeed;
use \App\Google\ApiClient as GoogleApiClient;
use Illuminate\Support\Collection;

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

            $this->log($export->id, 'Загрузка началась');
            $feed = $this->getFeed($export->sid);

            $status = Export::STATUS_DONE;
            $failure = null;
            if (count($feed) > 0) {
                $this->log($export->id, 'Составляется план загрузки');
                $planner = new ExportPlanner($this->vk, $export->id);
                $planner->plan($feed);

                $this->executePlan($export);

                $fails = ExportOperation::where('export_id', $export->id)
                    ->where('status', 'failed')
                    ->get()
                    ->count();
                if ($fails > 0) {
                    $status = Export::STATUS_PARTIAL_FAILURE;
                }
            } else {
                $this->log($export->id, 'Файл загрузки пуст');
            }
            $this->log($export->id, 'Загрузка завершилась');
        } catch (CaptchaException $e) {
            $this->log($export->id, "Обновление прервано, для продолжения нужна капча");
            $status = Export::STATUS_INTERRUPTED;
            $failure = null;
            $export->captcha = $e->img;
            $export->captcha_code = null;
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
    }

    private function getFeed($spreadsheetId)
    {
        $feed = $this->google->getCells($spreadsheetId, self::FEED_SHEET_TITLE);

        if (count($feed) == 0) {
            return [];
        }

        $headers = array_keys($feed[0]);
        if (!in_array(AdsFeed::COL_AD_ID, $headers)) {
            throw new \RuntimeException(sprintf("Feed column '%s' required for ads update", AdsFeed::COL_AD_ID));
        }

        return $feed;
    }

    /**
     * @param Export $export
     * @throws CaptchaException
     * @throws \Exception
     */
    private function executePlan(Export $export)
    {
        /** @var Collection $operations */
        $operations = ExportOperation::where('export_id', $export->id)->where('status', 'pending')->get();

        $captcha = $export->captcha;
        $captchaCode = $export->captcha_code;

        $remaining = $operations->count();
        // обновляем по 5 за раз из-за ограничений API
        // см https://vk.com/dev/ads.updateAds
        /** @var Collection $chunk */
        foreach ($operations->groupBy('ad_id')->chunk(5) as $chunk) {
            $chunk = $chunk->collapse();
            $this->log($export->id, sprintf(
                "Обновляются объявления %s",
                implode(', ', $chunk->pluck('ad_id')->unique()->values()->all()
            )));
            try {
                foreach ($chunk as $operation) {
                    $operation->status = ExportOperation::STATUS_PROCESSING;
                    $operation->save();
                }
                $this->vk->batchUpdate($chunk, $captcha, $captchaCode);
                foreach ($chunk as $operation) {
                    $operation->save();
                }

                $remaining -= $chunk->count();
                if ($remaining) {
                    $captcha = null;
                    $captchaCode = null;
                    $sleep = random_int(60, 80);
                    $this->log($export->id, "Осталость {$remaining} объявлений. Ждем {$sleep} секунд из-за капчи.");
                    sleep($sleep);
                }
            } catch (\Exception $e) {
                foreach ($chunk as $operation) {
                    $operation->status = ExportOperation::STATUS_ABORTED;
                    $operation->error = $e->getMessage();
                    $operation->save();
                }

                throw $e;
            }
        }
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
