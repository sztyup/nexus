<?php

namespace Sztyup\Nexus;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Sztyup\Nexus\Commands\InitializeCommand;
use Sztyup\Nexus\Middleware\StartSession;

class NexusServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/nexus.php' => config_path('nexus.php'),
        ], 'config');

        $this->loadViewsFrom(__DIR__.'/../view', 'nexus');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InitializeCommand::class,
            ]);
        }

        /** @var SiteManager $manager */
        $manager = $this->app->make(SiteManager::class);

        // Register all routes for the sites
        $manager->registerRoutes();
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
    }

    public function register()
    {
        $this->app->singleton('nexus', function (Application $app) {
            return new SiteManager($app);
        });

        $this->app->alias('nexus', SiteManager::class);

        $this->mergeConfigFrom(
            __DIR__.'/../config/nexus.php', 'nexus'
        );

        $this->registerSession();
    }

    protected function registerSession()
    {
        $this->app->singleton(StartSession::class);
    }
}