<?php

namespace HankChen\Canvas\Tests\Layer;

use HankChen\Canvas\Layer\QrCodeLayer;
use HankChen\Canvas\Tests\Support\CanvasTestCase;

class QrCodeLayerTest extends CanvasTestCase
{
    public function testGenerateProducesSquareLayerAndRender()
    {
        $layer = QrCodeLayer::make(60);
        $layer->generateQrCodeLayerFromContent('payload');

        // 二维码层为正方形，高度跟随宽度
        $this->assertSame(60, $layer->getHeight());

        $image = $layer->render();
        $this->assertSame(60, $image->width());
        $this->assertSame(60, $image->height());
    }

    public function testRenderWithoutGeneratedQrFallsBackToDeclaredSize()
    {
        // 缺省构造（高度 auto）时按宽度兜底，避免 0 高度画布导致渲染报错
        $layer = QrCodeLayer::make(60);
        $this->assertSame(60, $layer->getHeight());

        $image = $layer->render();
        $this->assertSame(60, $image->width());
        $this->assertSame(60, $image->height());

        // 显式声明高度时以声明为准
        $fixed = QrCodeLayer::make(80, 40);
        $this->assertSame(40, $fixed->getHeight());

        $fixedImage = $fixed->render();
        $this->assertSame(80, $fixedImage->width());
        $this->assertSame(40, $fixedImage->height());
    }

    public function testGraphValueFollowsGeneration()
    {
        $layer = QrCodeLayer::make(60);

        $this->assertSame('', $layer->graph()['data']['value']);

        $layer->generateQrCodeLayerFromContent('payload');
        $this->assertSame('payload', $layer->graph()['data']['value']);
        $this->assertSame('StaticValue', $layer->graph()['data']['valueType']);
        $this->assertSame('QrCodeLayer', $layer->graph()['type']);
    }

    public function testRenderedQrHasDarkFinderPatternAtCorner()
    {
        $layer = QrCodeLayer::make(60);
        $layer->generateQrCodeLayerFromContent('payload');

        // 无边距模式下左上角定位图案应该是深色
        $pixel = $this->pixel($layer->render(), 0, 0);

        $this->assertLessThan(60, $pixel[0]);
        $this->assertLessThan(60, $pixel[1]);
        $this->assertLessThan(60, $pixel[2]);
    }

    public function testCjkContentRendersWithUtf8Encoding()
    {
        // v6 通过命名参数显式指定 UTF-8 编码，中文内容应能正常生成并渲染
        $layer = QrCodeLayer::make(60);
        $layer->generateQrCodeLayerFromContent('画布文字');

        $image = $layer->render();

        $this->assertSame(60, $image->width());
        $this->assertSame(60, $image->height());

        $pixel = $this->pixel($image, 0, 0);
        $this->assertLessThan(60, $pixel[0]);
        $this->assertLessThan(60, $pixel[1]);
        $this->assertLessThan(60, $pixel[2]);

        // graph 数据保留原始中文内容
        $this->assertSame('画布文字', $layer->graph()['data']['value']);
    }
}
