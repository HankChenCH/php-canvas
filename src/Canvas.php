<?php

namespace LikePS;

use SplPriorityQueue;

use Intervention\Image\ImageManagerStatic as ImageManager;
use Intervention\Image\Image;

use LikePS\Layer\AbstractLayer;


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
     * @var Image
     */
    private $core;

    public static function make($width, $height, AbstractLayer ...$layers)
    {
        $canvas = new self();

        if (class_exists("\Imagick")) {
            $imageManager = ImageManager::configure(['driver' => 'imagick']);
        } else {
            $imageManager = ImageManager::configure();
        }

        $canvas->setCore($imageManager->canvas($width, $height));
        $canvas->initLayers($layers);

        return $canvas;
    }

    public function __construct()
    {
        $this->layers = new SplPriorityQueue();
    }

    private function setCore(Image $image)
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
                'width' => $this->core->getWidth(),
                'height' => $this->core->getHeight(),
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
            $position = $layer->getPosition();
            $this->core->insert($image, ...$position);

            $this->layers->next();
        }

        return $this;
    }

    public function save($filepath)
    {
        $this->core->save($filepath);
    }
}
