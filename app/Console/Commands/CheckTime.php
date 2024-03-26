<?php

namespace App\Console\Commands;

use App\Http\Controllers\GoveeController;
use Illuminate\Console\Command;

class CheckTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'govee:check-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check time and turn on Govee lights';

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
        $govee = new GoveeController();
        $govee->checkLights();
        \Log::info('Govee lights checking');
        return 0;
    }
}
