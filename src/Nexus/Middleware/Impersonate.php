<?php

namespace Sztyup\Nexus\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Sztyup\Nexus\Traits\InjectorMiddleware;

class Impersonate
{
    use InjectorMiddleware;

    protected $guard;

    protected $viewFactory;

    public function __construct(Guard $guard, Factory $factory)
    {
        $this->guard = $guard;
        $this->viewFactory = $factory;
    }

    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('_nexus_impersonate')) {
            if ($request->user() == null) {
                // Make sure unauthenticad users cant impersonate (eg. expired oauth)
                if ($request->session()->has('_nexus_impersonate')) {
                    $request->session()->remove("_nexus_impersonate");
                }
            } else {
                $this->guard->onceUsingId($request->session()->get('_nexus_impersonate'));

                /** @var Response $response */
                $response = $next($request);

                if ($this->shouldInject($response)) {
                    $this->injectImpersonateBar($response);
                }

                return $response;
            }
        }

        return $next($request);
    }

    protected function injectImpersonateBar(Response &$response)
    {
        $impersonate = $this->viewFactory->make('partials.impersonate', [
            'user' => $this->guard->user()
        ])->render();

        $content = preg_replace("/<body([^>])*>/", "\\0\n$impersonate", $response->getContent());

        $response->setContent($content);
    }
}
