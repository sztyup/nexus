<?php

namespace Sztyup\Nexus\Tests\Environment;;

use Illuminate\Support\Collection;
use Sztyup\Nexus\Contracts\SiteModelContract;
use Sztyup\Nexus\Contracts\SiteRepositoryContract;

class ModelRepo implements SiteRepositoryContract
{
    /**
     * Returns all site models, implementing SiteModelContract
     *
     * @return Collection|SiteModelContract[]
     */
    public function getAll(): Collection
    {
        return new Collection([
            new DummySite('foo', 'foo.com')
        ]);
    }

    /**
     * Returns Models for a specified site
     *
     * @param string $slug
     * @return SiteModelContract[]|Collection
     */
    public function getBySlug(string $slug): Collection
    {
        if ($slug == 'foo') {
            return Collection::make([
                new DummySite('foo', 'foo.com'),
            ]);
        }

        if ($slug == 'bar') {
            return Collection::make([
                new DummySite('bar', 'bar.com')
            ]);
        }

        if ($slug == 'foobar') {
            return Collection::make([
                new DummySite('foobar', 'bob.com'),
                new DummySite('foobar', 'alice.com')
            ]);
        }

        return Collection::make();
    }
}
