<?php

namespace App;

class ExportLogger
{

    private int $exportId;

    public function __construct(int $exportId)
    {
        $this->exportId = $exportId;
    }

    public function info(string $message)
    {
        $this->log($message, ExportLog::LEVEL_INFO);
    }

    public function notice(string $message)
    {
        $this->log($message, ExportLog::LEVEL_NOTICE);
    }

    public function warning(string $message)
    {
        $this->log($message, ExportLog::LEVEL_WARNING);
    }

    public function error(string $message)
    {
        $this->log($message, ExportLog::LEVEL_ERROR);
    }

    private function log(string $message, string $level)
    {
        $log = new ExportLog();
        $log->level = $level;
        $log->message = $message;
        $log->export_id = $this->exportId;

        try {
            $log->save();
        } catch (\Exception $e) {
            error_log('Failed to write log: ' . $e->getMessage());
        }
    }
}
