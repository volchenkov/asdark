<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{

    const STATUS_DONE = 'done';
    const STATUS_PARTIAL_FAILURE = 'done_with_errors';
    const STATUS_INTERRUPTED = 'interrupted';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PENDING = 'pending';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELED = 'canceled';

    protected $table = 'exports';


    /**
     * Get the post that owns the comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
