<?php

namespace Sztyup\Nexus;

interface SiteModelContract
{
    public function getId();

    public function getName();

    public function getDomain();

    public function getTagManagerId();

    public function getRedirect();

    public function isEnabled();
}
