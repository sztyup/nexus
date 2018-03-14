<?php

namespace Sztyup\Nexus\Tests\Environment;

use Sztyup\Nexus\Contracts\SiteModelContract;

class DummySite implements SiteModelContract
{
    private $name;
    private $domain;

    public function __construct($name, $domain)
    {
        $this->name = $name;
        $this->domain = $domain;
    }

    /**
     * Get the name of the Site this model refers to
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the domain where the site should be served
     *
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
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
}
