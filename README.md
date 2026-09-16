# php-canvas

[![Tests](https://github.com/HankChenCH/php-canvas/actions/workflows/tests.yml/badge.svg)](https://github.com/HankChenCH/php-canvas/actions/workflows/tests.yml)
![PHP](https://img.shields.io/badge/PHP-%E2%89%A58.3-777BB4?logo=php&logoColor=white)

A PHP library for drawing images, similar to using layers.

用 Photoshop 式「图层」模型合成图片的 PHP 库。底层基于
[intervention/image](https://github.com/Intervention/image) v4 与
[endroid/qr-code](https://github.com/endroid/qr-code) v6。

## 特性

- **图层化合成**：图片、文字、二维码、表格作为独立图层堆叠，`priority` 控制层级
- **盒模型布局**：宽高（支持 `'auto'`）、CSS 简写内边距、四边边框、水平/垂直对齐、绝对定位
- **远程资源**：图片与字体 URL 自动下载并缓存到临时目录，可注入自定义下载器
- **自动折行**：`TextLayer` 按全角/半角字宽自动折行，支持行高与文字旋转
- **二维码**：`QrCodeLayer` 一键生成二维码并作为图层参与合成（UTF-8、高容错、零边距）
- **`graph()` 序列化**：整棵图层树输出为结构化数组（JSON 友好），适合作为前端渲染协议

## 环境要求

- PHP `>= 8.3`，且启用 `ext-mbstring`
- GD 或 Imagick 扩展（两者都装时**自动优先 Imagick**）；二维码 PNG 输出依赖 GD
- `ext-exif`（可选）：带 EXIF 方向信息的 JPEG 自动转正
- 默认远程下载使用 `file_get_contents`，需要 `allow_url_fopen`（可通过注入下载器绕开）

## 安装

```sh
composer require hankchen/php-canvas
```

## 快速开始

```php
use HankChen\Canvas\Canvas;
use HankChen\Canvas\Layer\ImageLayer;
use HankChen\Canvas\Layer\TextLayer;

$fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'; // 真实 TTF，见「驱动差异」

$canvas = Canvas::make(750, 1334,
    // priority 越大越先渲染（越靠底层），背景层给最大的优先级
    ImageLayer::make(750, 1334, '#ffffff')
        ->setImage('https://example.com/bg.jpg')   // 远程图片自动下载并缓存
        ->setPriority(100),

    ImageLayer::make(300, 300)
        ->setImage('/path/to/avatar.png')
        ->setPosition(60, 200)
        ->setPriority(10),

    TextLayer::make(630, 'auto')                   // 高度 auto：按行高自动计算
        ->setText('Hello php-canvas')
        ->setFont($fontFile, 32, '#333333')
        ->setPosition(60, 560)
        ->setPriority(10),
);

$canvas->render()->save('/path/to/output.png');    // 按扩展名输出（png/jpg...）
```

图层也可以在画布创建后追加，`addLayer()` 返回 `$this` 支持链式调用：

```php
$canvas->addLayer($anotherLayer);
```

## 图层类型

所有图层都通过 `Layer::make($width, $height, $backgroundColor)` 创建，
`$width`/`$height` 传 `'auto'`（大小写不敏感）表示由内容决定尺寸。

### ImageLayer

```php
use HankChen\Canvas\Layer\ImageLayer;

ImageLayer::make(300, 300, '#0000ff')
    ->setImage('/path/to/img.png')     // 本地路径或 URL（URL 自动下载并缓存）
    ->setPadding(0, 10)                // 内容盒左右各收 10px
    ->setHorizontalAlign('left');      // 图片在内容盒内左对齐（默认 center/center）
```

源图按 `cover` 语义（裁切放大铺满）填进内容盒。

### TextLayer

```php
use HankChen\Canvas\Layer\TextLayer;

TextLayer::make(630, 'auto', '#ffffff')
    ->setText($longText)
    ->setFont('/path/to/font.ttf', 24, '#333333') // 字体也支持 URL，自动缓存
    ->setAutowrap(true)                // 按内容盒宽度自动折行（半角字符按 0.55 计宽）
    ->setLineHeight(1.5)               // 行高倍数，auto 高度随之变化
    ->setAngle(0);
```

高度为 `'auto'` 时：单行文字高度 = `ceil(字号 × 行高)`；开启 autowrap 后按折行行数计算，
显式 `\n` 也会强制分行。文本在盒内的默认对齐为水平 `left`、垂直 `bottom`。

### QrCodeLayer

```php
use HankChen\Canvas\Layer\QrCodeLayer;

$qr = QrCodeLayer::make(200);          // 宽 200，正方形，高度跟随宽度
$qr->generateQrCodeLayerFromContent('https://example.com');
$qr->setPosition(520, 1100);

$canvas->addLayer($qr);
```

内部参数：UTF-8 编码、`High` 容错级别、零边距、黑码白底，生成后作为图片图层插入。

### TableLayer / TableRowLayer / TableCellLayer

表格由三层组合而成：行加入表格（宽度同步为表格宽）、单元格加入行（行高增长到最高单元格）、
内容层加入单元格（宽度同步；单元格为 auto 高度时高度跟随内容，否则内容层被压成同高）。

```php
use HankChen\Canvas\Layer\TableLayer;
use HankChen\Canvas\Layer\TableRowLayer;
use HankChen\Canvas\Layer\TableCellLayer;

$table = TableLayer::make(600, 200, '#ffffff');
$table->addRow(
    TableRowLayer::make('auto', 60)->addCell(
        TableCellLayer::make(300, 60, '#f5f5f5')->addContentLayer(
            TextLayer::make(280, 'auto')
                ->setText('单元格内容')
                ->setFont($fontFile, 20, '#333333')
        )
    )
);
$table->addRow(
    TableRowLayer::make('auto', 60)->addCell(
        TableCellLayer::make(600, 60, '#eeeeee')
    )
);
```

长列表场景可用 `TableLayer::isOverHeight($row)` 判断累加行高是否已超出表格高度。

## 盒模型与布局（所有图层通用）

| Setter | 说明 |
| --- | --- |
| `setWidth($w)` / `setHeight($h)` | 整数像素；传 `'auto'` 开启自动宽/高 |
| `setPadding(...$args)` | CSS 简写顺序：1 个参数四边、2 个为「上下，左右」、3 个为「上，左右，下」、4 个为「上，右，下，左」 |
| `setBorder($w, $color)` / `setBorderTop/Bottom/Left/Right()` | 四边边框，传宽度 `0` 清除对应边 |
| `setHorizontalAlign($v)` | `left` / `center` / `right`（内容在盒内的对齐） |
| `setVerticalAlign($v)` | `top` / `center` / `bottom`（各图层默认值不同，如 TextLayer 默认 `bottom`） |
| `setPosition($x, $y, $position)` | 在画布/父容器上的锚点定位，`$position` 形如 `top-left`（默认）、`center`、`bottom-right` 等 |
| `setPriority($p)` | 数值越大越先渲染（越靠底层） |
| `setDownloader($downloader)` | 注入自定义远程资源下载器 |
| `setBackground($color)` | 背景色，不设则为透明 |

## graph()：图层树序列化

`Canvas::graph()` / 每个图层的 `graph()` 输出结构化描述，适合存库或下发给前端渲染引擎：

```php
$spec = $canvas->graph();
// [
//   'canvas' => ['width' => 750, 'height' => 1334],
//   'layers' => [
//     [
//       'type' => 'TextLayer',
//       'priority' => 10,
//       'spec' => ['shape' => [...], 'align' => [...], 'position' => [...], 'fontFamily' => [...]],
//       'data' => ['valueType' => 'StaticValue', 'value' => 'Hello php-canvas'],
//     ],
//     ...
//   ],
// ]
```

> **注意**：内部使用 `SplPriorityQueue`，迭代是破坏性的——同一 Canvas 实例的
> `render()` 和 `graph()` 各自会耗尽队列，**不能重复调用**（先 graph 后 render，
> 或需要重复输出时请重建实例/图层）。

## 远程资源缓存与自定义下载器

远程图片/字体下载后缓存于 `sys_get_temp_dir()/canvas/{img_layers,text_layers}/`，
键为 URL 的 basename：**不同 URL 若 basename 相同会互相覆盖命中，且没有失效机制**。

对缓存策略或网络环境有要求时，实现下载器接口并注入：

```php
use HankChen\Canvas\Contracts\DownloaderInterface;

$layer->setDownloader(new class implements DownloaderInterface {
    public function download($url)
    {
        return file_get_contents($url); // 换成带超时/代理/签名的实现
    }
});
```

## 驱动差异注意（重要）

- 服务器**同时装有 GD 与 Imagick 时自动使用 Imagick**，否则用 GD。
- **Imagick 驱动没有内置字体机制**：`TextLayer` 未通过 `setFont()` 设置真实字体文件时，
  渲染会抛出 `No font file specified` 异常。生产环境（尤其 Imagick 驱动）请务必为
  文字图层指定真实 TTF；GD 驱动下未设字体仅以内置点阵字体兜底（不随字号缩放，只适合调试）。
- 两种驱动的文字度量与渲染像素存在差异，不要假设像素级一致。

## 测试

```sh
composer install
composer test             # PHPUnit 单元测试
composer test:coverage    # 生成覆盖率报告（需 pcov/xdebug，或用 phpdbg）
composer check-coverage   # 校验语句覆盖率 ≥ 80%（CI 门禁同款脚本）
```

CI（GitHub Actions）在 PHP 8.3 / 8.4 矩阵上运行，并强制语句覆盖率不低于 80%。
CI 环境预装 Imagick（验证 Imagick 驱动路径），本地开发环境通常覆盖 GD 路径，两者互补。
