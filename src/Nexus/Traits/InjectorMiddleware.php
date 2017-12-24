<?php

namespace Sztyup\Nexus\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Str;

trait InjectorMiddleware
{
    protected function shouldInject($response)
    {
        if (!$response instanceof Response) { // Probably a redirect
            return false;
        }

        // Only inject html responses
        if (!Str::contains($response->headers->get('Content-Type'), 'html')) {
            return false;
        }

        return true;
    }
}
