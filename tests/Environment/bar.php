<?php

use Sztyup\Nexus\CommonRouteGroup;
use Illuminate\Contracts\Routing\Registrar;
/** @var Registrar $router */
/** @var CommonRouteGroup[] $commonRegistrars */

$router->get('/', function () {
    return response('lol');
})->name('index');

$commonRegistrars[\Sztyup\Nexus\Tests\Environment\CustomRouteGroup::class]->register();