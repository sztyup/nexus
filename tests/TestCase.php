<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Base;
use Sztyup\Nexus\NexusServiceProvider;
use Sztyup\Nexus\Traits\NexusTestHelper;
use Tests\Environment\CustomRouteGroup;
use Tests\Environment\ModelRepo;

class TestCase extends Base
{
    use NexusTestHelper;

    protected function setUp()
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
