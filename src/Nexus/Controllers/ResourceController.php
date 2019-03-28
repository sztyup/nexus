<?php

namespace Sztyup\Nexus\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
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

    public function internalAuth()
    {
        return $this->responseFactory->make();
    }

    /**
     * @param $path
     *
     * @return \Illuminate\Http\Response|null|BinaryFileResponse
     * @throws \Sztyup\Nexus\Exceptions\NexusException
     */
    public function fonts($path)
    {
        $resource = $this->resource('fonts' . DIRECTORY_SEPARATOR . $path);

        if ($resource) {
            return $resource;
        }

        $asset = $this->asset('fonts' . DIRECTORY_SEPARATOR . $path);

        if ($asset) {
            return $asset;
        }

        return $this->responseFactory->make('', 404);
    }

    /**
     * @param $path
     *
     * @return \Illuminate\Http\Response|null|BinaryFileResponse
     * @throws \Sztyup\Nexus\Exceptions\NexusException
     */
    public function image($path)
    {
        $mime = null;

        if (Str::endsWith($path, '.svg')) {
            $mime = 'image/svg+xml';
        }

        $image = $this->resource('img' . DIRECTORY_SEPARATOR . $path, $mime);

        if ($image) {
            return $image;
        }

        $image = $this->asset('img' . DIRECTORY_SEPARATOR . $path, $mime);

        if ($image) {
            return $image;
        }

        return $this->responseFactory->make('', 404);
    }

    /**
     * @param $path
     *
     * @return \Illuminate\Http\Response|null|BinaryFileResponse
     * @throws \Sztyup\Nexus\Exceptions\NexusException
     */
    public function js($path)
    {
        $js = $this->asset('js' . DIRECTORY_SEPARATOR . $path, 'text/javascript');

        if ($js) {
            return $js;
        }

        return $this->responseFactory->make('', 404);
    }

    /**
     * @param $path
     *
     * @return \Illuminate\Http\Response|null|BinaryFileResponse
     * @throws \Sztyup\Nexus\Exceptions\NexusException
     */
    public function css($path)
    {
        $css = $this->asset('css' . DIRECTORY_SEPARATOR . $path, 'text/css');

        if ($css) {
            return $css;
        }

        return $this->responseFactory->make('', 404);
    }

    /**
     * @param $path
     * @param null $mime
     *
     * @return null|BinaryFileResponse
     * @throws \Sztyup\Nexus\Exceptions\NexusException
     */
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

        return null;
    }

    /**
     * @param $path
     * @param null $mime
     *
     * @return null|BinaryFileResponse
     * @throws \Sztyup\Nexus\Exceptions\NexusException
     */
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

        return null;
    }

    /**
     * @param $path
     * @param null $mime
     *
     * @return null|BinaryFileResponse
     * @throws \Sztyup\Nexus\Exceptions\NexusException
     */
    public function asset($path, $mime = null)
    {
        $site = $this->siteManager->current();

        if ($site) {
            $file = $site->assetPath($path);

            if ($this->filesystem->exists($file)) {
                return $this->fileResponse($file, $mime);
            }
        }

        $file = storage_path('assets' . DIRECTORY_SEPARATOR . $path);
        if ($this->filesystem->exists($file)) {
            return $this->fileResponse($file, $mime);
        }

        return null;
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
