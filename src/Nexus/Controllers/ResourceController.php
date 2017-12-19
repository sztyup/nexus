<?php

namespace Sztyup\Nexus\Controllers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Sztyup\Nexus\SiteManager;

class ResourceController extends Controller
{
    private $site;

    private $filesystem;

    public function __construct(SiteManager $siteManager, Filesystem $filesystem)
    {
        $this->site = $siteManager->current();
        $this->filesystem = $filesystem;
    }

    public function image($path)
    {
        $file = resource_path("sites" . DIRECTORY_SEPARATOR . Str::lower($this->site->getSlug()) . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . $path);

        if ($this->filesystem->exists($file)) {
            return $this->response($file);
        }

        $file = storage_path("app" . DIRECTORY_SEPARATOR . Str::lower($this->site->getSlug()) . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . $path);

        if ($this->filesystem->exists($file)) {
            return $this->response($file);
        }

        $file = storage_path("assets" . DIRECTORY_SEPARATOR . Str::lower($this->site->getSlug()) . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . $path);

        if ($this->filesystem->exists($file)) {
            return $this->response($file);
        }

        $empty = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=";

        return $this->response($empty, "image/png", "404.png", 404);
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
            return $this->response($file, $mime);
        }

        $file = storage_path("assets" . DIRECTORY_SEPARATOR . $path);
        if ($this->filesystem->exists($file)) {
            return $this->response($file, $mime);
        }

        return new Response('', 404);
    }

    private function response($data, $mime = null, $name = null, $statusCode = 200)
    {
        if ($mime == null) {
            $mime = $this->filesystem->mimeType($data);
        }
        if ($name == null) {
            $name = Arr::last(explode(DIRECTORY_SEPARATOR, $data));

            $data = $this->filesystem->get($data);
        }

        return new Response($data, $statusCode, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $name . '"',
        ]);
    }
}
