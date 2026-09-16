<?php

namespace HankChen\Canvas\Layer;

use Exception;
use HankChen\Canvas\Contracts\DownloaderInterface;
use HankChen\Canvas\ImageManagerFactory;
use Intervention\Image\Interfaces\ImageInterface;

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
            $tmpPath = $this->ensureCacheDir('img_layers');

            $urlParseResult = parse_url($img);
            if (!$urlParseResult || !isset($urlParseResult['path'])) {
                throw new Exception("image url parse failed:{$img}");
            }

            $pathinfo = pathinfo($urlParseResult['path']);
            $imagePath = $tmpPath . DIRECTORY_SEPARATOR . $pathinfo['basename'];
            if (!is_file($imagePath)) {
                $content = $this->resourceDownloader->download($img);
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

    public function render(): ImageInterface
    {
        $image = $this->renderOutterBox();

        if ($this->img) {
            list($posx, $posy) = $this->getInitXY();
            $image->insert(
                ImageManagerFactory::make()
                    ->decode($this->img)
                    ->orient()
                    ->cover($this->getContentWidth(), $this->getContentHeight()),
                $posx,
                $posy,
                'top-left',
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
