<?php

namespace Sztyup\Multisite;

use Collective\Html\HtmlBuilder;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Exception;

class Site
{
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
     * The current request object
     *
     * @var Request
     */
    protected $request;

    /**
     * The model for the Site we are currently in
     */
    protected $siteModel;

    /**
     * Create a new site instance.
     *
     * @param Factory $view
     * @param Registrar $registrar
     * @param UrlGenerator $urlGenerator
     * @param HtmlBuilder $builder
     * @param Request $request
     * @param mixed $site
     * @internal param \Illuminate\Foundation\Application $app
     */
    public function __construct(Factory $view, Registrar $registrar, UrlGenerator $urlGenerator, HtmlBuilder $builder, Request $request, $site)
    {
        $this->view = $view;
        $this->registrar = $registrar;
        $this->urlGenerator = $urlGenerator;
        $this->html = $builder;
        $this->request = $request;
        $this->siteModel = $site;
    }

    /*
     * Model abstractors
     */
    public function getId(): int
    {
        return $this->siteModel->id;
    }

    public function getName(): string
    {
        return $this->siteModel->slug;
    }

    public function getDomain(): string
    {
        return $this->siteModel->domain;
    }

    public function getTrackerId(): string
    {
        return $this->siteModel->tracker_id ?? '';
    }

    public function getRedirect(): string
    {
        return $this->siteModel->redirect ?? '';
    }

    public function isEnabled(): bool
    {
        return $this->siteModel->enabled;
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
        return config('multisite.directories.routes') . DIRECTORY_SEPARATOR . $this->getRoutePrefix() . ".php";
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
        if (!$this->isEnabled())
            return false;

        if (!file_exists($this->getRoutesFile()))
            return false;

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
            return $this->urlGenerator->route($this->getSiteSpecificRoute($route), $parameters, $absolute);
        } else {
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
            return $this->view->make($this->getSiteSpecificView($view), $data, $mergeData);
        } else {
            return $this->view->make($view, $data, $mergeData);
        }
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

    /*
     * Assets
     */

    private function mix($path): HtmlString
    {
        $manifestFile = config('multisite.directories.assets') . DIRECTORY_SEPARATOR . 'mix-manifest.json';
        if (! file_exists($manifestFile)) {
            throw new Exception('The Mix manifest does not exist.');
        }

        $manifest = json_decode(
            file_get_contents($manifestFile), true
        );

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

    /*
     * Getters
     */

    public function __get($name)
    {
        if($name == "id") {
            return $this->getId();
        }
        return null;
    }
}