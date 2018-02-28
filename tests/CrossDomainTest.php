<?php

namespace Tests;

class CrossDomainTest extends TestCase
{
    protected function setUp()
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
            ->assertSee($this->url('bar', 'auth/internal'))
            ->assertDontSee($this->url('foo', 'auth/internal'))
        ;
    }
}
