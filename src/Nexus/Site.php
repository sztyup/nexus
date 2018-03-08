<?php

namespace Sztyup\Nexus;

use Collective\Html\HtmlBuilder;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Exception;
use Sztyup\Nexus\Contracts\CommonRouteGroup;

class Site
{
    /**
     * The name of the site as represented in the code
     *
     * @var string
     */
    private $name;

    /**
     * Human readable name of the Site
     *
     * @var string
     */
    private $title;

    /**
     * The domain where we accept requests for the site
     *
     * @var array
     */
    private $domains;

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
     * @var array The Common Route Group registrars
     */
    protected $commonRegistrars;

    /**
     * Create a new site instance.
     *
     * @param Factory $view
     * @param UrlGenerator $urlGenerator
     * @param HtmlBuilder $builder
     * @param Repository $config
     * @param array $commonRegistrars
     * @param array $domains
     * @param string $name
     * @param string $title
     */
    public function __construct(
        Factory $view,
        UrlGenerator $urlGenerator,
        HtmlBuilder $builder,
        Repository $config,
        array $commonRegistrars,
        array $domains,
        string $name,
        string $title
    ) {
        $this->view = $view;
        $this->urlGenerator = $urlGenerator;
        $this->html = $builder;
        $this->config = $config->get('nexus');
        $this->commonRegistrars = $commonRegistrars;
        $this->domains = $domains;
        $this->name = $name;
        $this->title = $title;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function isEnabled(): bool
    {
        return count($this->domains) > 0;
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

    /**
     * Registers all route for this site
     * @param Registrar $router
     */
    public function registerRoutes(Registrar $router)
    {
        $router->nexus([
            'site' => $this
        ], function () use ($router) {
            if ($this->hasRoutes()) {
                /*
                 * Route returning empty response, needed for the cross-domain login.
                 * Used by the cross domain redirect page, where it includes this route as an image
                 * for all domain and a middleware uses the encrypted session_id as its own session id
                 */
                $router->get('auth/internal', function () {
                    return new Response();
                })->name($this->getRoutePrefix() . '.auth.internal');

                /*
                 * Register common route groups to prevent duplications
                 */
                /** @var CommonRouteGroup $registrar */
                foreach ($this->commonRegistrars as $registrar) {
                    $registrar->register($router, $this);
                }

                /*
                 * Include the actual route file for the site
                 */
                $router->group([
                    'as' => $this->getRoutePrefix() . ".",
                    'namespace' => $this->getNameSpace()
                ], $this->getRoutesFile());
            } else {
                /*
                 * If the site is not operational by any reason, all routes catched by a central 503 response
                 */
                if ($this->getSiteConfig('disabled_route')) {
                    $router->get('{all?}', $this->getSiteConfig('disabled_route'))->where('all', '.*');
                } elseif (class_exists($this->getNameSpace() . '\\Main\\MainController')) {
                    $router->get('{all?}', 'Main\\MainController@disabled')->where('all', '.*');
                } else {
                    $router->get('{all?}', function () {
                        return response('', 503);
                    })->where('all', '.*');
                }
            }
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
     * @throws Exception
     */
    private function mix($path): HtmlString
    {
        $manifestFile = $this->config['directories']['assets'] . DIRECTORY_SEPARATOR . 'mix-manifest.json';
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

    /**
     * @return HtmlString
     * @throws Exception
     */
    public function css(): HtmlString
    {
        return $this->html->style(
            $this->mix($this->getViewPrefix() . "/css/app.css")
        );
    }

    /**
     * @return HtmlString
     * @throws Exception
     */
    public function js(): HtmlString
    {
        return $this->html->script(
            $this->mix($this->getViewPrefix() . "/js/app.js")
        );
    }
}
