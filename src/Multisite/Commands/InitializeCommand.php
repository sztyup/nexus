<?php

namespace Sztyup\Multisite\Commands;

use Illuminate\Console\Command;

class InitializeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multisite:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize multisite environment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Multisite Initialization');
    }

}