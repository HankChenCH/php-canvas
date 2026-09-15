<?php

namespace HankChen\Canvas\Tests\Layer;

use Exception;
use HankChen\Canvas\Layer\ImageLayer;
use HankChen\Canvas\Tests\Support\CanvasTestCase;
use HankChen\Canvas\Tests\Support\FakeDownloader;

class ImageLayerTest extends CanvasTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 清掉远程图片缓存目录，保证每条用例都真正执行下载与建目录逻辑
        $this->removeDir($this->cacheDir('img_layers'));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->cacheDir('img_layers'));

        parent::tearDown();
    }

    private function localPng(string $color = '#ff0000', int $size = 8): string
    {
        $path = sys_get_temp_dir() . '/php-canvas-test-img-' . uniqid() . '.png';
        file_put_contents($path, $this->pngBytes($size, $size, $color));

        return $path;
    }

    public function testRenderLocalImageFittedIntoContentBox()
    {
        $path = $this->localPng('#ff0000');

        $layer = ImageLayer::make(20, 20, '#0000ff')->setImage($path);
        $image = $layer->render();

        $this->assertSame(20, $image->getWidth());
        $this->assertSame(20, $image->getHeight());
        // 源图被 fit 进内容盒后铺满整层
        $this->assertPixelSame([255, 0, 0], $image, 10, 10);
    }

    public function testEmptyImageValueIsIgnored()
    {
        $layer = ImageLayer::make(10, 10, '#00ff00');

        $this->assertSame($layer, $layer->setImage(''));
        $this->assertSame($layer, $layer->setImage(null));

        // 未设置图像时渲染出纯背景层
        $this->assertPixelSame([0, 255, 0], $layer->render(), 5, 5);
        $this->assertNull($layer->graph()['data']['value']);
    }

    public function testRemoteImageDownloadsAndCachesToTmp()
    {
        $content = $this->pngBytes(10, 10, '#ff0000');
        $downloader = new FakeDownloader($content);

        $layer = ImageLayer::make(20, 20, '#0000ff');
        $layer->setDownloader($downloader);

        $url = 'http://example.com/canvas-test-' . uniqid() . '.png';
        $layer->setImage($url);

        // 下载内容落盘到 sys_get_temp_dir()/canvas/img_layers/
        $cached = $this->cacheDir('img_layers') . DIRECTORY_SEPARATOR . basename($url);
        $this->assertFileExists($cached);
        $this->assertSame($content, file_get_contents($cached));

        // 渲染时从缓存文件读取并铺满内容盒
        $this->assertPixelSame([255, 0, 0], $layer->render(), 10, 10);
    }

    public function testRemoteImageIsCachedAcrossLayers()
    {
        $downloader = new FakeDownloader($this->pngBytes(4, 4, '#ff0000'));

        $url = 'http://example.com/canvas-test-' . uniqid() . '.png';

        $first = ImageLayer::make(10, 10)->setDownloader($downloader);
        $first->setImage($url);

        // 第二个图层加载同一 URL 时命中缓存，不再触发下载
        $second = ImageLayer::make(10, 10)->setDownloader($downloader);
        $second->setImage($url);

        $this->assertCount(1, $downloader->calls);
        $this->assertSame([$url], $downloader->calls);
    }

    public function testHealsLegacyCacheDirWithoutExecuteBit()
    {
        // 历史缺陷以 0644 建缓存子目录（缺少执行位，文件写不进去），升级后应自动修复
        $base = $this->cacheDir('');
        $this->removeDir($base);
        mkdir($base, 0755, true);

        $dir = $this->cacheDir('img_layers');
        mkdir($dir, 0644);

        $url = 'http://example.com/canvas-test-' . uniqid() . '.png';
        $layer = ImageLayer::make(10, 10)
            ->setDownloader(new FakeDownloader($this->pngBytes(4, 4, '#ff0000')));

        $layer->setImage($url);

        $this->assertFileExists($dir . DIRECTORY_SEPARATOR . basename($url));
    }

    public function testHealsLegacyCanvasDirWithoutExecuteBit()
    {
        // 旧版递归 mkdir 会先把 canvas 父目录也建成 0644，导致子目录创建失败，升级后应自动修复
        $base = $this->cacheDir('');
        $this->removeDir($base);
        mkdir($base, 0644, true);

        $url = 'http://example.com/canvas-test-' . uniqid() . '.png';
        $layer = ImageLayer::make(10, 10)
            ->setDownloader(new FakeDownloader($this->pngBytes(4, 4, '#ff0000')));

        $layer->setImage($url);

        $this->assertFileExists(
            $this->cacheDir('img_layers') . DIRECTORY_SEPARATOR . basename($url)
        );
    }

    public function testDownloadFailureThrows()
    {
        $layer = ImageLayer::make(10, 10);
        $layer->setDownloader(new FakeDownloader(false));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('could not get remote file');

        $layer->setImage('http://example.com/canvas-test-' . uniqid() . '.png');
    }

    public function testGraphKeepsRawValueAndStaticValueType()
    {
        $url = 'http://example.com/canvas-test-' . uniqid() . '.png';
        $layer = ImageLayer::make(10, 10)->setDownloader(new FakeDownloader($this->pngBytes(4, 4, '#ff0000')));
        $layer->setImage($url);

        $graph = $layer->graph();

        $this->assertSame('ImageLayer', $graph['type']);
        $this->assertSame('StaticValue', $graph['data']['valueType']);
        $this->assertSame($url, $graph['data']['value']);
    }

    public function testHorizontalLeftAlignOffsetsImageByLeftPadding()
    {
        $path = $this->localPng('#ff0000', 30);

        // 左右内边距 10：内容盒 40x40，left 对齐时图像从 x=10 开始
        $layer = ImageLayer::make(60, 40, '#0000ff')
            ->setPadding(0, 10)
            ->setHorizontalAlign('left')
            ->setImage($path);

        $image = $layer->render();

        $this->assertPixelSame([0, 0, 255], $image, 5, 20);
        $this->assertPixelSame([255, 0, 0], $image, 15, 20);
        $this->assertPixelSame([255, 0, 0], $image, 45, 20);
    }
}
