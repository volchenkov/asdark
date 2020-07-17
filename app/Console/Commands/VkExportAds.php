<?php

namespace App\Console\Commands;

use App\Export;
use App\ExportLogger;
use App\ExportOperation;
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
    private ExportLogger $logger;

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
        $this->logger = new ExportLogger($export->id);

        // после запуска хотим обнулить капчу в любом случае кроме новой капчи
        $failure = $captcha = $captchaCode = null;
        try {
            $export->status = Export::STATUS_PROCESSING;
            $export->save();

            $this->logger->info('Выполнение началось');
            $feed = $this->getFeed($export->sid);

            $status = Export::STATUS_DONE;
            if (count($feed) == 0) {
                $this->logger->info('Файл загрузки пуст');
            } else {
                $this->vk->setClientId($feed[0][AdsFeed::COL_CLIENT_ID] ?? null);

                /** @var Collection $operations */
                $operations = ExportOperation::where('export_id', $export->id)
                    ->where('status', '!=', ExportOperation::STATUS_DONE)
                    ->get();

                $hasPlan = $operations->count() > 0;
                if (!$hasPlan) {
                    $this->logger->info('Составляется план загрузки');
                    $operations = $this->plan($feed, $export->id);
                }

                if ($operations->count() == 0) {
                    $this->logger->notice("Нет изменений для загрузки");
                } else {
                    $this->executeOperations($operations, $export->captcha, $export->captcha_code);

                    $fails = ExportOperation::where('export_id', $export->id)
                        ->where('status', ExportOperation::STATUS_FAILED)
                        ->count();

                    if ($fails > 0) {
                        $status = Export::STATUS_PARTIAL_FAILURE;
                    }
                }
            }
            $this->logger->info('Выполнение завершилось');
        } catch (CaptchaException $e) {
            $this->logger->warning("Обновление прервано, для продолжения нужна капча");
            $status = Export::STATUS_INTERRUPTED;
            $captcha = $e->img;
            $captchaCode = null;
        } catch (\Throwable $e) {
            $this->logger->error("Обновление прервано из-за ошибки: {$e->getMessage()}");
            $status = Export::STATUS_FAILED;
            $failure = $e->getMessage();
        } finally {
            $export->status = $status;
            $export->failure = $failure;
            $export->finish_time = new \DateTime('now');
            $export->captcha = $captcha;
            $export->captcha_code = $captchaCode;

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
     * @param Collection $operations
     * @param string|null $captcha
     * @param string|null $captchaCode
     * @throws CaptchaException
     * @throws \Exception
     */
    private function executeOperations(Collection $operations, ?string $captcha, ?string $captchaCode): void
    {
        $adsCount = $operations->pluck('ad_id')->unique()->count();
        $this->logger->info("К исполнению {$operations->count()} операций для {$adsCount} объявлений");

        $remaining = $operations->count();
        // обновляем по 5 за раз из-за ограничений API
        // см https://vk.com/dev/ads.updateAds
        /** @var Collection $chunk */
        foreach ($operations->sortBy('ad_id')->groupBy('ad_id')->chunk(5) as $chunk) {
            $chunk = $chunk->collapse();

            $adIds = $chunk->pluck('ad_id')->unique()->values()->all();
            $this->logger->info("Обновляются объявления: " . implode(', ', $adIds));
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
                    $sleep = random_int(60, 80);
                    $this->logger->info("Осталость {$remaining} операций. Ждем {$sleep} секунд из-за капчи.");
                    sleep($sleep);
                }
            } catch (\Exception $e) {
                foreach ($chunk as $operation) {
                    $operation->status = ExportOperation::STATUS_ABORTED;
                    $operation->error = $e->getMessage();
                    $operation->save();
                }

                throw $e;
            } finally {
                $captchaCode = $captcha = null;
            }
        }
    }

    private function plan(array $feed, int $exportId): Collection
    {
        $adIds = array_column($feed, AdsFeed::COL_AD_ID);
        $currentStateFeed = $this->vk->getFeed($adIds, array_keys(AdsFeed::FIELDS));

        $operations = new Collection();
        foreach ($this->diff($currentStateFeed, $feed) as $data) {
            $operation = new ExportOperation($data);
            $operation->export_id = $exportId;
            $operation->save();

            $operations->push($operation);
        }

        return $operations;
    }

    private function diff(array $currentStateFeed, array $newStateFeed): array
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
                    'type'       => ExportOperation::TYPE_UPDATE_AD,
                    'ad_id'      => $adId,
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
                    'type'       => ExportOperation::TYPE_UPDATE_POST,
                    'ad_id'      => $adId,
                    'state_from' => $currentAd,
                    'state_to'   => $newPostState,
                    'status'     => ExportOperation::STATUS_PENDING
                ];
            }
        }

        return $operations;
    }

}
