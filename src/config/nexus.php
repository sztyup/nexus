<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Main domain
    |--------------------------------------------------------------------------
    |
    | If the multi domain system fails for any reason this one domain is the
    | last line of defense.
    | It doesn't depend on the pipeline system, the database, the cache system,
    | and a lot of thing, in order to maintain a minimum content available in
    | case of system failures.
    |
    */
    'main_domain' => env('MAIN_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Disabled route
    |--------------------------------------------------------------------------
    |
    | This route is used if a site is disabled
    |
    */
    'disabled_route' => function () {
        throw new \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException();
    },

    /*
    |--------------------------------------------------------------------------
    | Router namespace
    |--------------------------------------------------------------------------
    |
    | Here you should define the namespace where all your controllers are, so
    | your route files dont need the include this in all routes
    |
    */
    'route_namespace' => 'App\Http\Controllers',

    /*
    |--------------------------------------------------------------------------
    | Global parameters
    |--------------------------------------------------------------------------
    |
    | The parameters defined here are available to all site and if required
    | they can throw exception if a site doesnt have a value for it
    |
    */
    'global_params' => [
        'tracker_id' => [
            'required' => false
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Sites
    |--------------------------------------------------------------------------
    |
    | Here are the sites
    |
    */
    'sites' => [
        'Foo' => [
            'title' => 'Foo site',
            'default_domain' => 'example.com'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Route Groups
    |--------------------------------------------------------------------------
    |
    | List the route collection which can be used dinamically on selected sites
    |
    */
    'common_route_groups' => [
    ],

    /*
    |--------------------------------------------------------------------------
    | Site model repository
    |--------------------------------------------------------------------------
    |
    | The repository class which
    |
    */
    'model_repository' => null,

    /*
    |--------------------------------------------------------------------------
    | Directories
    |--------------------------------------------------------------------------
    |
    | Here you can override the default directory structure
    |
    */
    'directories' => [
        'routes' => base_path('routes'),
        'resources' => resource_path('sites'),
        'assets' => storage_path('assets')
    ],
];
