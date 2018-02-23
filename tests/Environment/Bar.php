<?php

namespace Tests\Environment;

use Sztyup\Nexus\Contracts\SiteModelContract;

class Bar implements SiteModelContract
{
    /**
     * Get the name of the Site this model refers to
     *
     * @return string
     */
    public function getName()
    {
        return 'bar';
    }

    /**
     * Get the domain where the site should be served
     *
     * @return mixed
     */
    public function getDomain()
    {
        return 'bar.com';
    }

    /**
     * Get all extra data stored about the site
     *
     * @param $key
     * @return mixed
     */
    public function getExtraData($key)
    {
        return null;
    }

    /**
     * Whether the given sitemodel is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }
}
