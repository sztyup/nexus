<?php

Route::group([
    'namespace' => 'Sztyup\\Multisite',
    'domain' => config('multisite.main_domain'),
], function() {
    Route::get('auth', [
        'uses' => 'Controllers\AuthenticationController@auth',
        'as' => 'auth',
    ]);

    Route::get('auth/2fa', [
        'uses' => 'Controllers\TwoFactorController@settings',
        'as' => 'twofactor.settings',
        'middleware' => 'auth'
    ]);
    Route::post('auth/2fa', [
        'uses' => 'Controllers\TwoFactorController@settings',
        'as' => 'twofactor.settings',
        'middleware' => 'auth'
    ]);

    Route::get('auth/2fa/check', [
        'uses' => 'Controllers\TwoFactorController@check',
        'as' => 'twofactor.check',
        'middleware' => 'auth'
    ]);
    Route::post('auth/2fa/check', [
        'uses' => 'Controllers\TwoFactorController@check',
        'as' => 'twofactor.check',
        'middleware' => 'auth'
    ]);

    Route::get('auth/2fa/login', [
        'uses' => 'Controllers\TwoFactorController@login',
        'as' => 'twofactor.login'
    ]);

    Route::post('auth/2fa/login', [
        'uses' => 'Controllers\TwoFactorController@login',
        'as' => 'twofactor.login'
    ]);

    Route::get('auth/redirect/{provider}', [
        'uses' => 'Controllers\AuthenticationController@redirect',
        'as' => 'auth.redirect'
    ]);

    Route::get('auth/callback/{provider}', [
        'uses' => 'Controllers\AuthenticationController@callback',
        'as' => 'auth.callback'
    ]);

});