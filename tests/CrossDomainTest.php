<?php

namespace Sztyup\Nexus\Tests;

class CrossDomainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupNexus();
        $this->loadNexus();
    }

    public function testImageInjection()
    {
        $this->get($this->url('foo', 'foo/lol'))
            ->assertSuccessful()
            ->assertSee('<!-- Cross domain login handling -->')
            ->assertSee($this->url('bar', 'nexus/internal/auth'))
            ->assertDontSee($this->url('foo', 'nexus/internal/auth'))
        ;
    }
}
