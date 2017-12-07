<?php

namespace Sztyup\Multisite;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Sztyup\Multisite\Commands\InitializeCommand;
use Sztyup\Multisite\Middleware\StartSession;

class MultisiteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/path/to/config/courier.php' => config_path('courier.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__.'/../routes/auth.php');

        $this->loadViewsFrom(__DIR__.'/../view', 'multisite');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InitializeCommand::class,
            ]);
        }
    }

    protected function bladeDirectives()
    {
        // @route blade funcion, for site specific routes
        \Blade::directive("route", function($expression) {
            return "<?php echo site()->route($expression); ?>";
        });

        \Blade::directive("resource", function() {
            return "<?php echo  ?>";
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
        $this->app->singleton('multisite', function (Application $app) {
            return new SiteManager($app);
        });

        $this->app->alias('multisite', SiteManager::class);

        $this->app->singleton(StartSession::class);

        $this->mergeConfigFrom(
            __DIR__.'/../config/multisite.php', 'courier'
        );

    }
}