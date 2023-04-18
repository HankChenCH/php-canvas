<?php

namespace LikePS\Layer;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeNone;
use Endroid\QrCode\Writer\PngWriter;

use Intervention\Image\Image;


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

    /**
     * qrcode文本提示图层
     *
     * @var TextLayer|null
     */
    private $qrCodeTipsLayer;

    public function generateQrCodeLayerFromContent($content)
    {
        $this->qrCodeLayer = ImageLayer::make($this->width, $this->width)
            ->setImage($this->generateQrCode($content));

        return $this;
    }

    private function generateQrCode($text)
    {
        $this->qrCodeText = $text;

        // Create a basic QR code
        $qrCode = QrCode::create($text)
            ->setSize($this->getWidth())
            ->setMargin(0)  // Set advanced options
            ->setRoundBlockSizeMode(new RoundBlockSizeModeNone())
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->setForegroundColor(new Color(0, 0, 0)) // ['r' => 0, 'g' => 0, 'b' => 0]
            ->setBackgroundColor(new Color(255, 255, 255));

        $writer = new PngWriter();
        return $writer->write($qrCode)
            ->getDataUri();
    }

    public function setQrCodeLayer(ImageLayer $qrCodeLayer)
    {
        $this->qrCodeLayer = $qrCodeLayer;
        return $this;
    }

    public function setQrCodeTipsLayer(TextLayer $qrCodeTipsLayer)
    {
        $qrCodeTipsLayer->setWidth($this->getWidth());
        $this->qrCodeTipsLayer = $qrCodeTipsLayer;
        return $this;
    }

    public function getHeight()
    {
        $imageHeight = 0;
        if ($this->qrCodeLayer) {
            $imageHeight += $this->qrCodeLayer->getHeight();
        }

        if ($this->qrCodeTipsLayer) {
            $imageHeight += $this->qrCodeTipsLayer->getHeight();
        }

        return $imageHeight;
    }

    public function render(): Image
    {
        $image = $this->renderOutterBox();

        if ($this->qrCodeLayer) {
            $image->insert($this->qrCodeLayer->render());
        }

        if ($this->qrCodeTipsLayer) {
            $offsetY = $this->qrCodeLayer ? $this->qrCodeLayer->getHeight() : 0;
            $image->insert($this->qrCodeTipsLayer->render(), 'top-left', 0, $offsetY);
        }

        return $image;
    }

    public function graph()
    {
        $graph = parent::graph();
        $graph['data'] = [
            'qrCodeText' => $this->qrCodeText
        ];

        if ($this->qrCodeTipsLayer) {
            $graph['tips'] = $this->qrCodeTipsLayer->graph();
        }

        return $graph;
    }
}
