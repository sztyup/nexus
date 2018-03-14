<?php

namespace Sztyup\Nexus\Tests\Environment;;

use Illuminate\Support\Collection;
use Sztyup\Nexus\Contracts\SiteModelContract;
use Sztyup\Nexus\Contracts\SiteRepositoryContract;

class EmptyModelRepo implements SiteRepositoryContract
{
    /**
     * Returns all site models, implementing SiteModelContract
     *
     * @return Collection|SiteModelContract[]
     */
    public function getAll(): Collection
    {
        return new Collection();
    }

    /**
     * Returns Models for a specified site
     *
     * @param string $slug
     * @return SiteModelContract[]|Collection
     */
    public function getBySlug(string $slug): Collection
    {
        return new Collection();
    }
}
