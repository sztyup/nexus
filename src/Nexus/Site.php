<?php

namespace Sztyup\Nexus;

use Collective\Html\HtmlBuilder;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Exception;
use Sztyup\Nexus\Exceptions\NexusException;

class Site
{
    /**
     * The name of the site as represented in the code
     *
     * @var string
     */
    private $name;

    /**
     * The domain where we accept requests for the site
     *
     * @var array
     */
    private $domains;

    /**
     * The primary domain, where routing always goes
     *
     * @var string
     */
    private $primaryDomain;

    /**
     * @var array
     */
    private $domainParams;

    /**
     * View service
     *
     * @var View
     */
    protected $view;

    /**
     * URL generator service
     *
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * HTML builder service
     *
     * @var HtmlBuilder
     */
    protected $html;

    /**
     * @var array The config for the site
     */
    protected $config;

    /**
     * @var Collection The Common Route Group registrars
     */
    protected $commonRegistrars;

    /**
     * Create a new site instance.
     *
     * @param Factory $view
     * @param UrlGenerator $urlGenerator
     * @param HtmlBuilder $builder
     * @param Repository $config
     * @param Collection $commonRegistrars
     * @param array $domains
     * @param array $domainParams
     * @param string $name
     * @param string $primaryDomain
     */
    public function __construct(
        Factory $view,
        UrlGenerator $urlGenerator,
        HtmlBuilder $builder,
        Repository $config,
        Collection $commonRegistrars,
        array $domains,
        array $domainParams,
        string $name,
        string $primaryDomain
    ) {
        $this->view = $view;
        $this->urlGenerator = $urlGenerator;
        $this->html = $builder;
        $this->config = $config->get('nexus');
        $this->commonRegistrars = $commonRegistrars;
        $this->domains = $domains;
        $this->domainParams = $domainParams;
        $this->name = $name;
        $this->primaryDomain = $primaryDomain;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDomains(): array
    {
        return array_keys($this->domains);
    }

    public function isEnabled($domain): bool
    {
        return $this->domains[$domain] ?? false;
    }

    public function getEnabledDomains()
    {
        return array_keys(array_filter($this->domains));
    }

    public function getDisabledDomains()
    {
        return array_keys(array_filter($this->domains, function ($value) {
            return !$value;
        }));
    }

    public function getPrimaryDomain()
    {
        return $this->primaryDomain;
    }

    /*
     * Attributes
     */
    public function getSlug()
    {
        return Str::lower($this->getName());
    }

    public function getSiteSpecificRoute($route): string
    {
        return $this->getRoutePrefix() . '.' . $route;
    }

    public function getSiteSpecificView($view): string
    {
        return $this->getViewPrefix() . '.' . $view;
    }

    public function getRoutePrefix(): string
    {
        return $this->getSlug();
    }

    public function getViewPrefix(): string
    {
        return $this->getSlug();
    }

    public function getPermissionPrefix(): string
    {
        return $this->getSlug();
    }

    public function getNameSpace(): string
    {
        return $this->getName();
    }

    public function getRoutesFile(): string
    {
        return $this->routePath($this->getRoutePrefix() . '.php');
    }

    /*
     * Functions
     */

    /**
     * Returns site specific route, or general if none exists
     *
     * @param $route
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    public function route($route, $parameters = [], $absolute = true): string
    {
        try {
            return $this->urlGenerator->route($this->getSiteSpecificRoute($route), $parameters, $absolute);
        } catch (Exception $exception) {
            return $this->urlGenerator->route($route, $parameters, $absolute);
        }
    }

    /**
     * Return site specific view, or general if none exists
     *
     * @param $view
     * @param array $data
     * @param $mergeData
     * @return View
     */
    public function view($view, $data = [], $mergeData = []): View
    {
        if ($this->view->exists($this->getSiteSpecificView($view))) {
            $view = $this->getSiteSpecificView($view);
        }

        return $this->view->make($view, $data, $mergeData);
    }

    public function getParamForDomain(string $domain, string $param)
    {
        return $this->domainParams[$domain][$param] ?? null;
    }

    /**
     * Registers all route for this site
     * @param Registrar $router
     */
    public function registerRoutes(Registrar $router)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $router->nexus([
            'site' => $this->getName(),
            'domains' => $this->getDomains()
        ], function () use ($router) {
            $this->commonRegistrars->each(function (CommonRouteGroup $routeGroup) {
                $routeGroup->setSite($this)->register();
            });

            /*
             * Include the actual route file for the site
             */
            $router->group([
                'as' => $this->getRoutePrefix() . '.',
                'namespace' => $this->getNameSpace()
            ], function (Registrar $router) {
                /** @noinspection PhpIncludeInspection */
                include $this->getRoutesFile();
            });
        });
    }

    /*
     * Configuration
     */

    protected function getSiteConfig($key)
    {
        return data_get($this->config['sites'][$this->getName()], $key);
    }

    public function storagePath($path)
    {
        return $this->config['directories']['storage'] . DIRECTORY_SEPARATOR .
            $this->getSlug() . DIRECTORY_SEPARATOR . $path;
    }

    public function resourcePath($path)
    {
        return $this->config['directories']['resources'] . DIRECTORY_SEPARATOR .
            $this->getSlug() . DIRECTORY_SEPARATOR . $path;
    }

    public function assetPath($path)
    {
        return $this->config['directories']['assets'] . DIRECTORY_SEPARATOR .
            $this->getSlug() . DIRECTORY_SEPARATOR . $path;
    }

    protected function routePath($path)
    {
        return $this->config['directories']['routes'] . DIRECTORY_SEPARATOR . $path;
    }

    /*
     * Assets
     */

    /**
     * @param $path
     * @return HtmlString
     * @throws NexusException
     */
    private function mix($path): HtmlString
    {
        $manifestFile = $this->config['directories']['assets'] . DIRECTORY_SEPARATOR . 'mix-manifest.json';
        if (! file_exists($manifestFile)) {
            throw new NexusException('The Mix manifest does not exist.');
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);

        if (! Str::startsWith($path, DIRECTORY_SEPARATOR)) {
            $path = DIRECTORY_SEPARATOR . $path;
        }

        if (! array_key_exists($path, $manifest)) {
            throw new NexusException('No generated asset exists for this site.');
        }

        $path = implode(DIRECTORY_SEPARATOR, Arr::except(explode(DIRECTORY_SEPARATOR, $manifest[$path]), 1));

        return new HtmlString($path);
    }

    /**
     * @return HtmlString
     * @throws NexusException
     */
    public function css(): HtmlString
    {
        return $this->html->style(
            $this->mix($this->getViewPrefix() . '/css/app.css')
        );
    }

    /**
     * @return HtmlString
     * @throws NexusException
     */
    public function js(): HtmlString
    {
        return $this->html->script(
            $this->mix($this->getViewPrefix() . '/js/app.js')
        );
    }
}
