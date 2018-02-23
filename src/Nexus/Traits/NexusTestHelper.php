<?php

namespace Sztyup\Nexus\Traits;

use Sztyup\Nexus\SiteManager;

trait NexusTestHelper
{
    public function url($slug, $uri = '/', $number = 1)
    {
        if (!$this->app->bound(SiteManager::class)) {
            return null;
        }

        /** @var SiteManager $manager */
        $manager = $this->app->make(SiteManager::class);

        $site = $manager->getBySlug($slug);

        if (is_null($site)) {
            return null;
        }

        if (!$site->isEnabled()) {
            return null;
        }

        return 'http://' . $site->getDomains()[$number - 1] . $uri;
    }
}
