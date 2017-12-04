<?php

namespace Sztyup\Multisite;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Sztyup\Multisite\Middleware\StartSession;

class MultisiteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // @route blade funcion, for site specific routes
        \Blade::directive("route", function($expression) {
            return "<?php echo site()->route($expression); ?>";
        });

        \Blade::directive("internal_auth", function($expression) {
            $parts = explode(",", $expression);

            $site = $parts[0];
            $code = $parts[1];

            return "<?php echo site()->getById($site)->route('auth.internal', ['s_code' => $code]); ?>";
        });
    }

    public function register()
    {
        $this->app->singleton('site.manager', function (Application $app) {
            return new SiteManager($app);
        });

        $this->app->alias('site.manager', SiteManager::class);

        $this->app->singleton(StartSession::class);
    }
}