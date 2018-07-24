<?php

use Illuminate\Contracts\Routing\Registrar;

/** @var Registrar $router */

$router->get('/', function () {
    return response('bar');
})->name('index');


$router->get('foo/lol', function () {
    return response('<body>lol</body>');
})->name('lol');

$router->get('foo/asd', function () {
    return response('asd');
})->name('asd');
