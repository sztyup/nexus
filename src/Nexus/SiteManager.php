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
use Sztyup\Nexus\Exceptions\SiteNotFoundException;

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

    /**
     * SiteManager constructor.
     *
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
        $this->config = $config->get('nexus');

        $this->loadSitesFromRepo($container);
        $this->determineCurrentSite();
    }

    protected function determineCurrentSite()
    {
        if ($this->isConsole()) {
            return null;
        }

        $currentSite = $this->getByDomain($this->request->getHost());
        if ($currentSite) {
            $this->currentId = $currentSite->getId();
        }

        return $currentSite;
    }

    /**
     * @param Container $container
     * @throws \Exception
     */
    protected function loadSitesFromRepo(Container $container)
    {
        $repositoryClass = $this->config['model_repository'];

        // Check if it implements required Contract
        $reflection = new \ReflectionClass($repositoryClass);
        if (!$reflection->implementsInterface(SiteRepositoryContract::class)) {
            throw new \Exception('Configured repository does not implement SiteRepositoryContract');
        }

        // Instantiate repo
        /** @var SiteRepositoryContract $repository */
        $repository= $container->make($repositoryClass);

        // Add each of the sites to the collection
        /** @var SiteModelContract $siteModel */
        foreach ($repository->getAll() as $siteModel) {
            $commonRegistrars = [];
            foreach ($this->config['sites'][$siteModel->getName()]['routes'] ?? [] as $registrar) {
                $group = $container->make($registrar);
                if (!$group instanceof CommonRouteGroup) {
                    throw new \InvalidArgumentException('Given class does not implement CommonRouteGroup interface');
                }

                $commonRegistrars[] = $container->make($registrar);
            }

            $this->sites->put(
                $siteModel->getId(),
                $container->make(Site::class, [
                    'site' => $siteModel,
                    'commonRegistrars' => $commonRegistrars
                ])
            );
        }
    }

    public function registerRoutes()
    {
        /*
         * Main domain, where the central authentication takes place, can be moved by enviroment,
         * and independent of the sites storage, the asset generator pipeline and much else
         */
        $this->router->group([
            'middleware' => ['nexus', 'web'],
            'domain' => $this->config['main_domain'],
            'as' => 'main.',
            'namespace' => $this->config['route_namespace'] . '\\Main'
        ], $this->config['directories']['routes'] . DIRECTORY_SEPARATOR . 'main.php');

        /*
        * Resource routes, to handle resources for each site
        * Its needed to avoid eg. golya.sch.bme.hu/js/golya/app.js, instead we can use golya.sch.bme.hu/js/app.js
        */
        $this->router->get(
            'img/{path}',
            [
                'uses' => 'Sztyup\Nexus\Controllers\ResourceController@image',
                'as' => 'resource.img',
                'where' => ['path' => '.*']
            ]
        );
        $this->router->get(
            'js/{path}',
            [
                'uses' => 'Sztyup\Nexus\Controllers\ResourceController@js',
                'as' => 'resource.js',
                'where' => ['path' => '.*']
            ]
        );
        $this->router->get(
            'css/{path}',
            [
                'uses' => 'Sztyup\Nexus\Controllers\ResourceController@css',
                'as' => 'resource.css',
                'where' => ['path' => '.*']
            ]
        );

        // Global route group
        $this->router->group([
            'middleware' => ['nexus', 'web'],
            'namespace' => $this->config['route_namespace']
        ], function () {
            /* Global routes applied to each site */
            include $this->config['directories']['routes'] . DIRECTORY_SEPARATOR . 'global.php';

            /* Register each site's route */
            foreach ($this->all() as $site) {
                $site->registerRoutes();
            }
        });

        $this->router->getRoutes()->refreshActionLookups();
        $this->router->getRoutes()->refreshNameLookups();
    }

    protected function findBy($field, $value): Collection
    {
        return $this->sites->filter(function (Site $site) use ($field, $value) {
            return $site->{"get" . ucfirst($field)}() == $value;
        });
    }

    /**
     * @return Site
     */
    public function current()
    {
        if ($this->isConsole()) {
            return null;
        }

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
        return $this->findBy('domain', $domain)->first();
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

    private function isConsole(): bool
    {
        return php_sapi_name() == 'cli' || php_sapi_name() == 'phpdbg';
    }

    public function impersonate(int $userId)
    {
        $this->request->session()->put('_nexus_impersonate', $userId);
    }

    public function stopImpersonating()
    {
        $this->request->session()->forget('_nexus_impersonate');
    }

    public function isImpersonating()
    {
        $this->request->session()->has('_nexus_impersonate');
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
         * If running in console then we dont have a current site
         */
        if ($this->isConsole()) {
            return null;
        }

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
