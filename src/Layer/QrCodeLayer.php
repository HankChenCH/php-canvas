<?php

namespace HankChen\Canvas\Layer;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

use Intervention\Image\Interfaces\ImageInterface;


class QrCodeLayer extends AbstractLayer
{
    protected $name = 'QrCodeLayer';

    private $qrCodeText = '';

    /**
     * qrcode图像图层
     *
     * @var ImageLayer|null
     */
    private $qrCodeLayer;

    public function generateQrCodeLayerFromContent($content)
    {
        $this->qrCodeLayer = ImageLayer::make($this->width, $this->width)
            ->setImage($this->generateQrCode($content));

        return $this;
    }

    private function generateQrCode($text)
    {
        $this->qrCodeText = $text;

        // v6 起 QrCode 为只读值对象，通过命名参数构造；纠错级别与圆角模式由类枚举给出
        $qrCode = new QrCode(
            data: $text,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $this->getWidth(),
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::None,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        return (new PngWriter())->write($qrCode)
            ->getDataUri();
    }

    public function getHeight()
    {
        if ($this->qrCodeLayer) {
            return $this->qrCodeLayer->getHeight();
        }

        // 二维码图层未生成时按宽高声明兜底，避免 0 高度画布导致渲染报错
        if (!$this->autoHeight && $this->height > 0) {
            return $this->height;
        }

        return $this->getWidth();
    }

    public function render(): ImageInterface
    {
        $image = $this->renderOutterBox();

        if ($this->qrCodeLayer) {
            list($position, $posx, $posy) = $this->qrCodeLayer->getPosition();
            $image->insert($this->qrCodeLayer->render(), $posx, $posy, $position);
        }

        return $image;
    }

    public function graph()
    {
        $graph = parent::graph();
        $graph['data'] = [
            'valueType' => 'StaticValue',
            'value' => $this->qrCodeText
        ];

        return $graph;
    }
}
