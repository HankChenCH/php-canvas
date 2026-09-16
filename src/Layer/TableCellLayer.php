<?php

namespace HankChen\Canvas\Layer;

use Intervention\Image\Interfaces\ImageInterface;


class TableCellLayer extends AbstractLayer
{
    protected $name = 'TableCellLayer';

    /**
     * 内容
     *
     * @var AbstractLayer|null
     */
    private $contentLayer;

    public function addContentLayer(AbstractLayer $contentLayer)
    {
        $contentLayer->setWidth($this->getWidth());
        if ($this->autoHeight) {
            $this->setHeight($contentLayer->getHeight());
        } else {
            $contentLayer->setHeight($this->getHeight())
                ->setAutoHeight(false);
        }

        $this->contentLayer = $contentLayer;
        return $this;
    }

    public function render(): ImageInterface
    {
        $image = $this->renderOutterBox();

        if ($this->contentLayer) {
            list($position, $posx, $posy) = $this->contentLayer->getPosition();
            $image->insert($this->contentLayer->render(), $posx, $posy, $position);
        }

        return $image;
    }

    public function graph()
    {
        $graph = parent::graph();
        $graph['content'] = null;

        if ($this->contentLayer) {
            $graph['content'] = $this->contentLayer->graph();
        }

        return $graph;
    }
}
