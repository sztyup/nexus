<?php

namespace Sztyup\Nexus\Middleware;

use Closure;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Sztyup\Nexus\ImpersonationManager;
use Sztyup\Nexus\Traits\InjectorMiddleware;

class Impersonate
{
    use InjectorMiddleware;

    const SESSION_KEY = '_nexus_impersonate';

    /** @var Factory */
    protected $viewFactory;

    protected $manager;

    public function __construct(Factory $factory, ImpersonationManager $impersonationManager)
    {
        $this->viewFactory = $factory;
        $this->manager = $impersonationManager;
    }

    public function handle(Request $request, Closure $next)
    {
        $this->manager->handleRequest($request);

        /** @var Response $response */
        $response = $next($request);

        if ($this->manager->isImpersonating() && $this->shouldInject($response)) {
            $this->injectImpersonateBar($response);
        }

        return $response;
    }

    protected function injectImpersonateBar(Response $response)
    {
        $view = $this->viewFactory->make('nexus::impersonate', [
            'user' => $this->manager->getImpersonatedUser()
        ])->render();

        $content = preg_replace("/<body([^>])*>/", "\\0\n$view", $response->getContent());

        $response->setContent($content);
    }
}
