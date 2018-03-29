<?php

namespace Sztyup\Nexus;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Router;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Sztyup\Nexus\Commands\InitializeCommand;
use Sztyup\Nexus\Middleware\Nexus;
use Sztyup\Nexus\Middleware\StartSession;

class NexusServiceProvider extends ServiceProvider
{
    public function boot(BladeCompiler $blade, Repository $config)
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

        $this->bootRouting($manager);
        $this->filesystems($manager, $config);
        $this->bladeDirectives($blade);
    }

    /**
     * Registers storage disks for all sites and sets the current one as default
     *
     * @param SiteManager $manager
     * @param Repository $config
     */
    protected function filesystems(SiteManager $manager, Repository $config)
    {
        $disks = $config->get('filesystems.disks');

        foreach ($manager->all() as $site) {
            $disks[$site->getSlug()] = [
                'driver' => 'local',
                'root' => $config->get('nexus.directories.storage') . DIRECTORY_SEPARATOR . $site->getSlug()
            ];
        }

        $config->set('filesystems.disks', $disks);
    }

    protected function bootRouting(SiteManager $manager)
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        // Add middleware group named 'nexus' with everything needed for us
        $router->middlewareGroup(
            'nexus',
            [
                Nexus::class,
                StartSession::class
            ]
        );

        $router::macro('nexus', function ($parameters, $routes) {
            /** @var Site $site */
            $site = $parameters['site'];

            Arr::forget($parameters, 'site');

            if (count($site->getDomains()) == 1) {
                $regex = $site->getDomains()[0];
            } else {
                $regex = '(' . implode('|', $site->getDomains()) . ')';
            }

            $this->group(array_merge($parameters, [
                'domain' => '{__nexus_' . $site->getName() . '}',
                'where' => ['__nexus_' . $site->getName() => $regex]
            ]), $routes);
        });

        // Register all routes for the sites
        $manager->registerRoutes();
    }

    protected function bladeDirectives(BladeCompiler $blade)
    {
        // @route blade funcion, for site specific routes
        $blade->directive("route", function ($expression) {
            return "<?php echo nexus()->route($expression); ?>";
        });

        $blade->directive("resource", function () {
            return "<?php echo  ?>";
        });
    }

    public function register()
    {
        $this->app->singleton(SiteManager::class);

        $this->app->alias(SiteManager::class, 'nexus');

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
