<?php

namespace Sztyup\Nexus\Middleware;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Sztyup\Nexus\SiteManager;
use Closure;

class InjectCrossDomainLogin
{
    protected $siteManager;

    protected $viewFactory;

    protected $guard;

    protected $encrypter;

    public function __construct(SiteManager $siteManager, Factory $viewFactory, Guard $guard, Encrypter $encrypter)
    {
        $this->siteManager = $siteManager;
        $this->viewFactory = $viewFactory;
        $this->guard = $guard;
        $this->encrypter = $encrypter;
    }

    public function handle(Request $request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        // Only inject if authenticated
        if ($this->guard->guest()) {
            return $response;
        }

        // Only inject html responses
        if (!Str::contains($response->headers->get('Content-Type'), 'html')) {
            return $response;
        }

        // Inject images before the closing body tag
        $response->setContent(
            Str::replaceFirst("</body>", $this->getInjectedCode($request->getSession()) . "</body>", $response->getContent())
        );

        return $response;
    }

    protected function getInjectedCode(Session $session): string
    {
        return $this->viewFactory->make('nexus::cdimages', [
            'sites' => $this->siteManager->all(),
            'code' => $this->encrypter->encrypt($session->getId())
        ])->render();
    }
}
