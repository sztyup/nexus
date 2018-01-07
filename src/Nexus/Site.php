<?php

namespace Sztyup\Nexus;

use Collective\Html\HtmlBuilder;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Exception;

class Site
{
    /**
     * The ID of the site as represented in the DB
     *
     * @var  int
     */
    private $id;

    /**
     * The name of the site as represented in the code
     *
     * @var string
     */
    private $name;

    /**
     * The domain where we accept requests for the site
     *
     * @var string
     */
    private $domain;

    /**
     * The site where we should direct all requests
     *
     * @var string
     */
    private $redirect;

    /**
     * Whether we are enabled
     *
     * @var bool
     */
    private $enabled;

    /**
     * View service
     *
     * @var View
     */
    protected $view;

    /**
     * Route generator service
     *
     * @var Registrar
     */
    protected $registrar;

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
     * Create a new site instance.
     *
     * @param Factory $view
     * @param Registrar $registrar
     * @param UrlGenerator $urlGenerator
     * @param HtmlBuilder $builder
     * @param SiteModelContract $site
     */
    public function __construct(
        Factory $view,
        Registrar $registrar,
        UrlGenerator $urlGenerator,
        HtmlBuilder $builder,
        SiteModelContract $site,
        array $config
    ) {
        $this->view = $view;
        $this->registrar = $registrar;
        $this->urlGenerator = $urlGenerator;
        $this->html = $builder;
        $this->config = $config;

        $this->id = $site->getId();
        $this->name = $site->getName();
        $this->domain = $site->getDomain();
        $this->redirect = $site->getRedirect();
        $this->enabled = $site->isEnabled();
    }

    /*
     * Getters
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getRedirect(): string
    {
        return $this->redirect;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
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
        return $this->getRoutePrefix() . "." . $route;
    }

    public function getSiteSpecificView($view): string
    {
        return $this->getViewPrefix() . "." . $view;
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
        return $this->routePath($this->getRoutePrefix() . ".php");
    }

    /*
     * Functions
     */

    /**
     * Returns wheter or not the given site is valid, and has routes
     *
     * @return bool
     */
    public function hasRoutes(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (!file_exists($this->getRoutesFile())) {
            return false;
        }

        return true;
    }

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
        if ($this->hasRoute($route)) {
            $route = $this->getSiteSpecificRoute($route);
        }
        return $this->urlGenerator->route($route, $parameters, $absolute);
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

    /**
     * Return
     *
     * @param $route
     * @return bool
     */
    protected function hasRoute($route): bool
    {
        return $this->registrar->has($this->getSiteSpecificRoute($route));
    }

    public function registerRoutes()
    {
        $this->registrar->group([
            'domain' => $this->getDomain()
        ], function () {
            if ($this->hasRoutes()) {
                /*
                 * Route returning empty response, needed for the cross-domain login.
                 * Used by the cross domain redirect page, where it includes this route as an image
                 * for all domain and a middleware uses the encrypted session_id as its own session id
                 */
                $this->registrar->get('auth/internal', function () {
                    return response('');
                })->name($this->getRoutePrefix() . 'auth.internal');

                /*
                 * Include the actual route file for the site
                 */
                $this->registrar->group([
                    'as' => $this->getRoutePrefix() . ".",
                    'namespace' => $this->getNameSpace()
                ], $this->getRoutesFile());
            } else {
                /*
                 * If the site is not operational by any reason, all routes catched by a central 503 response
                 */
                $this->registrar->get('{all?}', 'Main\MainController@disabled')->where('all', '.*');
            }
        });
    }

    /*
     * Configuration
     */

    protected function getSiteConfig($key)
    {
        return data_get($key, $this->config['sites'][$this->getSlug()]);
    }

    protected function assetPath($path)
    {
        return $this->config['directories']['assets'] . DIRECTORY_SEPARATOR . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    protected function routePath($path)
    {
        return $this->config['directories']['routes'] . DIRECTORY_SEPARATOR . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /*
     * Assets
     */

    private function mix($path): HtmlString
    {
        $manifestFile = $this->assetPath('mix-manifest.json');
        if (! file_exists($manifestFile)) {
            throw new Exception('The Mix manifest does not exist.');
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);

        if (! starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = DIRECTORY_SEPARATOR . $path;
        }

        if (! array_key_exists($path, $manifest)) {
            throw new Exception('No generated asset exists for this site.');
        }

        $path = implode(DIRECTORY_SEPARATOR, Arr::except(explode(DIRECTORY_SEPARATOR, $manifest[$path]), 1));

        return new HtmlString($path);
    }

    public function css(): HtmlString
    {
        return $this->html->style($this->mix($this->getViewPrefix() . "/css/app.css"));
    }

    public function js(): HtmlString
    {
        return $this->html->script($this->mix($this->getViewPrefix() . "/js/app.js"));
    }
}
