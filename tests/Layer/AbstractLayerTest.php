<?php

namespace HankChen\Canvas\Tests\Layer;

use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;
use HankChen\Canvas\Layer\AbstractLayer;
use HankChen\Canvas\Tests\Support\CanvasTestCase;

class AbstractLayerTest extends CanvasTestCase
{
    /**
     * 用匿名子类测抽象基类的盒模型，避免依赖具体图层行为
     */
    private function layer(): AbstractLayer
    {
        return new class extends AbstractLayer {
            public function render(): Image
            {
                return ImageManagerStatic::canvas(1, 1);
            }
        };
    }

    public function testDefaultState()
    {
        $layer = $this->layer();

        $this->assertSame(['top-left', 0, 0], $layer->getPosition());
        $this->assertSame(0, $layer->getPriority());
        $this->assertSame([
            'left' => 0, 'right' => 0, 'top' => 0, 'bottom' => 0,
        ], $layer->getPadding());
        $this->assertSame([
            'top' => null, 'bottom' => null, 'left' => null, 'right' => null,
        ], $layer->getBorder());
    }

    public function testWidthHeightCastToInt()
    {
        $layer = $this->layer();

        $layer->setWidth('100');
        $layer->setHeight('50');

        $this->assertSame(100, $layer->getWidth());
        $this->assertSame(50, $layer->getHeight());
    }

    public function testAutoWidthHeightFlag()
    {
        $layer = $this->layer();

        // 'auto' 大小写不敏感，命中后宽高归零并置自动标记
        $layer->setWidth('AUTO')->setHeight('auto');

        $this->assertSame(0, $layer->getWidth());
        $this->assertSame(0, $layer->getHeight());

        $spec = $layer->graph()['spec']['shape'];
        $this->assertTrue($spec['autoWidth']);
        $this->assertTrue($spec['autoHeight']);
    }

    public function testContentSizeSubtractsPadding()
    {
        $layer = $this->layer()->setWidth(100)->setHeight(50);

        // 1 个参数：四边
        $layer->setPadding(10);
        $this->assertSame(80, $layer->getContentWidth());
        $this->assertSame(30, $layer->getContentHeight());

        // 2 个参数：上下、左右
        $layer->setPadding(10, 20);
        $this->assertSame(60, $layer->getContentWidth());
        $this->assertSame(30, $layer->getContentHeight());

        // 3 个参数：上、左右、下（CSS 简写顺序）
        $layer->setPadding(10, 20, 30);
        $this->assertSame(60, $layer->getContentWidth());
        $this->assertSame(10, $layer->getContentHeight());

        // 4 个参数：上、右、下、左
        $layer->setPadding(1, 2, 3, 4);
        $this->assertSame(94, $layer->getContentWidth());
        $this->assertSame(46, $layer->getContentHeight());
    }

    public function testSetPaddingLayout()
    {
        $layer = $this->layer();

        $layer->setPadding(10);
        $this->assertSame([
            'top' => 10.0, 'bottom' => 10.0, 'left' => 10.0, 'right' => 10.0,
        ], $layer->getPadding());

        $layer->setPadding(10, 20);
        $this->assertSame([
            'top' => 10.0, 'bottom' => 10.0, 'left' => 20.0, 'right' => 20.0,
        ], $layer->getPadding());

        $layer->setPadding(10, 20, 30);
        $this->assertSame([
            'top' => 10.0, 'bottom' => 30.0, 'left' => 20.0, 'right' => 20.0,
        ], $layer->getPadding());

        $layer->setPadding(1, 2, 3, 4);
        $this->assertSame([
            'top' => 1.0, 'bottom' => 3.0, 'left' => 4.0, 'right' => 2.0,
        ], $layer->getPadding());
    }

    public function testBorderSetAllAndClearWithZero()
    {
        $layer = $this->layer();

        $layer->setBorder(2, '#f00');
        $expected = ['width' => 2, 'color' => '#f00'];
        $this->assertSame([
            'top' => $expected, 'bottom' => $expected,
            'left' => $expected, 'right' => $expected,
        ], $layer->getBorder());

        // 宽度为 0 表示清除四边框
        $layer->setBorder(0);
        $this->assertSame([
            'top' => null, 'bottom' => null, 'left' => null, 'right' => null,
        ], $layer->getBorder());
    }

    public function testBorderSideSettersOnlyTouchOwnSide()
    {
        $layer = $this->layer();

        $layer->setBorder(1, '#000');
        $layer->setBorderTop(0);
        $layer->setBorderRight(3, '#00f');

        $border = $layer->getBorder();
        $this->assertNull($border['top']);
        $this->assertSame(['width' => 1, 'color' => '#000'], $border['bottom']);
        $this->assertSame(['width' => 1, 'color' => '#000'], $border['left']);
        $this->assertSame(['width' => 3, 'color' => '#00f'], $border['right']);
    }

    public function testSetPosition()
    {
        $layer = $this->layer();

        $layer->setPosition(10, 20, 'bottom-right');
        $this->assertSame(['bottom-right', 10, 20], $layer->getPosition());

        // 坐标参数会被转成整数
        $layer->setPosition('15', '25');
        $this->assertSame(['top-left', 15, 25], $layer->getPosition());
    }

    public function testSettersAreFluent()
    {
        $layer = $this->layer();

        $this->assertSame($layer, $layer->setWidth(10));
        $this->assertSame($layer, $layer->setHeight(10));
        $this->assertSame($layer, $layer->setAutoWidth(true));
        $this->assertSame($layer, $layer->setAutoHeight(true));
        $this->assertSame($layer, $layer->setLineHeight(1.5));
        $this->assertSame($layer, $layer->setBackground('#fff'));
        $this->assertSame($layer, $layer->setPosition(1, 1));
        $this->assertSame($layer, $layer->setBorder(1));
        $this->assertSame($layer, $layer->setPadding(1));
        $this->assertSame($layer, $layer->setHorizontalAlign('center'));
        $this->assertSame($layer, $layer->setVerticalAlign('center'));
        $this->assertSame($layer, $layer->setPriority(3));
    }

    public function testGraphStructure()
    {
        $layer = $this->layer();
        $layer->setWidth(100)
            ->setHeight(50)
            ->setLineHeight(1.5)
            ->setPadding(4)
            ->setBorder(2, '#123456')
            ->setBackground('#ffffff')
            ->setHorizontalAlign('center')
            ->setVerticalAlign('center')
            ->setPosition(7, 8, 'center')
            ->setPriority(9);

        $graph = $layer->graph();

        $this->assertArrayHasKey('type', $graph);
        $this->assertSame(9, $graph['priority']);
        $this->assertSame([
            'shape' => [
                'width' => 100,
                'height' => 50,
                'autoWidth' => false,
                'autoHeight' => false,
                'lineHeight' => 1.5,
                'padding' => [
                    'top' => 4.0, 'bottom' => 4.0, 'left' => 4.0, 'right' => 4.0,
                ],
                'border' => [
                    'top' => ['width' => 2, 'color' => '#123456'],
                    'bottom' => ['width' => 2, 'color' => '#123456'],
                    'left' => ['width' => 2, 'color' => '#123456'],
                    'right' => ['width' => 2, 'color' => '#123456'],
                ],
                'backgroundColor' => '#ffffff',
            ],
            'align' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'position' => [
                'x' => 7,
                'y' => 8,
                'position' => 'center',
            ],
        ], $graph['spec']);
    }
}
