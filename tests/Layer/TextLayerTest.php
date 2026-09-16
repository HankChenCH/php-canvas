<?php

namespace HankChen\Canvas\Tests\Layer;

use Exception;
use HankChen\Canvas\Layer\TextLayer;
use HankChen\Canvas\Tests\Support\CanvasTestCase;
use HankChen\Canvas\Tests\Support\FakeDownloader;

class TextLayerTest extends CanvasTestCase
{
    private const FONT_SIZE = 12;

    protected function setUp(): void
    {
        parent::setUp();

        // 清掉远程字体缓存目录，保证每条用例都真正执行下载与建目录逻辑
        $this->removeDir($this->cacheDir('text_layers'));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->cacheDir('text_layers'));

        parent::tearDown();
    }

    private function cjkText(int $chars): string
    {
        return str_repeat('字', $chars);
    }

    public function testFixedHeightReturnsDeclaredHeight()
    {
        $layer = TextLayer::make(100, 50)->setText('hello');

        $this->assertSame(50, $layer->getHeight());
    }

    public function testAutoHeightSingleLineUsesLineHeight()
    {
        $layer = TextLayer::make(100, 'auto')->setText('hello');

        // ceil(字号 12 * 行高 1.0) = 12
        $this->assertSame(12, $layer->getHeight());
    }

    public function testAutoHeightWithPaddingOnlyWhenTextEmpty()
    {
        $layer = TextLayer::make(100, 'auto')->setPadding(5);

        $this->assertSame(10, $layer->getHeight());
    }

    public function testAutoHeightRespectsLineHeight()
    {
        $layer = TextLayer::make(100, 'auto')
            ->setText('hello')
            ->setLineHeight(1.5);

        // ceil(12 * 1.5) = 18
        $this->assertSame(18, $layer->getHeight());
    }

    public function testAutowrapSplitsFullWidthChars()
    {
        // 宽 96、字号 12 → 每行 8 个全角字；16 个字拆成 2 行
        $layer = TextLayer::make(96, 'auto')
            ->setAutowrap(true)
            ->setText($this->cjkText(16));

        $this->assertSame(24, $layer->getHeight());
    }

    public function testAutowrapCountsHalfWidthCharsAsHalf()
    {
        // 每行最多容纳 13 个半角字符（0.55 计重），20 个字符拆成 2 行
        $layer = TextLayer::make(96, 'auto')
            ->setAutowrap(true)
            ->setText(str_repeat('a', 20));

        $this->assertSame(24, $layer->getHeight());
    }

    public function testAutowrapHeightRespectsLineHeight()
    {
        $layer = TextLayer::make(96, 'auto')
            ->setAutowrap(true)
            ->setLineHeight(1.5)
            ->setText($this->cjkText(16));

        // 2 行 * ceil(12 * 1.5)
        $this->assertSame(36, $layer->getHeight());
    }

    public function testSetTextTriggersRewrap()
    {
        $layer = TextLayer::make(96, 'auto')->setAutowrap(true);

        $layer->setText($this->cjkText(8));
        $this->assertSame(12, $layer->getHeight());

        // 重新 setText 后必须重新折行
        $layer->setText($this->cjkText(16));
        $this->assertSame(24, $layer->getHeight());
    }

    public function testAutowrapKeepsExplicitNewlines()
    {
        $layer = TextLayer::make(96, 'auto')
            ->setAutowrap(true)
            ->setText("aaaa\nbbbb");

        // 换行符强制分行
        $this->assertSame(24, $layer->getHeight());
    }

    public function testRenderFixedBoxKeepsSizeAndBackground()
    {
        $layer = TextLayer::make(96, 40, '#ffffff')->setText('hi');

        // Imagick 驱动没有内置字体，有系统 TTF 时用真实字体，否则只能跳过
        $ttf = $this->systemTtf();
        if ($ttf !== null) {
            $layer->setFont($ttf, 12, '#000000');
        } elseif ($this->usesImagickDriver()) {
            $this->markTestSkipped('Imagick 驱动渲染文字必须有字体文件，且环境中没有可用 TTF');
        }

        $image = $layer->render();

        $this->assertSame(96, $image->width());
        $this->assertSame(40, $image->height());
        $this->assertPixelSame([255, 255, 255], $image, 1, 1);
    }

    public function testNumericBuiltinFontIdFallsBackToDefaultFont()
    {
        // v2 默认字体是 GD 内置字体编号 '1'，v4 已移除内置字体支持；
        // GD 驱动下数字编号按无字体文件处理走 v4 内置默认字体；
        // Imagick 驱动无内置字体机制，不设字体文件会直接抛异常（v4 真实限制）
        if ($this->usesImagickDriver()) {
            $this->markTestSkipped('v4 的 Imagick 驱动不支持无字体文件/数字字体编号渲染');
        }

        $layer = TextLayer::make(96, 40, '#ffffff')->setText('hello world');
        $image = $layer->render();

        $darkPixels = 0;
        for ($x = 0; $x < 96; $x++) {
            for ($y = 0; $y < 40; $y++) {
                if ($this->pixel($image, $x, $y)[0] < 128) {
                    $darkPixels++;
                }
            }
        }

        $this->assertGreaterThan(0, $darkPixels, '数字字体编号应回退为默认字体并画出文字像素');
    }

    public function testLocalFontRecordedAsBasenameInGraph()
    {
        $layer = TextLayer::make(100, 40);
        $layer->setFont('/assets/fonts/PingFang.ttf', 14, '#333333');

        $fontFamily = $layer->graph()['spec']['fontFamily'];

        $this->assertSame('PingFang.ttf', $fontFamily['font']);
        $this->assertSame(14, $fontFamily['fontSize']);
        $this->assertSame('#333333', $fontFamily['fontColor']);
    }

    public function testRemoteFontDownloadsAndCachesToTmp()
    {
        $downloader = new FakeDownloader('font-binary-content');
        $layer = TextLayer::make(100, 40);
        $layer->setDownloader($downloader);

        $url = 'http://example.com/fonts/canvas-test-font-' . uniqid() . '.ttf';
        $layer->setFont($url, 14, '#333333');

        // 字体文件落盘且内容一致
        $cached = $this->cacheDir('text_layers') . DIRECTORY_SEPARATOR . basename($url);
        $this->assertFileExists($cached);
        $this->assertSame('font-binary-content', file_get_contents($cached));

        // graph 中只暴露 basename
        $this->assertSame(basename($url), $layer->graph()['spec']['fontFamily']['font']);
    }

    public function testRemoteFontIsCachedAcrossLayers()
    {
        $downloader = new FakeDownloader('font-binary-content');
        $url = 'http://example.com/fonts/canvas-test-font-' . uniqid() . '.ttf';

        $first = TextLayer::make(100, 40)->setDownloader($downloader);
        $first->setFont($url, 14, '#333333');

        // 第二个图层加载同一字体 URL 时命中缓存，不再触发下载
        $second = TextLayer::make(100, 40)->setDownloader($downloader);
        $second->setFont($url, 14, '#333333');

        $this->assertCount(1, $downloader->calls);
    }

    public function testRemoteFontDownloadFailureThrows()
    {
        $layer = TextLayer::make(100, 40);
        $layer->setDownloader(new FakeDownloader(false));

        $this->expectException(Exception::class);

        $layer->setFont('http://example.com/fonts/canvas-test-font-' . uniqid() . '.ttf', 14, '#333333');
    }

    public function testGraphCarriesTextAndStaticValueType()
    {
        $layer = TextLayer::make(100, 40)->setText('画布文字');

        $graph = $layer->graph();

        $this->assertSame('TextLayer', $graph['type']);
        $this->assertSame('StaticValue', $graph['data']['valueType']);
        $this->assertSame('画布文字', $graph['data']['value']);
        $this->assertSame('', $graph['data']['expression']);
        $this->assertFalse($graph['spec']['fontFamily']['autowrap']);
    }
}
