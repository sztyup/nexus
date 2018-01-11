<?php

namespace Sztyup\Nexus\Controllers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Sztyup\Nexus\Site;
use Sztyup\Nexus\SiteManager;

class ResourceController extends Controller
{
    /** @var Site */
    private $site;

    /** @var FilesystemAdapter */
    private $filesystem;

    public function __construct(SiteManager $siteManager, FilesystemAdapter $filesystem)
    {
        $this->site = $siteManager->current();
        $this->filesystem = $filesystem;
    }

    public function image($path)
    {
        if (is_null($this->site)) {
            return new Response('', 404);
        }

        $file = resource_path("sites" . DIRECTORY_SEPARATOR . Str::lower($this->site->getSlug()) . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . $path);

        if ($this->filesystem->exists($file)) {
            return $this->filesystem->response($file);
        }

        $file = storage_path("app" . DIRECTORY_SEPARATOR . Str::lower($this->site->getSlug()) . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . $path);

        if ($this->filesystem->exists($file)) {
            return $this->filesystem->response($file);
        }

        $file = storage_path("assets" . DIRECTORY_SEPARATOR . Str::lower($this->site->getSlug()) . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . $path);

        if ($this->filesystem->exists($file)) {
            return $this->filesystem->response($file);
        }

        return new Response('', 404);
    }

    public function js($path)
    {
        return $this->asset("js" . DIRECTORY_SEPARATOR . $path, "application/javascript");
    }

    public function css($path)
    {
        return $this->asset("css" . DIRECTORY_SEPARATOR . $path, "text/css");
    }

    private function asset($path, $mime = "text/plain")
    {
        $file = storage_path("assets" . DIRECTORY_SEPARATOR . Str::lower($this->site->getSlug()) . DIRECTORY_SEPARATOR . $path);
        if ($this->filesystem->exists($file)) {
            return $this->filesystem->response($file, $mime);
        }

        $file = storage_path("assets" . DIRECTORY_SEPARATOR . $path);
        if ($this->filesystem->exists($file)) {
            return $this->filesystem->response($file);
        }

        return new Response('', 404);
    }
}
