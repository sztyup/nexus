<?php

namespace Sztyup\Nexus;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class ImpersonationManager
{
    /** @var Authenticatable */
    protected $impersonated;

    /** @var AuthManager */
    protected $authManager;

    /** @var Request */
    protected $request;

    const SESSION_KEY = '_nexus_impersonate';

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * @param Request $request
     */
    public function handleRequest(Request $request)
    {
        $this->request = $request;

        if (!$this->isImpersonating()) {
            return;
        }

        $guard = $this->authManager->guard();

        if (!$guard instanceof SessionGuard) {
            throw new \LogicException('Impersonating only supported in session based authentication');
        }

        if ($guard->guest()) {
            // Make sure unauthenticad users cant impersonate (eg. expired oauth)
            if ($this->isImpersonating()) {
                $this->stopImpersonating();
            }

            return;
        }

        $this->impersonated = $guard->getProvider()->retrieveById(
            $request->session()->get(self::SESSION_KEY)
        );

        $guard->setUser($this->impersonated);
    }

    /**
     * @param Authenticatable $user
     */
    public function impersonate(Authenticatable $user)
    {
        $this->impersonated = $user;

        $this->request->session()->put(self::SESSION_KEY, $user->getAuthIdentifier());
    }

    public function stopImpersonating()
    {
        $this->impersonated = null;

        $this->request->session()->forget(self::SESSION_KEY);
    }

    /**
     * @return bool
     */
    public function isImpersonating()
    {
        return $this->request->session()->has(self::SESSION_KEY);
    }

    /**
     * @return Authenticatable|null
     */
    public function getImpersonatedUser()
    {
        return $this->impersonated;
    }
}