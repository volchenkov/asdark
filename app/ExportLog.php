<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExportLog extends Model
{

    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

}
