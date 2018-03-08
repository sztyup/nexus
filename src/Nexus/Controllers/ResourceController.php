<?php

namespace Sztyup\Nexus\Controllers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Routing\Controller;
use Sztyup\Nexus\SiteManager;

class ResourceController extends Controller
{
    /** @var SiteManager */
    private $siteManager;

    /** @var Filesystem */
    private $filesystem;

    /** @var ResponseFactory */
    private $responseFactory;

    public function __construct(
        SiteManager $siteManager,
        Filesystem $filesystem,
        ResponseFactory $responseFactory
    ) {
        $this->siteManager = $siteManager;
        $this->filesystem = $filesystem;
        $this->responseFactory = $responseFactory;
    }

    public function fonts($path)
    {
        return $this->resource("fonts" . DIRECTORY_SEPARATOR . $path);
    }

    public function image($path)
    {
        return $this->resource("img" . DIRECTORY_SEPARATOR . $path);
    }

    public function js($path)
    {
        return $this->asset("js" . DIRECTORY_SEPARATOR . $path);
    }

    public function css($path)
    {
        return $this->asset("css" . DIRECTORY_SEPARATOR . $path);
    }

    private function resource($path)
    {
        $site = $this->siteManager->current();

        if ($site) {
            $file = $site->resourcePath($path);

            if ($this->filesystem->exists($file)) {
                return $this->responseFactory->file($file);
            }
        }

        $file = resource_path($path);
        if ($this->filesystem->exists($file)) {
            return $this->responseFactory->file($file);
        }

        return $this->responseFactory->make('', 404);
    }

    public function storage($path)
    {
        $site = $this->siteManager->current();

        if ($site) {
            $file = $site->storagePath($path);

            if ($this->filesystem->exists($file)) {
                return $this->responseFactory->file($file);
            }
        }

        $file = storage_path('app' . DIRECTORY_SEPARATOR . $path);
        if ($this->filesystem->exists($file)) {
            return $this->responseFactory->file($file);
        }

        return $this->responseFactory->make('', 404);
    }

    public function asset($path)
    {
        $site = $this->siteManager->current();

        if ($site) {
            $file = $site->assetPath($path);

            if ($this->filesystem->exists($file)) {
                return $this->responseFactory->file($file);
            }
        }

        $file = storage_path("assets" . DIRECTORY_SEPARATOR . $path);
        if ($this->filesystem->exists($file)) {
            return $this->responseFactory->file($file);
        }

        return $this->responseFactory->make('', 404);
    }
}
