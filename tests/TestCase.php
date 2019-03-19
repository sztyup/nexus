<?php

namespace Sztyup\Nexus\Tests;

use Orchestra\Testbench\TestCase as Base;
use Sztyup\Nexus\NexusServiceProvider;
use Sztyup\Nexus\Tests\Environment\CustomRouteGroup;
use Sztyup\Nexus\Tests\Environment\ModelRepo;
use Sztyup\Nexus\Traits\NexusTestHelper;

class TestCase extends Base
{
    use NexusTestHelper;

    protected function setUp(): void
    {
        $this->refreshApplication();
    }

    public function setupNexus()
    {
        $this->app['config']['nexus'] = [
            'main_domain' => 'fallback.com',
            'model_repository' => ModelRepo::class,
            'sites' => [
                'foo' => [

                ],
                'bar' => [
                    'routes' => [
                        CustomRouteGroup::class
                    ]
                ],
                'foobar' => [
                ]
            ],
            'directories' => [
                'routes' => __DIR__ . DIRECTORY_SEPARATOR . 'Environment',
                'resources' => resource_path(),
                'assets' => storage_path('assets')
            ]
        ];
    }

    public function loadNexus()
    {
        $this->app->registerDeferredProvider(NexusServiceProvider::class);
        $this->app->loadDeferredProvider(NexusServiceProvider::class);
    }
}
