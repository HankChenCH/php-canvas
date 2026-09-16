<?php

namespace HankChen\Canvas;

use SplPriorityQueue;

use Intervention\Image\Interfaces\ImageInterface;

use HankChen\Canvas\Layer\AbstractLayer;


class Canvas
{
    /**
     * 图层
     *
     * @var SplPriorityQueue<AbstractLayer>
     */
    private $layers = [];

    /**
     * 图像实例
     *
     * @var ImageInterface
     */
    private $core;

    public static function make($width, $height, AbstractLayer ...$layers)
    {
        $canvas = new self();

        $canvas->setCore(ImageManagerFactory::make()->createImage($width, $height));
        $canvas->initLayers($layers);

        return $canvas;
    }

    public function __construct()
    {
        $this->layers = new SplPriorityQueue();
    }

    private function setCore(ImageInterface $image)
    {
        $this->core = $image;
        return $this;
    }

    public function getCore()
    {
        return $this->core;
    }

    /**
     * 初始化图层
     *
     * @param AbstractLayer[] $layers
     * @return static
     */
    private function initLayers(array $layers)
    {
        foreach ($layers as $key => $layer) {
            $this->layers->insert($layer, $layer->getPriority());
        }
        return $this;
    }

    public function addLayer(AbstractLayer $layer)
    {
        $this->layers->insert($layer, $layer->getPriority());
        return $this;
    }

    public function graph()
    {
        $layerGraphs = [];
        while ($this->layers->valid()) {
            /**
             * @var AbstractLayer
             */
            $layer = $this->layers->current();

            $layerGraphs[] = $layer->graph();
            $this->layers->next();
        }

        return [
            'canvas' => [
                'width' => $this->core->width(),
                'height' => $this->core->height(),
            ],
            'layers' => $layerGraphs,
        ];
    }

    public function render()
    {
        while ($this->layers->valid()) {
            /**
             * @var AbstractLayer
             */
            $layer = $this->layers->current();

            $image = $layer->render();
            list($position, $posx, $posy) = $layer->getPosition();
            $this->core->insert($image, $posx, $posy, $position);

            $this->layers->next();
        }

        return $this;
    }

    public function save($filepath)
    {
        $this->core->save($filepath);
    }
}
