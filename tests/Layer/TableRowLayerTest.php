<?php

namespace HankChen\Canvas\Tests\Layer;

use HankChen\Canvas\Layer\TableCellLayer;
use HankChen\Canvas\Layer\TableRowLayer;
use HankChen\Canvas\Tests\Support\CanvasTestCase;

class TableRowLayerTest extends CanvasTestCase
{
    public function testAddCellGrowsRowHeightToTallest()
    {
        $row = TableRowLayer::make(100, 10);

        $row->addCell(TableCellLayer::make(40, 25, '#ff0000'));
        $this->assertSame(25, $row->getHeight());

        // 更矮的单元格不会改变行高
        $row->addCell(TableCellLayer::make(60, 15, '#0000ff'));
        $this->assertSame(25, $row->getHeight());
    }

    public function testAddCellReturnsSelf()
    {
        $row = TableRowLayer::make(100, 10);

        $this->assertSame($row, $row->addCell(TableCellLayer::make(40, 10, '#ff0000')));
    }

    public function testRenderPlacesCellsLeftToRight()
    {
        $row = TableRowLayer::make(100, 20, '#ffffff');
        $row->addCell(TableCellLayer::make(40, 20, '#ff0000'));
        $row->addCell(TableCellLayer::make(60, 20, '#0000ff'));

        $image = $row->render();

        $this->assertSame(100, $image->getWidth());
        $this->assertSame(20, $image->getHeight());
        $this->assertPixelSame([255, 0, 0], $image, 20, 10);
        $this->assertPixelSame([0, 0, 255], $image, 70, 10);
    }

    public function testGraphContainsCellTemplates()
    {
        $row = TableRowLayer::make(100, 20);
        $row->addCell(TableCellLayer::make(40, 20, '#ff0000'));
        $row->addCell(TableCellLayer::make(60, 20, '#0000ff'));

        $graph = $row->graph();

        $this->assertSame('TableRowLayer', $graph['type']);
        $this->assertCount(2, $graph['cellTemplates']);
        $this->assertSame('TableCellLayer', $graph['cellTemplates'][0]['type']);
        $this->assertSame('#ff0000', $graph['cellTemplates'][0]['spec']['shape']['backgroundColor']);
    }
}
