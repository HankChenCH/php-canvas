<?php

namespace HankChen\Canvas\Layer;

use Exception;
use Intervention\Image\AbstractFont;
use Intervention\Image\Image;


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
        $paddingHeight = $padding['top'] + $padding['bottom'];
        if ($this->autowrap) {
            $this->autowrap();
            return $this->lineHeight() * $this->lines + $paddingHeight;
        }

        if (!empty($this->text)) {
            return $this->lineHeight() * 1 + $paddingHeight;
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
            $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'canvas' . DIRECTORY_SEPARATOR . 'text_layers';
            if (!is_dir($tmpPath)) {
                mkdir($tmpPath, 0644, true);
            }

            if (!is_writable($tmpPath)) {
                throw new Exception("tmp path can not writable:" . $tmpPath);
            }

            $urlParseResult = parse_url($font);
            $pathinfo = pathinfo($urlParseResult['path']);
            $fontPath = $tmpPath . DIRECTORY_SEPARATOR . $pathinfo['basename'];
            if (!is_file($fontPath)) {
                if (!file_put_contents($tmpPath . DIRECTORY_SEPARATOR . $pathinfo['basename'], file_get_contents($font))) {
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

    public function render(): Image
    {
        $outterBox = $this->renderOutterBox();
        $innerBox = $this->renderInnerBox();

        if ($this->autowrap) {
            $this->autowrap();
            
            list($posx, $posy) = $this->getInitXY();
            foreach ($this->lineWords as $line) {
                $innerBox->text($line, $posx, $posy, function (AbstractFont $font) {
                    $font->file($this->font);
                    $font->size($this->fontSize);
                    $font->color($this->fontColor);
                    $font->align($this->horizontalAlign);
                    $font->valign($this->verticalAlign);
                    $font->angle($this->textAngle);
                });

                $posy += $this->lineHeight();
            }
        } else {
            list($posx, $posy) = $this->getInitXY();
            $innerBox->text($this->text, $posx, $posy, function (AbstractFont $font) {
                $font->file($this->font);
                $font->size($this->fontSize);
                $font->color($this->fontColor);
                $font->align($this->horizontalAlign);
                $font->valign($this->verticalAlign);
                $font->angle($this->textAngle);
            });
        }

        $padding = $this->getPadding();
        $outterBox->insert($innerBox, 'top-left', $padding['left'], $padding['top']);
        return $outterBox;
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
        return ceil($this->fontSize * $this->lineHeight);
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
