<?php

namespace Sztyup\Nexus\Contracts;

use Illuminate\Contracts\Routing\Registrar;
use Sztyup\Nexus\Site;

interface CommonRouteGroup
{
    public function register(Registrar $registrar, Site $site);
}
