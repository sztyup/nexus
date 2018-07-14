<?php

namespace Sztyup\Nexus\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
        return $this->asset("fonts" . DIRECTORY_SEPARATOR . $path);
    }

    public function image($path)
    {
        return $this->resource("img" . DIRECTORY_SEPARATOR . $path);
    }

    public function js($path)
    {
        return $this->asset("js" . DIRECTORY_SEPARATOR . $path, 'text/javascript');
    }

    public function css($path)
    {
        return $this->asset("css" . DIRECTORY_SEPARATOR . $path, 'text/css');
    }

    private function resource($path, $mime = null)
    {
        $site = $this->siteManager->current();

        if ($site) {
            $file = $site->resourcePath($path);

            if ($this->filesystem->exists($file)) {
                return $this->fileResponse($file, $mime);
            }
        }

        $file = resource_path($path);
        if ($this->filesystem->exists($file)) {
            return $this->fileResponse($file, $mime);
        }

        return $this->responseFactory->make('', 404);
    }

    public function storage($path, $mime = null)
    {
        $site = $this->siteManager->current();

        if ($site) {
            $file = $site->storagePath($path);

            if ($this->filesystem->exists($file)) {
                return $this->fileResponse($file, $mime);
            }
        }

        $file = storage_path('app' . DIRECTORY_SEPARATOR . $path);
        if ($this->filesystem->exists($file)) {
            return $this->fileResponse($file, $mime);
        }

        return $this->responseFactory->make('', 404);
    }

    public function asset($path, $mime = null)
    {
        $site = $this->siteManager->current();

        if ($site) {
            $file = $site->assetPath($path);

            if ($this->filesystem->exists($file)) {
                return $this->fileResponse($file, $mime);
            }
        }

        $file = storage_path("assets" . DIRECTORY_SEPARATOR . $path);
        if ($this->filesystem->exists($file)) {
            return $this->fileResponse($file, $mime);
        }

        return $this->responseFactory->make('', 404);
    }

    /**
     * @param $file
     * @param string|null $mime
     * @return BinaryFileResponse
     */
    protected function fileResponse($file, $mime = null)
    {
        /** @var BinaryFileResponse $response */
        $response = $this->responseFactory->file($file);

        if ($mime) {
            $response->headers->set('Content-Type', $mime);
        }

        return $response;
    }
}
