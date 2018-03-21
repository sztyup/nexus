<?php

namespace Sztyup\Nexus\Doctrine;

use Sztyup\Nexus\Contracts\SiteModelContract;

class SiteEntity implements SiteModelContract
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $domain;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $enabled;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $extraData;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the domain
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Set the domain
     *
     * @param string $domain
     * @return Site
     */
    public function setDomain(string $domain): Site
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Check if the current site-domain pairing is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set if the current site-domain pairing is enabled
     *
     * @param bool $enabled
     * @return Site
     */
    public function setEnabled(bool $enabled): Site
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get the Site name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the Site name
     *
     * @param string $name
     * @return Site
     */
    public function setName(string $name): Site
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get all extra data stored about the site
     *
     * @param $key
     * @return mixed
     */
    public function getExtraData($key)
    {
        return $this->extraData[$key] ?? null;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setExtraData($key, $value)
    {
        $this->extraData[$key] = $value;
    }
}