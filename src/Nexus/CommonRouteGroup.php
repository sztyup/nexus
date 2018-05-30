<?php

namespace Sztyup\Nexus;

use Illuminate\Contracts\Routing\Registrar;

abstract class CommonRouteGroup
{
    /** @var Registrar */
    protected $registrar;

    /** @var Site */
    protected $site;

    public function __construct(Registrar $registrar)
    {
        $this->registrar = $registrar;
    }

    public function setSite(Site $site)
    {
        $this->site = $site;

        return $this;
    }

    abstract public function register();
}
