# AGENTS.md

## Purpose

PHP library (`hankchen/php-canvas`) for compositing images in a Photoshop-like layer model, built on `intervention/image` v2 and `endroid/qr-code` v4. Namespace `HankChen\Canvas\` (PSR-4 → `src/`).

## Layout

- `src/Canvas.php` — entry point: holds layers in an `SplPriorityQueue` (sorted by priority), `render()` composites them onto the canvas, `graph()` returns a serializable layer-tree spec.
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

On this machine Homebrew PHP is keg-only (`php` is not on PATH); prefix it first:

```sh
export PATH="$(brew --prefix)/opt/php@8.2/bin:$PATH"   # 8.1/8.3 also installed
```

Follow TDD for behavior changes: write/extend a failing test first (red), fix `src/` until green (refactor after). Tests run on the GD driver when Imagick is absent; `pickColor()` returns `[r,g,b,a]` arrays on GD. `tests/Support/CanvasTestCase.php` provides pixel-sampling and cache-dir cleanup helpers; `tests/Support/FakeDownloader.php` stubs remote downloads without network. For broader behavior checks, write a throwaway script that calls `Canvas::make(...)->render()->save(...)` and inspect the output image.

## Rules and gotchas

- **intervention/image v2 API only.** The code uses `ImageManagerStatic`, `AbstractFont`, `->fit()`, `->orientate()` — all v2 idioms. Do not "modernize" to v3 (different namespace, manager, font API); it would break.
- **`SplPriorityQueue` iteration is destructive.** `Canvas::render()` and `Canvas::graph()` each drain the queue; a Canvas instance cannot be rendered twice.
- **Driver:** Imagick when the extension is loaded, else GD. Text metrics and rendering differ between drivers — don't assume pixel-exact parity.
- **Remote images/fonts** are cached under `sys_get_temp_dir()/canvas/` keyed by URL basename only: different URLs sharing a basename collide, and there is no cache invalidation. `AbstractLayer::ensureCacheDir()` creates the cache dir with 0755 and `chmod`-heals 0644 dirs left by pre-2026.09 versions (those lacked the execute bit, breaking all remote downloads). `DefaultDownloader` uses `file_get_contents` (needs `allow_url_fopen`); inject a `DownloaderInterface` for anything stricter.
- **`'auto'` width/height** are strings that set `autoWidth`/`autoHeight` flags; several layers override `getHeight()` to compute from content (e.g. `TextLayer` with `autowrap`, `QrCodeLayer`). Container layers sync child sizes in `addRow`/`addCell`/`addContentLayer` — preserve those contracts when editing.
- **`graph()` is a public serialization surface** (layer-tree spec with `spec`/`data` keys). If you add a layer property, decide whether it belongs in `graph()` and keep all layer `graph()` overrides consistent.
- Chinese is the working language for code comments; commit messages use conventional-commit prefixes (`feat(scope):`, `fix:`, `refactor:`) with Chinese subjects. Feature work happens on a `dev` branch merged to `main` via PR.
