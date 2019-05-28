<?php

namespace Sztyup\Nexus\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;

class InitializeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize multisite environment';

    /** @var  Filesystem */
    protected $filesystem;

    protected $config;

    public function __construct(Filesystem $filesystem, Repository $config)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
        $this->config = $config->get('nexus');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Nexus Initialization');

        // Save sites info for nodejs
        $this->filesystem->put(
            $this->config['directories']['assets'] . DIRECTORY_SEPARATOR . 'sites.json',
            $this->sitesToJson()
        );

        $this->info('Writing sites cache');
    }

    /**
     * Returns info about sites needed for nodejs in json
     *
     * @return string json
     */
    protected function sitesToJson()
    {
        $sites = [];

        foreach ($this->config['sites'] as $slug => $site) {
            $sites[] = [
                'slug' => Str::lower($slug),
                'title' => $site['title']
            ];
        }

        return json_encode([
            'sites' => $sites
        ]);
    }
}
