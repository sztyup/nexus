<?php

namespace Sztyup\Nexus;

use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Router;
use Illuminate\Session\SessionManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Sztyup\Nexus\Commands\InitializeCommand;
use Sztyup\Nexus\Middleware\Impersonate;
use Sztyup\Nexus\Middleware\InjectCrossDomainLogin;
use Sztyup\Nexus\Middleware\StartSession;

class NexusServiceProvider extends ServiceProvider
{
    public function boot(BladeCompiler $blade)
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

        $this->bootRouting();
        $this->bladeDirectives($blade);
    }

    protected function bootRouting()
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        // Add middleware group named 'nexus' with everything needed for us
        $router->middlewareGroup(
            'nexus',
            [
                StartSession::class,
                InjectCrossDomainLogin::class,
                Impersonate::class
            ]
        );

        /** @var SiteManager $manager */
        $manager = $this->app->make(SiteManager::class);

        // Register all routes for the sites
        $manager->registerRoutes();

        $this->app->make('view')->share('site', $manager->current());
    }

    protected function bladeDirectives(BladeCompiler $blade)
    {
        // @route blade funcion, for site specific routes
        $blade->directive("route", function ($expression) {
            return "<?php echo site()->route($expression); ?>";
        });

        $blade->directive("resource", function () {
            return "<?php echo  ?>";
        });
    }

    public function register()
    {
        $this->app->singleton(SiteManager::class);

        $this->app->alias('nexus', SiteManager::class);

        $this->mergeConfigFrom(
            __DIR__.'/../config/nexus.php',
            'nexus'
        );

        $this->registerSession();
    }

    protected function registerSession()
    {
        $this->app->singleton('session', function ($app) {
            return new SessionManager($app);
        });

        $this->app->singleton('session.store', function (Container $app) {
            return $app->make('session')->driver();
        });

        $this->app->singleton(StartSession::class);
    }
}
