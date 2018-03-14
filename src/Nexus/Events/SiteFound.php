<?php

namespace Sztyup\Nexus\Events;

use Sztyup\Nexus\Site;

class SiteFound
{
    protected $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    /**
     * @return Site
     */
    public function getSite(): Site
    {
        return $this->site;
    }
}
