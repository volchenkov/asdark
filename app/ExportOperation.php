<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Операция атомарного изменения объявления или его части
 *
 * @property string $type
 * @property integer $ad_id
 * @property integer $export_id
 * @property array $state_from
 * @preperty array $state_to
 * @property string $status
 * @property string $error
 * @property array $runtime
 */
class ExportOperation extends Model
{

    const STATUS_DONE = 'done';
    const STATUS_FAILED = 'failed';
    const STATUS_ABORTED = 'aborted';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PENDING = 'pending';

    const TYPE_UPDATE_AD = 'update_ad';
    const TYPE_UPDATE_POST = 'update_post';
    const TYPE_UPDATE_CARD = 'update_card';

    const RNT_CARD_1_PHOTO_UPLOAD = 'card_1_photo_upload';
    const RNT_CARD_2_PHOTO_UPLOAD = 'card_2_photo_upload';
    const RNT_CARD_3_PHOTO_UPLOAD = 'card_3_photo_upload';
    const RNT_CARD_4_PHOTO_UPLOAD = 'card_4_photo_upload';
    const RNT_CARD_5_PHOTO_UPLOAD = 'card_5_photo_upload';

    protected $fillable = [
        'type',
        'ad_id',
        'export_id',
        'state_from',
        'state_to',
        'status',
        'error'
    ];

    /**
     * Контейнер для временных данных времени выполнения
     * @var array
     */
    public array $runtime = [];

    protected $casts = [
        'state_from' => 'array',
        'state_to'   => 'array',
    ];

    protected $table = 'export_operations';

}
