<?php

namespace Sztyup\Nexus\Traits;

use Sztyup\Nexus\SiteManager;

trait NexusTestHelper
{
    public function url($slug, $uri = '/')
    {
        if (!$this->app->bound(SiteManager::class)) {
            return null;
        }

        /** @var SiteManager $manager */
        $manager = $this->app->make(SiteManager::class);

        $site = $manager->getBySlug($slug);

        if (!$site->isEnabled()) {
            return null;
        }

        return 'http://' . $site->getDomains()[0] . $uri;
    }
}
