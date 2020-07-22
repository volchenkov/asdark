<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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

    protected $fillable = [
        'type',
        'ad_id',
        'export_id',
        'state_from',
        'state_to',
        'status'
    ];

    protected $casts = [
        'state_from' => 'array',
        'state_to'   => 'array',
    ];

    protected $table = 'export_operations';

}
