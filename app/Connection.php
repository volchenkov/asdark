<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{

    protected $casts = [
        'data' => 'array',
    ];

    protected $table = 'connections';

    protected $fillable = ['system'];


    public function isAgency()
    {
        return $this->data['account_type'] === "agency";
    }

    public function hasAccountId(): bool
    {
        return isset($this->data['account_id']);
    }

    public function getAccountId()
    {
        return $this->data['account_id'];
    }

}
