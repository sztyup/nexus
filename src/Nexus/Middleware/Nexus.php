<?php

namespace Sztyup\Nexus\Middleware;

use Sztyup\Nexus\SiteManager;
use Illuminate\Http\Request;
use Closure;

class Nexus
{
    /** @var SiteManager */
    protected $siteManager;

    /**
     * Nexus constructor.
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager)
    {
        $this->siteManager = $manager;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->siteManager->handleRequest($request);

        $response = $next($request);

        $this->siteManager->handleResponse($response);

        return $response;
    }
}
