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
use Sztyup\Nexus\Traits\InjectorMiddleware;

class InjectCrossDomainLogin
{
    use InjectorMiddleware;

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

        if (!$this->shouldInject($response)) {
            return $response;
        }

        // Only inject if authenticated
        if ($this->guard->guest()) {
            return $response;
        }

        // Inject images before the closing body tag
        $response->setContent($this->injectCode($request->session(), $response));

        return $response;
    }

    protected function injectCode(Session $session, Response $response): string
    {
        $content = $this->viewFactory->make('nexus::cdimages', [
            'sites' => $this->siteManager->getEnabledSites()->except(
                $this->siteManager->current()->getId(),
                $this->siteManager->getByDomain(
                    $this->siteManager->getConfig('main_domain')
                )
            ),
            'code' => $this->encrypter->encrypt($session->getId())
        ])->render();

        return Str::replaceFirst("</body>", $content . "\n</body>", $response->getContent());
    }
}
