<?php

namespace Sztyup\Nexus;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Router;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Sztyup\Nexus\Commands\InitializeCommand;
use Sztyup\Nexus\Middleware\StartSession;

class NexusServiceProvider extends ServiceProvider
{
    public function boot(
        BladeCompiler $blade,
        Repository $config,
        SiteManager $manager,
        Dispatcher $dispatcher
    ) {
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

        $this->app->refresh('request', $manager, 'handleRequest');

        $this->filesystems($manager, $config);
        $this->registerListeners($dispatcher);
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

    protected function registerListeners(Dispatcher $dispatcher)
    {
        $dispatcher->listen(RouteMatched::class, function (RouteMatched $routeMatched) {
            foreach ($routeMatched->route->parameters() as $parameter => $value) {
                if (Str::contains($parameter, '__nexus_')) {
                    $routeMatched->route->forgetParameter($parameter);
                }
            }
        });
    }

    protected function bootRouting()
    {
        Router::macro('nexus', function ($parameters, $routes) {
            $domains = $parameters['domains'];

            if (empty($domains)) {
                return;
            }

            $site = $parameters['site'];

            Arr::forget($parameters, 'domains');
            Arr::forget($parameters, 'site');

            if (count($domains) === 1) {
                $regex = $domains[0];
            } else {
                $regex = '(' . implode('|', $domains) . ')';
            }

            /** @noinspection PhpUndefinedMethodInspection */
            $this->group(array_merge($parameters, [
                'domain' => '{__nexus_' . $site . '}',
                'where' => ['__nexus_' . $site => $regex]
            ]), $routes);
        });
    }

    protected function bladeDirectives(BladeCompiler $blade)
    {
        // @route blade funcion, for site specific routes
        $blade->directive('route', function ($expression) {
            return "<?php echo \$__nexus_site->route($expression); ?>";
        });

        $blade->directive('css', function () {
            return '<?php echo $__nexus_site->css(); ?>';
        });

        $blade->directive('js', function () {
            return '<?php echo $__nexus_site->js(); ?>';
        });

        $blade->directive('resource', function () {
            return '<?php echo  ?>';
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
