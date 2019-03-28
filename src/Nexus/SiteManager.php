<?php

namespace Sztyup\Nexus;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Str;
use Sztyup\Nexus\Contracts\SiteRepositoryContract;
use Sztyup\Nexus\Controllers\ResourceController;
use Sztyup\Nexus\Events\SiteFound;
use Sztyup\Nexus\Exceptions\NexusException;

class SiteManager
{
    /** @var Request */
    protected $request;

    /** @var  Factory */
    protected $viewFactory;

    /** @var UrlGenerator */
    protected $urlGenerator;

    /** @var Container */
    protected $container;

    /** @var  Repository */
    protected $config;

    /** @var  Router */
    protected $router;

    /** @var Dispatcher */
    protected $dispatcher;


    /** @var Collection */
    protected $sites;

    /** @var Site */
    private $current;

    /**
     * SiteManager constructor.
     *
     * @param Factory $viewFactory
     * @param UrlGenerator $urlGenerator
     * @param Router $router
     * @param Repository $config
     * @param Container $container
     * @param Dispatcher $dispatcher
     *
     * @throws NexusException
     * @throws \ReflectionException
     */
    public function __construct(
        Factory $viewFactory,
        UrlGenerator $urlGenerator,
        Router $router,
        Repository $config,
        Container $container,
        Dispatcher $dispatcher
    ) {
        $this->sites = new Collection();
        $this->viewFactory = $viewFactory;
        $this->urlGenerator = $urlGenerator;
        $this->container = $container;
        $this->router = $router;
        $this->config = $config;
        $this->dispatcher = $dispatcher;

        $this->loadSitesFromRepo();
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
     * Handles request
     *
     * @param Request $request
     */
    public function handleRequest(Request $request)
    {
        $this->request = $request;

        if ($this->current === null) {
            // Determine current site
            $currentSite = $this->getByDomain($request->getHost());
            if ($currentSite) {
                $this->dispatcher->dispatch(SiteFound::class, [
                    $currentSite
                ]);

                $this->registerCurrentSite($currentSite);
            }
        }


        // Sets routing domain defaults
        foreach ($this->all() as $site) {
            $this->urlGenerator->defaults([
                '__nexus_' . $site->getName() => $site->getPrimaryDomain()
            ]);
        }
    }

    /**
     * Handles response
     *
     * @param Response $response
     *
     * @throws NexusException
     */
    public function handleResponse(Response $response)
    {
        $sites = $this->all();

        // remove the current site from the collection
        $sites = $sites->except($sites->search($this->current()));

        $encrypter = $this->container->make(Encrypter::class);

        // Render cross-domain login images
        $content = $this->viewFactory->make('nexus::cdimages', [
            'sites' => $sites,
            'code' => $encrypter->encrypt($this->request->session()->getId())
        ])->render();

        // Inject images into the response
        $response->setContent(
            Str::replaceFirst('</body>', $content . "\n</body>", $response->getContent())
        );
    }

    /**
     * Sets some global stuff for easier usage of the current Site object
     *
     * @param Site $site
     */
    protected function registerCurrentSite(Site $site)
    {
        $this->current = $site;

        $this->viewFactory->share('__nexus_site', $site);

        $this->config->set('filesystems.default', $site->getSlug());
    }

    /**
     * Loads all available Site object from the configured repository
     *
     * @throws NexusException
     * @throws \ReflectionException
     */
    protected function loadSitesFromRepo()
    {
        $repositoryClass = $this->getConfig('model_repository');

        // Check if it implements required Contract
        $reflection = new \ReflectionClass($repositoryClass);
        if (!$reflection->implementsInterface(SiteRepositoryContract::class)) {
            throw new NexusException('Configured repository does not implement SiteRepositoryContract');
        }

        // Instantiate repo
        /** @var SiteRepositoryContract $repository */
        $repository = $this->container->make($repositoryClass);

        foreach ($this->getConfig('sites') ?? [] as $site => $siteOptions) {
            $domains = [];
            $params = [];
            $primary = null;

            foreach ($repository->getBySlug($site) ?? [] as $siteModel) {
                $domains[$siteModel->getDomain()] = $siteModel->isEnabled();

                if ($siteModel->isPrimary()) {
                    if ($primary) {
                        throw new NexusException('Can only have one primary domain per site, "' . $site . '" have more');
                    }

                    $primary = $siteModel->getDomain();
                }

                foreach ($siteOptions['extra_params'] ?? [] + $this->getConfig('global_params') ?? [] as $param => $paramOptions) {
                    if ($siteModel->getExtraData($param)) {
                        $params[$siteModel->getDomain()] = $siteModel->getExtraData($param);
                    } elseif ($paramOptions['required']) {
                        throw new NexusException('Require parameter[' . $param . '] is not given for Site: ' . $site);
                    }
                }
            }

            if ($primary === null) {
                throw new NexusException('Must have exactly one primary domain for site ' . $site);
            }

            $commonRegistrars = Collection::make();
            foreach ($siteOptions['routes'] ?? [] as $registrar) {
                $group = $this->container->make($registrar);
                if (!$group instanceof CommonRouteGroup) {
                    throw new NexusException('Given class does not implement CommonRouteGroup interface');
                }

                $commonRegistrars->push($group);
            }

            // Always have at least one domain to avoid missing routes errors
            if (empty($domains)) {
                throw new NexusException('All site must have at least one domain');
            }

            if (empty(array_filter($domains))) {
                throw new NexusException('Site "' . $site . '" must have at least one enabled domain');
            }

            $this->sites->push(
                $site = $this->container->make(Site::class, [
                    'commonRegistrars' => $commonRegistrars,
                    'domains' => $domains,
                    'name' => $site,
                    'title' => $siteOptions['title'] ?? 'Névtelen',
                    'domainParams' => $params,
                    'primaryDomain' => $primary
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
            /** @noinspection PhpUndefinedMethodInspection */
            $this->router->nexus([
                'middleware' => ['nexus', 'web'],
                'site' => $site->getName(),
                'domains' => $site->getEnabledDomains()
            ], function (Router $router) use ($site) {
                include __DIR__ . '/../routes/resources.php';

                /*
                 * Route returning empty response, needed for the cross-domain login.
                 * Used by the cross domain redirect page, where it includes this route as an image
                 * for all domain and a middleware uses the encrypted session_id as its own session id
                 */
                $this->router->get('nexus/internal/auth', [
                    'uses' => ResourceController::class . '@internalAuth',
                    'as' => $site->getRoutePrefix() . '.auth.internal'
                ]);
            });
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
            if (method_exists($site, 'get' . ucfirst($field))) {
                $got = $site->{'get' . ucfirst($field)}();
            } elseif (method_exists($site, 'is' . ucfirst($field))) {
                $got = $site->{'is' . ucfirst($field)}();
            } else {
                $got = null;
            }

            if (is_array($got)) {
                return in_array($value, $got, true);
            }

            return $got === $value;
        });
    }

    /**
     * @return Site
     * @throws NexusException()
     */
    public function current()
    {
        if ($this->request === null) {
            throw new NexusException('SiteManager has not been booted');
        }

        return $this->current;
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
}
