<?php

namespace Sztyup\Nexus\Tests;

use Sztyup\Nexus\Tests\Environment\EmptyModelRepo;

class MainTest extends TestCase
{
    protected function setUp(): void
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

        $this->get('foo.com')
            ->assertStatus(404)
        ;
    }
}
