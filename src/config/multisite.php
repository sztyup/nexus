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
    | Sites
    |--------------------------------------------------------------------------
    |
    | Here are the sites with some settings regarding blogs and auth system
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
    'model_repository' => 'App\Models\Site',

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
        'auth_waiting' => 'multisite:auth.redirect'
    ]
];