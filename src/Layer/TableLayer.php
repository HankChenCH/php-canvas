<?php

namespace HankChen\Canvas\Layer;

use Intervention\Image\Image;


class TableLayer extends AbstractLayer
{
    protected $name = 'TableLayer';

    /**
     * 内容
     *
     * @var TableRowLayer[]
     */
    private $rows = [];

    private $contentBoxHeight = 0;

    public function isOverHeight(TableRowLayer $row)
    {
        return $this->contentBoxHeight + $row->getHeight() > $this->getHeight();
    }

    public function addRow(TableRowLayer $row)
    {
        $row->setWidth($this->getWidth());
        $this->rows[] = $row;

        $this->contentBoxHeight += $row->getHeight();
        return $this;
    }

    public function render(): Image
    {
        $image = $this->renderOutterBox();

        $posy = 0;
        foreach ($this->rows as $row) {
            $image->insert($row->render(), 'top-left', 0, $posy);
            $posy += $row->getHeight();
        }

        return $image;
    }

    public function graph()
    {
        $graph = parent::graph();
        $graph['rowTemplate'] = null;
        
        if (count($this->rows) > 0) {
            $graph['rowTemplate'] = $this->rows[0]->graph();
        }

        return $graph;
    }
}
