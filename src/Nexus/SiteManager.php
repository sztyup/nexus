<?php

namespace Sztyup\Nexus;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\Factory;
use Sztyup\Nexus\Contracts\CommonRouteGroup;
use Sztyup\Nexus\Contracts\SiteRepositoryContract;

class SiteManager
{
    /** @var Request */
    protected $request;

    /** @var  Factory */
    protected $viewFactory;

    /** @var UrlGenerator */
    protected $urlGenerator;

    /** @var  Repository */
    protected $config;

    /** @var  Router */
    protected $router;


    /** @var Collection */
    protected $sites;

    /** @var  int */
    private $currentId = 0;

    const IMPERSONATE_SESSION_KEY = '_nexus_impersonate';

    /**
     * SiteManager constructor.
     *
     * @param Request $request
     * @param Factory $viewFactory
     * @param UrlGenerator $urlGenerator
     * @param Router $router
     * @param Repository $config
     * @param Container $container
     * @throws \Exception
     */
    public function __construct(
        Request $request,
        Factory $viewFactory,
        UrlGenerator $urlGenerator,
        Router $router,
        Repository $config,
        Container $container
    ) {
        $this->sites = new Collection();
        $this->request = $request;
        $this->viewFactory = $viewFactory;
        $this->urlGenerator = $urlGenerator;
        $this->router = $router;
        $this->config = $config;

        $this->loadSitesFromRepo($container);
        $this->determineCurrentSite($request);
    }

    /**
     * Gets a config out of nexus
     *
     * @param $config
     * @param null $default
     * @return mixed
     */
    public function getConfig($config, $default = null)
    {
        return $this->config->get('nexus.' . $config, $default);
    }

    /**
     * Logic for determining current site
     *
     * @param Request $request
     * @return Site
     */
    public function determineCurrentSite(Request $request)
    {
        $currentSite = $this->getByDomain($request->getHost());
        if ($currentSite) {
            $this->registerCurrentSite($currentSite);
        }

        return $currentSite;
    }

    /**
     * Sets some global stuff for easier usage of the current Site object
     *
     * @param Site $site
     */
    protected function registerCurrentSite(Site $site)
    {
        $this->currentId = $site->getId();

        $this->viewFactory->share('nexus', $this);

        $this->config->set('filesystems.default', $site->getSlug());
    }

    /**
     * Loads all available Site object from the configured repository
     *
     * @param Container $container
     * @throws \Exception
     */
    protected function loadSitesFromRepo(Container $container)
    {
        $repositoryClass = $this->getConfig('model_repository');

        // Check if it implements required Contract
        $reflection = new \ReflectionClass($repositoryClass);
        if (!$reflection->implementsInterface(SiteRepositoryContract::class)) {
            throw new \Exception('Configured repository does not implement SiteRepositoryContract');
        }

        // Instantiate repo
        /** @var SiteRepositoryContract $repository */
        $repository = $container->make($repositoryClass);

        foreach ($this->getConfig('sites') ?? [] as $site => $siteOptions) {
            $domains = [];
            $params = [];

            foreach ($repository->getBySlug($site) ?? [] as $siteModel) {
                if ($siteModel->isEnabled()) {
                    $domains[] = $siteModel->getDomain();
                }

                foreach ($siteOptions['extra_params'] ?? [] as $param => $paramOptions) {
                    if ($siteModel->getExtraData($param)) {
                        $params[] = $siteModel->getExtraData($param);
                    } elseif ($paramOptions['required']) {
                        throw new \Exception('Require parameter[' . $param . '] is not given for Site: ' . $site);
                    }
                }
            };

            if (empty($domains)) {
                continue;
            }

            $commonRegistrars = [];
            foreach ($siteOptions['routes'] ?? [] as $registrar) {
                $group = $container->make($registrar);
                if (!$group instanceof CommonRouteGroup) {
                    throw new \InvalidArgumentException('Given class does not implement CommonRouteGroup interface');
                }

                $commonRegistrars[] = $container->make($registrar);
            }

            $this->sites->push(
                $container->make(Site::class, [
                    'commonRegistrars' => $commonRegistrars,
                    'domains' => $domains,
                    'name' => $site
                ])
            );
        }
    }

    /**
     * Register all routes defined by the Sites
     */
    public function registerRoutes()
    {
        /*
         * Main domain, where the central authentication takes place, can be moved by enviroment,
         * and independent of the sites storage, the asset generator pipeline and much else
         */
        if (file_exists($main = $this->getConfig('directories.routes') . DIRECTORY_SEPARATOR . 'main.php')) {
            $this->router->group([
                'middleware' => ['nexus', 'web'],
                'domain' => $this->getConfig('main_domain'),
                'as' => 'main.',
                'namespace' => $this->getConfig('route_namespace') . '\\Main'
            ], $main);
        }

        /*
         * Resource routes, to handle resources for each site
         * Its needed to avoid eg. golya.sch.bme.hu/js/golya/app.js,
         * instead we can use golya.sch.bme.hu/js/app.js
         */
        foreach ($this->all() as $site) {
            $this->router->group([
                'middleware' => ['nexus', 'web'],
                'domain' => '{domain}',
                'where' => ['domain' => $site->getDomainsAsString()]
            ], __DIR__ . '/../routes/resources.php');
        }

        // Global route group
        $global = $this->getConfig('directories.routes') . DIRECTORY_SEPARATOR . 'global.php';

        if (file_exists($global)) {
            $this->registerGlobalRoute(function ($router) use ($global) {
                include $global;
            });
        }

        foreach ($this->all() as $site) {
            $this->registerGlobalRoute(function ($router) use ($site) {
                $site->registerRoutes($router);
            });
        }
    }

    /**
     * Passes the given closure to a route group with everything setup for nexus
     *
     * @param \Closure $closure
     */
    public function registerGlobalRoute(\Closure $closure)
    {
        $this->router->group([
            'middleware' => ['nexus', 'web'],
            'namespace' => $this->getConfig('route_namespace')
        ], function () use ($closure) {
            $closure($this->router);
        });

        /*
         * Needed because of Route::...->name() declarations
         */
        $this->router->getRoutes()->refreshActionLookups();
        $this->router->getRoutes()->refreshNameLookups();
    }

    /**
     * @param $field
     * @param $value
     * @return Collection
     */
    protected function findBy($field, $value): Collection
    {
        return $this->sites->filter(function (Site $site) use ($field, $value) {
            $got = $site->{"get" . ucfirst($field)}();
            if (is_array($got)) {
                return in_array($value, $got);
            } else {
                return $got == $value;
            }
        });
    }

    /**
     * @return Site
     */
    public function current()
    {
        if ($this->currentId == 0) {
            return null;
        }

        return $this->sites[$this->currentId];
    }

    /**
     * @param string $domain
     * @return Site
     */
    public function getByDomain(string $domain)
    {
        return $this->findBy('domains', $domain)->first();
    }

    /**
     * @param string $slug
     * @return Site
     */
    public function getBySlug(string $slug)
    {
        return $this->findBy('slug', $slug)->first();
    }

    /**
     * @param int $id
     * @return Site
     */
    public function getById(int $id)
    {
        return $this->sites->get($id);
    }

    /**
     * @return Collection|Site[]
     */
    public function all(): Collection
    {
        return $this->sites;
    }

    public function getEnabledSites(): Collection
    {
        return $this->findBy('enabled', true);
    }

    /*
     * Impersonation
     */
    public function impersonate(int $userId)
    {
        $this->request->session()->put(self::IMPERSONATE_SESSION_KEY, $userId);
    }

    public function stopImpersonating()
    {
        $this->request->session()->forget(self::IMPERSONATE_SESSION_KEY);
    }

    public function isImpersonating()
    {
        $this->request->session()->has(self::IMPERSONATE_SESSION_KEY);
    }

    /**
     * Direct every call to the current site
     *
     * @param $name
     * @param $arguments
     * @return null
     */
    public function __call($name, $arguments)
    {
        /*
         * If we couldnt find current site
         */
        if ($this->currentId == 0) {
            return null;
        }

        if (method_exists($this->current(), $name)) {
            return $this->current()->{$name}(...$arguments);
        }

        throw new \BadMethodCallException('Method[' . $name . '] does not exists on Site');
    }
}
