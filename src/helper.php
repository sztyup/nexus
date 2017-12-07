<?php

use Sztyup\Multisite\SiteManager;

function multisite(): SiteManager
{
    return app('multisite');
}