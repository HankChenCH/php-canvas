<?php

namespace HankChen\Canvas\ResourceManagers;

use HankChen\Canvas\Contracts\DownloaderInterface;

class DefaultDownloader implements DownloaderInterface
{
    public function download($url)
    {
        return file_get_contents($url);
    }
}