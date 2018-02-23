<?php

namespace Sztyup\Nexus\Contracts;

use Illuminate\Support\Collection;

interface SiteRepositoryContract
{
    /**
     * Returns all site models, implementing SiteModelContract
     *
     * @return Collection|SiteModelContract[]
     */
    public function getAll(): Collection;

    /**
     * Returns Models for a specified site
     *
     * @param string $slug
     * @return SiteModelContract[]|Collection
     */
    public function getBySlug(string $slug): Collection;
}
