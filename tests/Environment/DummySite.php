<?php

namespace Sztyup\Nexus\Tests\Environment;

use Sztyup\Nexus\Contracts\SiteModelContract;

class DummySite implements SiteModelContract
{
    private $name;
    private $domain;

    private $primary;
    private $disabled;

    public function __construct($name, $domain, $primary = false, $disabled = false)
    {
        $this->name = $name;
        $this->domain = $domain;
        $this->primary = $primary;
        $this->disabled = $disabled;
    }

    /**
     * Get the name of the Site this model refers to
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the domain where the site should be served
     *
     * @return mixed
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Whether the given sitemodel is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return !$this->disabled;
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

    public function isPrimary(): bool
    {
        return $this->primary;
    }
}
