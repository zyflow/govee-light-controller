<?php

namespace App\Console\Commands;

use App\Http\Controllers\GoveeController;
use Illuminate\Console\Command;

class SetSunset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'govee:set-sunset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set sunset in DB';

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
        $govee->setSunetTime();
        \Log::info('Set sunset');
        return 0;
    }
}
