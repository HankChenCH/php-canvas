<?php

namespace HankChen\Canvas;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

/**
 * 图像管理器工厂：v4 起 ImageManagerStatic 被移除，
 * 统一在此按扩展可用性选择驱动并复用实例
 */
class ImageManagerFactory
{
    /**
     * @var ImageManager|null
     */
    private static $manager;

    public static function make(): ImageManager
    {
        if (!self::$manager instanceof ImageManager) {
            self::$manager = class_exists('\Imagick')
                ? new ImageManager(ImagickDriver::class)
                : new ImageManager(GdDriver::class);
        }

        return self::$manager;
    }
}
