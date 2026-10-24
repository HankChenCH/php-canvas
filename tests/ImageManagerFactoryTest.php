<?php

namespace HankChen\Canvas\Tests;

use HankChen\Canvas\ImageManagerFactory;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;

class ImageManagerFactoryTest extends TestCase
{
    public function testManagerIsSharedAndUsesAvailableDriver()
    {
        $manager = ImageManagerFactory::make();

        // 管理器在进程内复用，与 v2 ImageManagerStatic 的单例行为保持一致
        $this->assertInstanceOf(ImageManager::class, $manager);
        $this->assertSame($manager, ImageManagerFactory::make());

        // 装有 Imagick 扩展时优先使用，否则回退 GD
        $expectedDriver = class_exists('\Imagick')
            ? ImagickDriver::class
            : GdDriver::class;
        $this->assertInstanceOf($expectedDriver, $manager->driver);
    }

    public function testCreatesBlankImageWithDeclaredSize()
    {
        $image = ImageManagerFactory::make()->createImage(7, 5);

        $this->assertSame(7, $image->width());
        $this->assertSame(5, $image->height());
    }
}
