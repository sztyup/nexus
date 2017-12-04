<?php

namespace Sztyup\Multisite\Exceptions;

class SiteNotFoundException extends MultiSiteException
{
    public function __construct($site)
    {
        parent::__construct("Site '$site' not found");
    }
}