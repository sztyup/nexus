<?php

namespace Sztyup\Nexus\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Str;

trait InjectorMiddleware
{
    protected function shouldInject($response)
    {
        // Not a normal response, probably a redirect
        if (!$response instanceof Response) {
            return false;
        }

        // Explicit redirect response
        if ($response->isRedirection()) {
            return false;
        }

        // Only inject html responses
        if (!Str::contains($response->headers->get('Content-Type'), 'html')) {
            return false;
        }

        return true;
    }
}
