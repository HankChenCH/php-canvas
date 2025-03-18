<?php

namespace HankChen\Canvas\Layer;

use Exception;
use Intervention\Image\ImageManagerStatic as ImageManager;
use Intervention\Image\Image;

class ImageLayer extends AbstractLayer
{
    protected $name = 'ImageLayer';

    private $rawImg;
    private $img;

    // value: left,center,right
    protected $horizontalAlign = 'center';
    // value: top,center,bottom
    protected $verticalAlign = 'center';

    public function setImage($img)
    {
        if (!$img) {
            return $this;
        }

        $this->rawImg = $img;

        if (filter_var($img, FILTER_VALIDATE_URL) !== false) {
            $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'canvas' . DIRECTORY_SEPARATOR . 'img_layers';
            if (!is_dir($tmpPath)) {
                mkdir($tmpPath, 0644, true);
            }

            if (!is_writable($tmpPath)) {
                throw new Exception("tmp path can not writable:" . $tmpPath);
            }

            $urlParseResult = parse_url($img);
            $pathinfo = pathinfo($urlParseResult['path']);
            $imagePath = $tmpPath . DIRECTORY_SEPARATOR . $pathinfo['basename'];
            if (!is_file($imagePath)) {
                $content = file_get_contents($img);
                if (!$content) {
                    throw new Exception("could not get remote file({$img})");
                }
                
                if (!file_put_contents($tmpPath . DIRECTORY_SEPARATOR . $pathinfo['basename'], $content)) {
                    throw new Exception("remote file({$img}) save to tmp path failed");
                }
            }

            $this->img = $imagePath;
        } else {
            $this->img = $img;
        }

        return $this;
    }

    public function render(): Image
    {
        $image = $this->renderOutterBox();

        if ($this->img) {
            list($posx, $posy) = $this->getInitXY();
            $image->insert(
                ImageManager::make($this->img)
                    ->orientate()
                    ->fit($this->getContentWidth(), $this->getContentHeight()),
                'top-left',
                $posx,
                $posy,
            );
        }

        return $image;
    }

    private function getInitXY()
    {
        $posx = $posy = 0;
        switch ($this->horizontalAlign) {
            case 'left':
                $posx = $this->padding['left'];
                break;
            case 'center':
                $posx = ($this->getWidth() - $this->getContentWidth()) / 2;
                break;
            case 'right':
                $posx = $this->getWidth() - $this->getContentWidth();
                break;
        }

        switch ($this->verticalAlign) {
            case 'top':
                $posy = $this->padding['top'];
                break;
            case 'center':
                $posy = ($this->getHeight() - $this->getContentHeight()) / 2;
                break;
            case 'bottom':
                $posy = $this->getHeight() - $this->getContentHeight();
                break;
        }

        return [$posx, $posy];
    }

    public function graph()
    {
        $graph = parent::graph();

        $graph['data'] = [
            'valueType' => 'StaticValue',
            'value' => $this->rawImg
        ];

        return $graph;
    }
}
