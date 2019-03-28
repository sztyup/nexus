<?php

namespace Sztyup\Nexus\Contracts;

interface SiteModelContract
{
    /**
     * Get the name of the Site this model refers to
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the domain where the site should be served
     *
     * @return mixed
     */
    public function getDomain(): string;

    /**
     * Whether the given sitemodel is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    public function isPrimary(): bool;

    /**
     * Get all extra data stored about the site
     *
     * @param $key
     * @return mixed
     */
    public function getExtraData($key);
}
