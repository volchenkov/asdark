<?php

namespace App\Console\Commands;

use App\Google\ApiClient;
use Illuminate\Console\Command;

class CreatePrivateSheet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:create-private-sheet {title : Название нового документа}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $rows = [];
        $permission = new \Google_Service_Drive_Permission([
            'role'         => 'writer',
            'type'         => 'user',
            'emailAddress' => getenv('ADMIN_EMAIL')
        ]);
        $spreadsheet = (new ApiClient())->createSpreadSheet($this->argument('title'), $rows, $permission);

        $this->line("Created spreadsheet id: {$spreadsheet->getSpreadsheetId()}");
    }
}
