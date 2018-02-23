<?php

namespace Tests;

use Tests\Environment\EmptyModelRepo;

class MainTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setupNexus();

        $this->app['config']['nexus.model_repository'] = EmptyModelRepo::class;

        $this->loadNexus();
    }

    public function testMain()
    {
        $this->get('http://fallback.com')
            ->assertSuccessful()
            ->assertSee('main')
        ;

        $this->get($this->url('foo'))
            ->assertStatus(404)
        ;
    }
}
