<?php

namespace Sztyup\Nexus\Tests;

class ResourcesTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setupNexus();
        $this->loadNexus();
    }

    public function testFiles()
    {
        $this->fileResponseTest(
            $this->app->storagePath() . '/assets/foo/fonts/foo.woff',
            $this->url('foo', '/fonts/foo.woff')
        );

        $this->fileResponseTest(
            $this->app->resourcePath('foo/img/foo.jpg'),
            $this->url('foo', '/img/foo.jpg')
        );

        $this->fileResponseTest(
            $this->app->basePath('storage/assets/foo/img/foo.jpg'),
            $this->url('foo', '/assets/img/foo.jpg')
        );

        $this->fileResponseTest(
            $this->app->basePath('storage/assets/foo/css/app.css'),
            $this->url('foo', '/css/app.css')
        );

        $this->fileResponseTest(
            $this->app->basePath('storage/assets/foo/js/app.js'),
            $this->url('foo', '/js/app.js')
        );
    }

    public function test404()
    {
        $this->get($this->url('/css/app.css'))
            ->assertStatus(404)
        ;

        $this->get($this->url('/js/app.js'))
            ->assertStatus(404)
        ;

        $this->get($this->url('/img/foo.jpg'))
            ->assertStatus(404)
        ;
    }
}
