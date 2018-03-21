<?php

namespace Sztyup\Nexus\Doctrine;

use Illuminate\Support\Collection;
use Sztyup\Nexus\Contracts\SiteModelContract;
use Sztyup\Nexus\Contracts\SiteRepositoryContract;

class SiteRepository implements SiteRepositoryContract
{
    protected $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

    public function getAll(): Collection
    {
        Collection::make(
            $this->em->getRepository(SiteEntity::class)->findAll()
        );
    }

    public function getBySlug(string $slug): Collection
    {
        Collection::make(
            $this->em->getRepository(SiteEntity::class)->findBy([
                'name' => $slug
            ])
        );
    }

}
