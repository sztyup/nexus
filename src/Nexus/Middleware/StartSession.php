<?php

namespace Sztyup\Nexus\Middleware;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Session\Session;
use Illuminate\Session\Middleware\StartSession as Base;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;

class StartSession extends Base
{
    protected $encrypter;

    public function __construct(SessionManager $manager, Encrypter $encrypter)
    {
        parent::__construct($manager);

        $this->encrypter = $encrypter;
    }

    /**
     * Implements the receiveing end of the cross-domain login
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Session
     */
    public function getSession(Request $request)
    {
        /** @var Session $session */
        $session = $this->manager->driver();

        if ($request->has("s_code")) {
            $session->setId(
                $this->encrypter->decrypt($request->get("s_code"))
            );
        } else {
            $session->setId(
                $request->cookies->get(
                    $session->getName()
                )
            );
        }

        return $session;
    }
}
