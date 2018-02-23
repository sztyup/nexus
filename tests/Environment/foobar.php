<?php

$router->get('/', function () {
    return response('foobar');
})->name('foobar');
