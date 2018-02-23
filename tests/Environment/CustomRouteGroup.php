<?php

namespace Tests\Environment;

use Illuminate\Contracts\Routing\Registrar;
use Sztyup\Nexus\Contracts\CommonRouteGroup;
use Sztyup\Nexus\Site;

class CustomRouteGroup implements CommonRouteGroup
{
    public function register(Registrar $registrar, Site $site)
    {
        $registrar->get('custom', function () {
            return response('custom.bar');
        });
    }
}
