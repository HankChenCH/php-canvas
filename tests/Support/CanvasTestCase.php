<?php

namespace HankChen\Canvas\Tests\Support;

use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;
use PHPUnit\Framework\TestCase;

/**
 * 图像相关测试的公共基类：像素取样、PNG 生成、缓存目录清理
 */
abstract class CanvasTestCase extends TestCase
{
    /**
     * 取某像素的 RGB 值（GD 驱动 pickColor 返回 [r,g,b,a]，alpha 因驱动而异，不参与比较）
     */
    protected function pixel(Image $image, int $x, int $y): array
    {
        $rgba = $image->pickColor($x, $y);

        return [(int) $rgba[0], (int) $rgba[1], (int) $rgba[2]];
    }

    protected function assertPixelSame(array $expected, Image $image, int $x, int $y): void
    {
        $this->assertSame(
            $expected,
            $this->pixel($image, $x, $y),
            "像素({$x}, {$y}) 颜色不符合预期"
        );
    }

    /**
     * 生成纯色 PNG 的二进制内容
     */
    protected function pngBytes(int $width, int $height, string $color): string
    {
        return ImageManagerStatic::canvas($width, $height, $color)
            ->encode('png')
            ->getEncoded();
    }

    /**
     * 源码中远程资源的缓存目录（sys_get_temp_dir()/canvas/<sub>）
     */
    protected function cacheDir(string $sub): string
    {
        return sys_get_temp_dir()
            . DIRECTORY_SEPARATOR . 'canvas'
            . DIRECTORY_SEPARATOR . $sub;
    }

    /**
     * 删除目录及其内容，保证每条用例都真正走到 mkdir 分支
     */
    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}
