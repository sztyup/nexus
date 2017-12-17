<?php

namespace Sztyup\Nexus;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\Factory;
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

    /** @var  Registrar */
    protected $registrar;


    /** @var Collection */
    protected $sites;

    /** @var  int */
    private $currentId;

    public function __construct(Container $container)
    {
        $this->sites = new Collection();
        $this->request = $container->make(Request::class);
        $this->viewFactory = $container->make(Factory::class);
        $this->urlGenerator = $container->make(UrlGenerator::class);
        $this->registrar = $container->make(Registrar::class);
        $this->config = $container->make(Repository::class)->get('nexus');

        $this->loadSitesFromRepo($container);
        $this->determineCurrentSite();
    }

    protected function determineCurrentSite()
    {
        if($this->isConsole()) {
            return null;
        }

        $currentSite = $this->getByDomain($host = $this->request->getHost());
        if($currentSite == null) {
            throw new SiteNotFoundException($host);
        }

        $this->currentId = $currentSite->getId();

        return $currentSite;
    }

    protected function loadSitesFromRepo(Container $container)
    {
        $repositoryClass = $this->config['model_repository'];

        $reflection = new \ReflectionClass($repositoryClass);
        if(!$reflection->implementsInterface(SiteRepositoryContract::class)) {
            throw new \Exception('Configured repository does not implement SiteRepositoryContract');
        }

        /** @var SiteRepositoryContract $repository */
        $repository= $container->make($repositoryClass);

        /** @var SiteModelContract $siteModel */
        foreach($repository->getAll() as $siteModel) {
            $this->sites->put(
                $siteModel->getId(),
                $container->make(Site::class, ['site' => $siteModel])
            );
        }
    }

    public function registerRoutes()
    {
        /*
         * Main domain, where the central authentication takes place, can be moved by enviroment,
         * and independent of the sites table, and much else
         */
        $this->registrar->group([
            'middleware' => ['web'],
            'domain' => config('sites.main_domain', 'example.com'),
            'as' => 'main.',
            'namespace' => $this->config['route_namespace'] . '\\Main'
        ], base_path('routes/main.php'));

        /*
        * Resource routes, to handle resources for each site
        * Its needed to avoid eg. golya.sch.bme.hu/js/golya/app.js, instead we can use golya.sch.bme.hu/js/app.js
        */
        $this->registrar->get('img/{path}', 'Sztyup\Nexus\Controllers\ResourceController@image')->where('path', '.*')->name('resource.img');
        $this->registrar->get('js/{path}', 'Sztyup\Nexus\Controllers\ResourceController@js')->where('path', '.*')->name('resource.js');
        $this->registrar->get('css/{path}', 'Sztyup\Nexus\Controllers\ResourceController@css')->where('path', '.*')->name('resource.css');

        $this->registrar->group([
            'middleware' => ['web'],
            'namespace' => $this->config['route_namespace']
        ], function() {
            include base_path('routes/global.php');

            /*
             * Register each site's route
             */
            /** @var Site $site */
            foreach ($this->all() as $site) {
                $site->registerRoutes();
            }
        });
    }

    protected function findBy($field, $value): Collection
    {
        return $this->sites->filter(function(Site $site) use ($field, $value) {
            return $site->{"get" . ucfirst($field)}() == $value;
        });
    }

    public function current()
    {
        if($this->isConsole()) {
            return null;
        }

        return $this->sites[$this->currentId];
    }

    public function getByDomain(string $domain): Site
    {
        return $this->findBy('domain', $domain)->first();
    }

    public function getBySlug(string $slug): Site
    {
        return $this->findBy('slug', $slug)->first();
    }

    public function getById(int $id): Site
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

    public function auth($route = null, $provider = null): string
    {
        if (empty($route)) {
            $route = $this->request->getPathInfo();
        }

        $data = base64_encode(json_encode([
            "redirect_site" => $this->current()->getId(),
            "redirect_uri" => $route,
            "preferred_provider" => $provider
        ]));

        return $this->urlGenerator->route("main.auth", [
            "data" => $data,
        ]);
    }

    private function isConsole(): bool
    {
        return php_sapi_name() == 'cli' || php_sapi_name() == 'phpdbg';
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
        if($this->isConsole()) {
            return null;
        }

        if(method_exists($this->current(), $name)) {
            return $this->current()->{$name}(...$arguments);
        }

        throw new \BadMethodCallException('Method[' . $name . '] does not exists on Site');
    }

}