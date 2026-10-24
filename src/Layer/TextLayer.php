<?php

namespace HankChen\Canvas\Layer;

use Exception;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;


class TextLayer extends AbstractLayer
{
    protected $name = 'TextLayer';

    protected $text = '';
    protected $font = '1';
    protected $fontSize = 12;
    protected $fontColor = '#000000';

    // value: left,center,right
    protected $horizontalAlign = 'left';
    // value: top,center,bottom
    protected $verticalAlign = 'bottom';

    protected $textAngle = 0;

    protected $autowrap = false;
    protected $wraped = false;
    protected $lineWords = [];
    protected $lines = 1;

    public function getHeight()
    {
        if (!$this->autoHeight) {
            return $this->height;
        }

        $padding = $this->getPadding();
        $paddingHeight = intval($padding['top'] + $padding['bottom']);
        if ($this->autowrap) {
            $this->autowrap();
            return $this->lineHeight() * $this->lines + $paddingHeight;
        }

        if (!empty($this->text)) {
            return $this->lineHeight() + $paddingHeight;
        }

        return $paddingHeight;
    }

    public function setText($text)
    {
        $this->text = $text;
        $this->wraped = false;

        return $this;
    }

    public function setFont($font, $size, $color)
    {
        if (filter_var($font, FILTER_VALIDATE_URL) !== false) {
            $tmpPath = $this->ensureCacheDir('text_layers');

            $urlParseResult = parse_url($font);
            $pathinfo = pathinfo($urlParseResult['path']);
            $fontPath = $tmpPath . DIRECTORY_SEPARATOR . $pathinfo['basename'];
            if (!is_file($fontPath)) {
                $fontContent = $this->resourceDownloader->download($font);
                if (!file_put_contents($tmpPath . DIRECTORY_SEPARATOR . $pathinfo['basename'], $fontContent)) {
                    throw new Exception("remote file({$font}) save to tmp path failed");
                }
            }

            $this->font = $fontPath;
        } else {
            $this->font = $font;
        }

        $this->fontSize = $size;
        $this->fontColor = $color;
        return $this;
    }

    public function setAutowrap(bool $autowrap)
    {
        $this->autowrap = $autowrap;
        return $this;
    }

    public function setAngle($angle)
    {
        $this->textAngle = $angle;
        return $this;
    }

    public function render(): ImageInterface
    {
        $outterBox = $this->renderOutterBox();
        $innerBox = $this->renderInnerBox();

        if ($this->autowrap) {
            $this->autowrap();

            list($posx, $posy) = $this->getInitXY();
            foreach ($this->lineWords as $line) {
                $innerBox->text($line, $posx, $posy, function (FontFactory $font) {
                    $this->applyFont($font);
                });

                $posy += $this->lineHeight();
            }
        } else {
            list($posx, $posy) = $this->getInitXY();
            $innerBox->text($this->text, $posx, $posy, function (FontFactory $font) {
                $this->applyFont($font);
            });
        }

        $padding = $this->getPadding();
        $outterBox->insert($innerBox, $padding['left'], $padding['top'], 'top-left');
        return $outterBox;
    }

    /**
     * 应用字体配置到 v4 的 FontFactory；
     * 纯数字编号是 v2 的 GD 内置字体 id，v4 已移除该支持，跳过后走 v4 内置默认字体
     */
    private function applyFont(FontFactory $font)
    {
        if (!is_numeric($this->font)) {
            $font->file($this->font);
        }

        $font->size($this->fontSize);
        $font->color($this->fontColor);
        $font->align($this->horizontalAlign, $this->verticalAlign);
        $font->angle($this->textAngle);
    }

    private function getInitXY()
    {
        $posx = $posy = 0;
        switch ($this->horizontalAlign) {
            case 'left':
                $posx = 0;
                break;
            case 'center':
                $posx = $this->getContentWidth() / 2;
                break;
            case 'right':
                $posx = $this->getContentWidth();
                break;
        }

        switch ($this->verticalAlign) {
            case 'top':
                $posy = 0;
                break;
            case 'center':
                if (!$this->autoHeight) {
                    $posy = intval(($this->getContentHeight() - $this->lineHeight() * ($this->lines - 1)) / 2);
                } else {
                    $posy = (int) $this->lineHeight() / 2;
                }
                break;
            case 'bottom':
                if (!$this->autowrap) {
                    $posy = $this->getContentHeight();
                } else {
                    $posy = $this->getContentHeight() - $this->lineHeight() * ($this->lines - 1) - round($this->fontSize * 0.1);
                }
                break;
        }

        return [$posx, $posy];
    }

    private function lineHeight()
    {
        return intval(ceil($this->fontSize * $this->lineHeight));
    }

    private function autowrap()
    {
        if (!$this->autowrap) {
            return;
        }

        if ($this->wraped) {
            return;
        }

        $texts = explode("\n", $this->text);

        $lineWords = [];
        $lineWordCount = intval(floor($this->getContentWidth() / $this->fontSize));
        foreach ($texts as $text) {
            $strLength = mb_strlen($text);

            $currentCount = 0;
            $currentTxt = "";
            for ($i = 0; $i < $strLength; $i++) {
                $txt = mb_substr($text, $i, 1);
                $currentTxt .= $txt;

                // 半角字符当半个字长度
                if (preg_match('/[\x{0020}\x{0020}-\x{7e}]/u', $txt) > 0) {
                    $currentCount += 0.55;
                } else {
                    $currentCount += 1;
                }

                if ($currentCount + 1 > $lineWordCount) {
                    $lineWords[] = $currentTxt;
                    $currentTxt = "";
                    $currentCount = 0;
                }
            }

            if (mb_strlen($currentTxt) > 0) {
                $lineWords[] = $currentTxt;
            }
        }

        $this->lineWords = $lineWords;
        $this->lines = count($lineWords);
        $this->wraped = true;
    }

    public function graph()
    {
        $graph = parent::graph();

        $graph['spec']['fontFamily'] = [
            'font' => pathinfo($this->font, PATHINFO_BASENAME),
            'fontSize' => $this->fontSize,
            'fontColor' => $this->fontColor,
            'angle' => $this->textAngle,
            'autowrap' => $this->autowrap,
        ];
        $graph['data'] = [
            'valueType' => 'StaticValue',
            'expression' => '',
            'value' => $this->text
        ];

        return $graph;
    }
}
