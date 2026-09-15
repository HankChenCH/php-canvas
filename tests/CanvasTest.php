<?php

namespace HankChen\Canvas\Tests;

use Intervention\Image\Image;
use HankChen\Canvas\Canvas;
use HankChen\Canvas\Layer\ImageLayer;
use HankChen\Canvas\Tests\Support\CanvasTestCase;

class CanvasTest extends CanvasTestCase
{
    public function testMakeCreatesCoreWithDeclaredSize()
    {
        $canvas = Canvas::make(100, 80);

        $this->assertInstanceOf(Canvas::class, $canvas);
        $this->assertInstanceOf(Image::class, $canvas->getCore());
        $this->assertSame(100, $canvas->getCore()->getWidth());
        $this->assertSame(80, $canvas->getCore()->getHeight());
    }

    public function testGraphContainsCanvasSizeAndLayerSpecs()
    {
        $canvas = Canvas::make(
            100,
            80,
            ImageLayer::make(30, 20, '#ff0000'),
            ImageLayer::make(10, 10, '#0000ff')
        );

        $graph = $canvas->graph();

        $this->assertSame(['width' => 100, 'height' => 80], $graph['canvas']);
        $this->assertCount(2, $graph['layers']);
        $this->assertSame('ImageLayer', $graph['layers'][0]['type']);
        $this->assertSame('#ff0000', $graph['layers'][0]['spec']['shape']['backgroundColor']);
    }

    public function testGraphOrdersLayersByPriorityDesc()
    {
        $red = ImageLayer::make(10, 10, '#ff0000')->setPriority(1);
        $blue = ImageLayer::make(10, 10, '#0000ff')->setPriority(5);

        // make() 时以构造参数顺序入队，graph() 按 priority 从大到小输出
        $canvas = Canvas::make(50, 50, $red, $blue);

        $backgrounds = array_map(function ($layerGraph) {
            return $layerGraph['spec']['shape']['backgroundColor'];
        }, $canvas->graph()['layers']);

        $this->assertSame(['#0000ff', '#ff0000'], $backgrounds);
    }

    public function testRenderCompositesHigherPriorityFirstSoItSitsBelow()
    {
        // SplPriorityQueue 大优先级先出队、先渲染，因此先画的在底层：
        // 红色优先级 1 后渲染，盖在优先级 5 的蓝色之上
        $red = ImageLayer::make(30, 20, '#ff0000')->setPriority(1);
        $blue = ImageLayer::make(30, 20, '#0000ff')->setPriority(5);

        $canvas = Canvas::make(30, 20, $red, $blue)->render();

        $this->assertPixelSame([255, 0, 0], $canvas->getCore(), 15, 10);
    }

    public function testRenderRespectsLayerPosition()
    {
        $base = ImageLayer::make(30, 20, '#ffffff')->setPriority(10);
        $patch = ImageLayer::make(10, 10, '#ff0000')
            ->setPriority(1)
            ->setPosition(10, 5);

        $canvas = Canvas::make(30, 20, $base, $patch)->render();

        $this->assertPixelSame([255, 0, 0], $canvas->getCore(), 15, 10);
        $this->assertPixelSame([255, 255, 255], $canvas->getCore(), 2, 2);
        $this->assertPixelSame([255, 255, 255], $canvas->getCore(), 25, 15);
    }

    public function testQueueIsDrainedByRenderAndGraph()
    {
        // 已知限制：SplPriorityQueue 迭代是破坏性的，render()/graph() 各自耗尽队列，
        // 同一实例第二次调用时图层列表为空（见 AGENTS.md）
        $canvas = Canvas::make(30, 20, ImageLayer::make(30, 20, '#ff0000'));

        $this->assertSame($canvas, $canvas->render());
        $this->assertSame([], $canvas->graph()['layers']);
    }

    public function testAddLayerAppendsAndReturnsSelf()
    {
        $canvas = Canvas::make(30, 20);
        $result = $canvas->addLayer(ImageLayer::make(30, 20, '#ff0000'));

        $this->assertSame($canvas, $result);
        $this->assertCount(1, $canvas->graph()['layers']);
    }

    public function testSaveWritesPngFile()
    {
        $path = sys_get_temp_dir() . '/php-canvas-test-save.png';

        Canvas::make(30, 20, ImageLayer::make(30, 20, '#ff0000'))
            ->render()
            ->save($path);

        $this->assertFileExists($path);
        $size = getimagesize($path);
        $this->assertSame(30, $size[0]);
        $this->assertSame(20, $size[1]);
        $this->assertSame('image/png', $size['mime']);

        @unlink($path);
    }
}
