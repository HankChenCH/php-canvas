<?php

namespace HankChen\Canvas\Tests\Layer;

use HankChen\Canvas\Layer\TableLayer;
use HankChen\Canvas\Layer\TableCellLayer;
use HankChen\Canvas\Layer\TableRowLayer;
use HankChen\Canvas\Tests\Support\CanvasTestCase;

class TableLayerTest extends CanvasTestCase
{
    private function row(int $height, string $bg): TableRowLayer
    {
        return TableRowLayer::make(100, $height, $bg);
    }

    public function testAddRowSyncsRowWidthToTableWidth()
    {
        $table = TableLayer::make(100, 60);
        $row = TableRowLayer::make('auto', 20);

        $table->addRow($row);

        $this->assertSame(100, $row->getWidth());
    }

    public function testAddRowReturnsSelf()
    {
        $table = TableLayer::make(100, 60);

        $this->assertSame($table, $table->addRow($this->row(20, '#ffffff')));
    }

    public function testIsOverHeightTracksAccumulatedRowHeights()
    {
        $table = TableLayer::make(100, 60);

        $table->addRow($this->row(20, '#ffffff'));

        $this->assertFalse($table->isOverHeight($this->row(40, '#ffffff')));
        $this->assertTrue($table->isOverHeight($this->row(45, '#ffffff')));

        // 累加到 60 后，再多 1px 即溢出
        $table->addRow($this->row(40, '#ffffff'));
        $this->assertTrue($table->isOverHeight($this->row(1, '#ffffff')));
    }

    public function testRenderStacksRowsVertically()
    {
        $table = TableLayer::make(100, 60, '#ffffff');
        $table->addRow(TableRowLayer::make(100, 20, '#ffffff')
            ->addCell($this->cell(100, 20, '#ff0000')));
        $table->addRow(TableRowLayer::make(100, 20, '#ffffff')
            ->addCell($this->cell(100, 20, '#0000ff')));

        $image = $table->render();

        $this->assertSame(100, $image->getWidth());
        $this->assertSame(60, $image->getHeight());
        $this->assertPixelSame([255, 0, 0], $image, 50, 10);
        $this->assertPixelSame([0, 0, 255], $image, 50, 30);
        $this->assertPixelSame([255, 255, 255], $image, 50, 50);
    }

    private function cell(int $width, int $height, string $bg): TableCellLayer
    {
        return TableCellLayer::make($width, $height, $bg);
    }

    public function testGraphRowTemplateIsNullWithoutRows()
    {
        $table = TableLayer::make(100, 60);

        $this->assertNull($table->graph()['rowTemplate']);
        $this->assertSame('TableLayer', $table->graph()['type']);
    }

    public function testGraphRowTemplateComesFromFirstRow()
    {
        $table = TableLayer::make(100, 60);
        $row = $this->row(20, '#ffffff')->addCell($this->cell(100, 20, '#ff0000'));
        $table->addRow($row);
        $table->addRow($this->row(20, '#ffffff'));

        $rowTemplate = $table->graph()['rowTemplate'];

        $this->assertSame('TableRowLayer', $rowTemplate['type']);
        $this->assertCount(1, $rowTemplate['cellTemplates']);
        $this->assertSame('TableCellLayer', $rowTemplate['cellTemplates'][0]['type']);
    }
}
