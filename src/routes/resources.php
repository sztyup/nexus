<?php

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
