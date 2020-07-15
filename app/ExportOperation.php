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
