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

    protected function getPackageProviders($app)
    {
        $app['config']['nexus'] = [
            'model_repository' => ModelRepo::class,
            'sites' => [
                'foo' => [

                ],
                'bar' => [
                    'routes' => [
                        CustomRouteGroup::class
                    ]
                ]
            ],
            'directories' => [
                'routes' => __DIR__ . DIRECTORY_SEPARATOR . 'Environment'
            ]
        ];

        return [NexusServiceProvider::class];
    }
}
