<?php

namespace HankChen\Canvas\Layer;

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

    public function getHeight()
    {
        $imageHeight = 0;
        if ($this->qrCodeLayer) {
            $imageHeight += $this->qrCodeLayer->getHeight();
        }

        return $imageHeight;
    }

    public function render(): Image
    {
        $image = $this->renderOutterBox();

        if ($this->qrCodeLayer) {
            $position = $this->qrCodeLayer->getPosition();
            $image->insert($this->qrCodeLayer->render(), ...$position);
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
