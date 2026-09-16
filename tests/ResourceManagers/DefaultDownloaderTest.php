<?php

namespace HankChen\Canvas\Tests\ResourceManagers;

use HankChen\Canvas\ResourceManagers\DefaultDownloader;
use HankChen\Canvas\Tests\Support\CanvasTestCase;

class DefaultDownloaderTest extends CanvasTestCase
{
    public function testDownloadReadsFileContent()
    {
        $path = sys_get_temp_dir() . '/php-canvas-downloader-fixture-' . uniqid() . '.txt';
        file_put_contents($path, 'canvas-downloader-content');

        $content = (new DefaultDownloader())->download('file://' . $path);

        $this->assertSame('canvas-downloader-content', $content);

        @unlink($path);
    }
}
