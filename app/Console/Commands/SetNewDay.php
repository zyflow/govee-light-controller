<?php

namespace App\Console\Commands;

use App\Http\Controllers\SheetController;
use Illuminate\Console\Command;

class SetNewDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'govee:set-new-day';

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
     * @return int
     */
    public function handle()
    {
        $contr = new SheetController();
        $contr->setExecuted("FALSE");
        return 0;
    }
}
