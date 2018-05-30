<?php

namespace Sztyup\Nexus\Tests\Environment;

use Sztyup\Nexus\CommonRouteGroup;

class CustomRouteGroup extends CommonRouteGroup
{
    public function register()
    {
        $this->registrar->get('custom', function () {
            return response('custom.bar');
        });
    }
}
