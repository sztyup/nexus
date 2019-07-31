<?php

namespace Sztyup\Nexus\Traits;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Sztyup\Nexus\SiteManager;

trait NexusTestHelper
{
    public function url($slug, $uri = '/', $number = 1)
    {
        if (!$this->app->bound(SiteManager::class)) {
            return null;
        }

        /** @var SiteManager $manager */
        $manager = $this->app->make(SiteManager::class);

        $site = $manager->getBySlug($slug);

        if ($site === null) {
            return null;
        }

        if (!Str::startsWith($uri, '/')) {
            $uri = '/' . $uri;
        }

        return 'http://' . $site->getPrimaryDomain() . $uri;
    }

    public function fileResponseTest($path, $url)
    {
        $filesystem = \Mockery::mock(Filesystem::class)->makePartial();
        $responseFactory = \Mockery::mock(ResponseFactory::class)->makePartial();

        $this->app->bind(Filesystem::class, function () use ($filesystem) {
            return $filesystem;
        });

        $this->app->bind(ResponseFactory::class, function () use ($responseFactory) {
            return $responseFactory;
        });

        $filesystem
            ->shouldReceive('exists')
            ->withArgs([$path])
            ->andReturn(true);

        $responseFactory
            ->shouldReceive('file')
            ->withArgs([$path])
            ->andReturn(new Response($testContent = Str::random(), 200, ['Content-type' => 'application/octet-stream']));

        $this->get($url)
            ->assertSuccessful()
            ->assertSee($testContent)
        ;
    }
}
