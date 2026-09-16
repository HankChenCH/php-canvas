# AGENTS.md

## Purpose

PHP library (`hankchen/php-canvas`) for compositing images in a Photoshop-like layer model, built on `intervention/image` v4 and `endroid/qr-code` v6 (requires PHP `^8.3`). Namespace `HankChen\Canvas\` (PSR-4 → `src/`).

## Layout

- `src/Canvas.php` — entry point: holds layers in an `SplPriorityQueue` (sorted by priority), `render()` composites them onto the canvas, `graph()` returns a serializable layer-tree spec.
- `src/ImageManagerFactory.php` — shared `ImageManager` singleton; picks the Imagick driver when the extension is loaded, else GD. All image creation/decoding goes through it (v4 has no `ImageManagerStatic`).
- `src/Layer/AbstractLayer.php` — base class: box model (width/height incl. `'auto'`, padding, border, alignment, x/y position), `renderOutterBox()`/`renderInnerBox()` helpers, and the `graph()` serialization.
- `src/Layer/*.php` — `ImageLayer`, `TextLayer`, `QrCodeLayer`, and the `TableLayer` → `TableRowLayer` → `TableCellLayer` composition chain (cells wrap a content layer).
- `src/Contracts/DownloaderInterface.php` + `src/ResourceManagers/DefaultDownloader.php` — pluggable remote-resource fetching; layers accept a custom downloader via `setDownloader()`.

## Commands

Test suite: PHPUnit 9.6 (`tests/`, PSR-4 `HankChen\Canvas\Tests\` → `tests/`, config in `phpunit.xml.dist`). Run with:

```sh
composer install
composer test          # or: vendor/bin/phpunit
php -l src/Layer/TextLayer.php   # syntax check changed files
```

On this machine Homebrew PHP is keg-only (`php` is not on PATH); prefix it first. The library requires PHP `^8.3` (intervention/image v4 needs it), so use the 8.3 keg:

```sh
export PATH="$(brew --prefix)/opt/php@8.3/bin:$PATH"   # 8.1/8.2 also installed but too old for this library
```

Follow TDD for behavior changes: write/extend a failing test first (red), fix `src/` until green (refactor after). Tests run on the GD driver when Imagick is absent; the `pixel()` helper in `CanvasTestCase` wraps v4's `colorAt()` (returns a `ColorInterface`, channels read via `->channels()[n]->value()`). `tests/Support/CanvasTestCase.php` provides pixel-sampling and cache-dir cleanup helpers; `tests/Support/FakeDownloader.php` stubs remote downloads without network. For broader behavior checks, write a throwaway script that calls `Canvas::make(...)->render()->save(...)` and inspect the output image.

## Rules and gotchas

- **intervention/image v4 API only.** v4 removed `ImageManagerStatic`; create/decode images via `ImageManagerFactory::make()` (`createImage()` / `decode()`). Key renames/moves to keep in mind when editing: `Image` type → `Intervention\Image\Interfaces\ImageInterface`; `canvas(w,h,bg)` → `createImage(w,h)` + `fill(bg)`; `make()` → `decode()`; `orientate()` → `orient()`; `fit()` → `cover()`; `getWidth()/getHeight()/pickColor()` on images → `width()/height()/colorAt()`; line drawing → `drawLine()` closures over `Geometry\Factories\LineFactory` (`from()->to()->width()->color()`); text closures receive `Typography\FontFactory` (`file()/size()/color()/align(h,v)/angle()`) — NOT `FontInterface`. `insert()` argument order changed to `insert($image, $x, $y, $position)` (position strings like `'top-left'` still work). GD built-in numeric font ids (`'1'`, v2 default) no longer exist: `TextLayer` treats numeric fonts as "no file" and falls back to v4's built-in default font.
- **endroid/qr-code v6 value object.** `QrCode` is constructed with named arguments (`new QrCode(data: ..., size: ..., margin: ...)`) — no `QrCode::create()`/setters. `ErrorCorrectionLevel` and `RoundBlockSizeMode` are enums in the top-level `Endroid\QrCode\` namespace (no sub-namespace); output via `(new PngWriter())->write($qrCode)->getDataUri()`.
- **`SplPriorityQueue` iteration is destructive.** `Canvas::render()` and `Canvas::graph()` each drain the queue; a Canvas instance cannot be rendered twice.
- **Driver:** Imagick when the extension is loaded, else GD. Text metrics and rendering differ between drivers — don't assume pixel-exact parity.
- **Remote images/fonts** are cached under `sys_get_temp_dir()/canvas/` keyed by URL basename only: different URLs sharing a basename collide, and there is no cache invalidation. `AbstractLayer::ensureCacheDir()` creates the cache dir with 0755 and `chmod`-heals 0644 dirs left by pre-2026.09 versions (those lacked the execute bit, breaking all remote downloads). `DefaultDownloader` uses `file_get_contents` (needs `allow_url_fopen`); inject a `DownloaderInterface` for anything stricter.
- **`'auto'` width/height** are strings that set `autoWidth`/`autoHeight` flags; several layers override `getHeight()` to compute from content (e.g. `TextLayer` with `autowrap`, `QrCodeLayer`). Container layers sync child sizes in `addRow`/`addCell`/`addContentLayer` — preserve those contracts when editing.
- **`graph()` is a public serialization surface** (layer-tree spec with `spec`/`data` keys). If you add a layer property, decide whether it belongs in `graph()` and keep all layer `graph()` overrides consistent.
- Chinese is the working language for code comments; commit messages use conventional-commit prefixes (`feat(scope):`, `fix:`, `refactor:`) with Chinese subjects. Feature work happens on a `dev` branch merged to `main` via PR.
