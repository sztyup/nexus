<?php

namespace Tests;

class RoutingTest extends TestCase
{
    public function testGetRoutes()
    {
        $this->get('http://foo.com/foo/lol')
            ->assertSuccessful()
            ->assertSee('lol')
            ->assertDontSee('asd')
        ;

        $this->get('http://foo.com/foo/asd')
            ->assertSuccessful()
            ->assertSee('asd')
            ->assertDontSee('lol')
        ;
    }

    public function testPostRoutesDenied()
    {
        $this->post('http://foo.com/foo/lol')->assertStatus(405);
    }

    public function testCommonRouteGroup()
    {
        $this->get('http://bar.com/custom')
            ->assertSuccessful()
            ->assertSee('custom.bar')
        ;
    }
}
