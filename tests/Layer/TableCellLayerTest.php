<?php

namespace HankChen\Canvas\Tests\Layer;

use HankChen\Canvas\Layer\ImageLayer;
use HankChen\Canvas\Layer\TableCellLayer;
use HankChen\Canvas\Layer\TextLayer;
use HankChen\Canvas\Tests\Support\CanvasTestCase;

class TableCellLayerTest extends CanvasTestCase
{
    public function testFixedCellForcesContentHeightAndDisablesAuto()
    {
        $cell = TableCellLayer::make(40, 30);
        $content = TextLayer::make(40, 'auto')->setText('hello');

        $cell->addContentLayer($content);

        // 固定高度的单元格把内容层压成同高，并关闭内容的自动高度
        $contentGraph = $cell->graph()['content'];
        $this->assertSame(30, $contentGraph['spec']['shape']['height']);
        $this->assertFalse($contentGraph['spec']['shape']['autoHeight']);
        $this->assertSame(30, $content->getHeight());
    }

    public function testAutoCellAdoptsContentHeight()
    {
        $cell = TableCellLayer::make(40, 'auto');
        $content = TextLayer::make(40, 'auto')->setText('hello');

        $cell->addContentLayer($content);

        // 自动高度的单元格跟随内容层高度（单行文本 12px）
        $this->assertSame(12, $cell->getHeight());
    }

    public function testAddContentLayerSyncsWidthAndReturnsSelf()
    {
        $cell = TableCellLayer::make(40, 20);
        $content = ImageLayer::make(10, 10, '#ff0000');

        $this->assertSame($cell, $cell->addContentLayer($content));
        $this->assertSame(40, $content->getWidth());
    }

    public function testRenderInsertsContentLayer()
    {
        $cell = TableCellLayer::make(40, 20, '#ffffff');
        $cell->addContentLayer(ImageLayer::make(40, 20, '#ff0000'));

        $image = $cell->render();

        $this->assertSame(40, $image->getWidth());
        $this->assertSame(20, $image->getHeight());
        $this->assertPixelSame([255, 0, 0], $image, 20, 10);
    }

    public function testRenderWithoutContentIsPlainBackground()
    {
        $cell = TableCellLayer::make(40, 20, '#00ff00');

        $image = $cell->render();

        $this->assertPixelSame([0, 255, 0], $image, 20, 10);
        $this->assertNull($cell->graph()['content']);
        $this->assertSame('TableCellLayer', $cell->graph()['type']);
    }
}
