<?php

namespace HankChen\Canvas\Layer;

use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic as ImageManager;

abstract class AbstractLayer
{
    protected $name;

    protected $width;
    protected $height;
    protected $autoWidth = false;
    protected $autoHeight = false;
    protected $lineHeight = 1;

    protected $padding = [
        'left' => 0,
        'right' => 0,
        'top' => 0,
        'bottom' => 0,
    ];

    protected $border = [
        'top' => null,
        'bottom' => null,
        'left' => null,
        'right' => null,
    ];

    // value: left,center,right
    protected $horizontalAlign = 'left';
    // value: top,center,bottom
    protected $verticalAlign = 'top';

    protected $bgColor;

    protected $position = 'top-left';
    protected $x = 0;
    protected $y = 0;
    protected $priority = 0;

    public static function make($width = 'auto', $height = 'auto', $background = null)
    {
        $self = new static();

        $self->setWidth($width)
            ->setHeight($height)
            ->setBackground($background);

        return $self;
    }

    abstract public function render(): Image;

    protected function renderOutterBox()
    {
        $width = $this->getWidth();
        $height = $this->getHeight();

        $image = ImageManager::canvas($width, $height, $this->bgColor);

        $border = $this->getBorder();
        if (!empty($border['top'])) {
            $image->line(0, 0, $width, 0, function ($draw) use ($border) {
                $draw->width($border['top']['width']);
                $draw->color($border['top']['color']);
            });
        }
        if (!empty($border['bottom'])) {
            $image->line(0, $height, $width, $height, function ($draw) use ($border) {
                $draw->width($border['bottom']['width']);
                $draw->color($border['bottom']['color']);
                $draw->border($border['bottom']['width'], $border['bottom']['color']);
            });
        }
        if (!empty($border['left'])) {
            $image->line(0, 0, 0, $height, function ($draw) use ($border) {
                $draw->width($border['left']['width']);
                $draw->color($border['left']['color']);
            });
        }
        if (!empty($border['right'])) {
            $image->line($width, 0, $width, $height, function ($draw) use ($border) {
                $draw->width($border['right']['width']);
                $draw->color($border['right']['color']);
            });
        }

        return $image;
    }

    protected function renderInnerBox()
    {
        $image = ImageManager::canvas($this->getContentWidth(), $this->getContentHeight(), $this->bgColor);
        return $image;
    }

    public function setWidth($width)
    {
        if (is_string($width) && strtolower($width) === 'auto') {
            $this->setAutoWidth(true);
            $this->width = 0;
            return $this;
        }

        $this->width = intval($width);
        return $this;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setAutoWidth(bool $autoWidth)
    {
        $this->autoWidth = $autoWidth;
        return $this;
    }

    public function setHeight($height)
    {
        if (is_string($height) && strtolower($height) === 'auto') {
            $this->setAutoHeight(true);
            $this->height = 0;
            return $this;
        }

        $this->height = intval($height);
        return $this;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setAutoHeight(bool $autoHeight)
    {
        $this->autoHeight = $autoHeight;
        return $this;
    }

    public function getContentWidth()
    {
        return $this->getWidth() - $this->padding['left'] - $this->padding['right'];
    }

    public function getContentHeight()
    {
        return $this->getHeight() - $this->padding['top'] - $this->padding['bottom'];
    }
    
    public function setLineHeight(float $lineHeight)
    {
        $this->lineHeight = $lineHeight;
        return $this;
    }

    public function setBackground($bgColor)
    {
        $this->bgColor = $bgColor;
        return $this;
    }

    public function setPosition($x = 0, $y = 0, $position = 'top-left')
    {
        $this->x = intval($x);
        $this->y = intval($y);
        $this->position = $position;
        return $this;
    }

    public function getPosition()
    {
        return [$this->position, $this->x, $this->y];
    }

    public function setBorder(int $width, $color = '#000')
    {
        if ($width === 0) {
            $this->border['top'] = null;
            $this->border['bottom'] = null;
            $this->border['left'] = null;
            $this->border['right'] = null;

            return $this;
        }

        $this->border['top'] = ['width' => $width, 'color' => $color];
        $this->border['bottom'] = ['width' => $width, 'color' => $color];
        $this->border['left'] = ['width' => $width, 'color' => $color];
        $this->border['right'] = ['width' => $width, 'color' => $color];
        return $this;
    }

    public function getBorder()
    {
        return $this->border;
    }

    public function setBorderTop($width, $color = '#000')
    {
        if ($width === 0) {
            $this->border['top'] = null;
            return $this;
        }

        $this->border['top'] = ['width' => $width, 'color' => $color];
        return $this;
    }

    public function setBorderBottom($width, $color = '#000')
    {
        if ($width === 0) {
            $this->border['bottom'] = null;
            return $this;
        }

        $this->border['bottom'] = ['width' => $width, 'color' => $color];
        return $this;
    }

    public function setBorderLeft($width, $color = '#000')
    {
        if ($width === 0) {
            $this->border['left'] = null;
            return $this;
        }

        $this->border['left'] = ['width' => $width, 'color' => $color];
        return $this;
    }

    public function setBorderRight($width, $color = '#000')
    {
        if ($width === 0) {
            $this->border['right'] = null;
            return $this;
        }

        $this->border['right'] = ['width' => $width, 'color' => $color];
        return $this;
    }

    public function setPadding(float ...$args)
    {
        $argsLength = count($args);
        if ($argsLength === 1) {
            $this->padding = [
                'top' => $args[0],
                'bottom' => $args[0],
                'left' => $args[0],
                'right' => $args[0],
            ];
            return $this;
        }

        if ($argsLength === 2) {
            $this->padding = [
                'top' => $args[0],
                'bottom' => $args[0],
                'left' => $args[1],
                'right' => $args[1],
            ];
            return $this;
        }

        if ($argsLength === 3) {
            $this->padding = [
                'top' => $args[0],
                'bottom' => $args[2],
                'left' => $args[1],
                'right' => $args[1],
            ];
            return $this;
        }

        if ($argsLength === 4) {
            $this->padding = [
                'top' => $args[0],
                'bottom' => $args[2],
                'left' => $args[3],
                'right' => $args[1],
            ];
            return $this;
        }
    }

    public function getPadding()
    {
        return $this->padding;
    }

    public function setHorizontalAlign($horizontal)
    {
        $this->horizontalAlign = $horizontal;
        return $this;
    }

    public function setVerticalAlign($vertical)
    {
        $this->verticalAlign = $vertical;
        return $this;
    }

    public function setPriority(int $priority)
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function graph()
    {
        return [
            'type' => $this->name,
            'priority' => $this->priority,
            'spec' => [
                'shape' => [
                    'width' => $this->width,
                    'height' => $this->height,
                    'autoWidth' => $this->autoWidth,
                    'autoHeight' => $this->autoHeight,
                    'lineHeight' => $this->lineHeight,
                    'padding' => $this->padding,
                    'border' => $this->border,
                    'backgroundColor' => $this->bgColor,
                ],
                'align' => [
                    'horizontal' => $this->horizontalAlign,
                    'vertical' => $this->verticalAlign,
                ],
                'position' => [
                    'x' => $this->x,
                    'y' => $this->y,
                    'position' => $this->position,
                ],
            ]
        ];
    }
}
