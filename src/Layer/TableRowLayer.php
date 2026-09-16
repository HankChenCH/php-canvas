<?php

namespace HankChen\Canvas\Layer;

use Intervention\Image\Interfaces\ImageInterface;


class TableRowLayer extends AbstractLayer
{
    protected $name = 'TableRowLayer';

    /**
     * 内容
     *
     * @var TableCellLayer[]
     */
    private $cells = [];

    public function addCell(TableCellLayer $cell)
    {
        $cellHeight = $cell->getHeight();
        if ($this->getHeight() < $cellHeight) {
            $this->setHeight($cellHeight);
        }

        $this->cells[] = $cell;
        return $this;
    }

    public function render(): ImageInterface
    {
        $image = $this->renderOutterBox();

        $posx = 0;
        foreach ($this->cells as $cell) {
            $image->insert($cell->render(), $posx, 0, 'top-left');
            $posx += $cell->getWidth();
        }

        return $image;
    }

    public function graph()
    {
        $graph = parent::graph();
        $graph['cellTemplates'] = [];

        foreach ($this->cells as $cell) {
            $graph['cellTemplates'][] = $cell->graph();
        }

        return $graph;
    }
}
