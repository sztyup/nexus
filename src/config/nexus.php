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
    | Sites
    |--------------------------------------------------------------------------
    |
    | Here are the sites
    |
    */
    'sites' => [
    ],

    /*
    |--------------------------------------------------------------------------
    | Site model repository
    |--------------------------------------------------------------------------
    |
    | The repository class which
    |
    */
    'model_repository' => \Sztyup\Nexus\Doctrine\SiteRepository::class,

    'extra_params' => [
        'tracking_id' => [
            'required' => true,
            'type' => 'string'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipelines
    |--------------------------------------------------------------------------
    |
    | The pipelines which are used by the multi site system
    |
    */
    'pipelines' => [

    ],

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

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | Here you can override the default views
    |
    */
    'views' => [
    ]
];
