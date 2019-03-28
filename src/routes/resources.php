<?php

/** @var Router $router */
use Illuminate\Routing\Router;

$router->get('assets/{path}', [
        'uses' => 'Sztyup\Nexus\Controllers\ResourceController@asset',
        'as' => 'resource.assets',
        'where' => ['path' => '.*']
]);

$router->get('storage/{path}', [
    'uses' => 'Sztyup\Nexus\Controllers\ResourceController@storage',
    'as' => 'resource.storage',
    'where' => ['path' => '.*']
]);

$router->get('fonts/{path}', [
    'uses' => 'Sztyup\Nexus\Controllers\ResourceController@fonts',
    'as' => 'resource.fonts',
    'where' => ['path' => '.*']
]);

$router->get('img/{path}', [
    'uses' => 'Sztyup\Nexus\Controllers\ResourceController@image',
    'as' => 'resource.img',
    'where' => ['path' => '.*']
]);

$router->get('js/{path}', [
        'uses' => 'Sztyup\Nexus\Controllers\ResourceController@js',
        'as' => 'resource.js',
        'where' => ['path' => '.*']
]);

$router->get('css/{path}', [
        'uses' => 'Sztyup\Nexus\Controllers\ResourceController@css',
        'as' => 'resource.css',
        'where' => ['path' => '.*']
]);
