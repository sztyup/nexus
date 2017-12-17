<?php

use Sztyup\Nexus\SiteManager;

function nexus(): SiteManager
{
    return app('nexus');
}

function resource($path)
{
    return $path;
}