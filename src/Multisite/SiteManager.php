<?php

namespace Sztyup\Multisite;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Sztyup\Multisite\Exceptions\SiteNotFoundException;

class SiteManager
{
    /** @var Request */
    protected $request;

    /** @var  Factory */
    protected $viewFactory;

    /** @var UrlGenerator */
    protected $urlGenerator;

    /** @var Encrypter */
    protected $encrypter;

    /** @var  Repository */
    protected $config;


    /** @var Collection */
    protected $sites;

    /** @var  int */
    protected $currentId;

    /** @var   */
    protected $manifest;

    public function __construct(Container $container)
    {
        $this->sites = new Collection();
        $this->request = $container->make(Request::class);
        $this->viewFactory = $container->make(Factory::class);
        $this->urlGenerator = $container->make(UrlGenerator::class);
        $this->encrypter =  $container->make(Encrypter::class);
        $this->config = $container->make(Repository::class)->get('multisite');

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
        /** @var SiteRepositoryContract $model */
        $repository = $this->config['model_repository'];

        $reflection = new \ReflectionClass($repository);
        if(!$reflection->implementsInterface(SiteRepositoryContract::class)) {
            throw new \Exception('Configured repository does not extend SiteRepositoryContract');
        }

        $repository = $container->make($repository);

        /** @var SiteModelContract $siteModel */
        foreach($repository->getAll() as $siteModel) {
            $this->sites->put(
                $siteModel->getId(),
                $container->make(Site::class, ['site' => $siteModel])
            );
        }
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

    public function redirectPage(): View
    {
        $session = $this->request->session();

        $intended = '//' . $this->getById( $session->get('origin.site') )->getDomain() . $session->get('origin.uri');

        return $this->viewFactory->make('main.auth.redirect', [
            'sites' => $this
                ->sites
                ->filter(function(Site $site) {
                    return
                        $site->getId() != $this->currentId &&
                        $site->isEnabled();
                })
                ->pluck('id'),
            'code' => $this->encrypter->encrypt($session->getId()),
            'intended' => $intended
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