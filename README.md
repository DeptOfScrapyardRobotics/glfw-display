# dept-of-scrapyard-robotics/glfw-display

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dept-of-scrapyard-robotics/glfw-display.svg)](https://packagist.org/packages/dept-of-scrapyard-robotics/glfw-display)
[![License](https://img.shields.io/packagist/l/dept-of-scrapyard-robotics/glfw-display.svg)](LICENSE)

Drive GLFW-powered windows from ScrapyardIO.

This package registers a `glfw` windowed display panel (`GLFWWindow`) that pairs with [`microscrap/glfw-gfx`](https://packagist.org/packages/microscrap/glfw-gfx) for rendering. Use it when you want a desktop OpenGL window instead of (or in addition to) an embedded panel.

## Requirements

* PHP 8.3+
* **ext-glfw** ^0.5.0 — [php-io-extensions/glfw](https://github.com/php-io-extensions/glfw)
* [`microscrap/glfw-gfx`](https://packagist.org/packages/microscrap/glfw-gfx) ^0.6.0  
  (pulls in [`microscrap/glfw`](https://packagist.org/packages/microscrap/glfw))
* ScrapyardIO Framework 0.6 (`fabricate/displays`, …)

## Installation

Confirm the extension is loaded:

```bash
php -m | grep glfw
```

### From GLFW GFX (recommended)

If the app already has `microscrap/glfw-gfx`:

```bash
workshop install:glfw-display
```

That requires this package and runs `workshop config:glfw-display` afterward. The install command is **hidden** once this package is already listed in the app’s `composer.json`.

### Via Composer

```bash
composer require dept-of-scrapyard-robotics/glfw-display
php workshop package:discover
php workshop config:glfw-display
```

Package discovery registers `DeptOfScrapyardRobotics\Displays\GLFW\Providers\GLFWDisplayServiceProvider`.

## Workshop configuration command

```bash
workshop config:glfw-display
workshop config:glfw-display --force
```

Adds a default entry under `config/displays.php` → `windowed.glfw`:

```php
'windowed' => [
    'glfw' => [
        'width' => 1024,
        'height' => 768,
        'title' => env('APP_NAME'),
        'boot_now' => true,
    ],
],
```

The command is **hidden** when `config('displays.windowed.glfw')` already exists. Pass `--force` to overwrite that block.

## Point `main` at the window (optional)

To make GLFW the primary display:

```php
// config/displays.php
'main' => [
    'type' => 'windowed',
    'driver' => 'glfw',
    'renderer' => 'glfw',
    'buffer' => 'glfw-ogl',
],
```

And ensure `config/gfx.php` can resolve the GLFW engine:

```php
'rendering' => [
    'default' => 'glfw',
    'engines' => [
        'glfw' => [],
    ],
],
```

`workshop install:gfx --glfw --default=glfw --force` can set both of those for you when installing GFX.

## What it registers

| Display driver key | Class |
|---|---|
| `glfw` | `DeptOfScrapyardRobotics\Displays\GLFW\GLFWWindow` |

`GLFWWindow` implements Fabricate’s software panel / boot sequence contracts and expects the GLFW GFX renderer / `glfw-ogl` framebuffer.

## Fresh Scrapyard checklist

```bash
# 1. Extension
php -m | grep glfw

# 2. Rendering
workshop install:gfx --glfw --default=glfw

# 3. Windowed display (if not pulled in already)
workshop install:glfw-display

# 4. Confirm config
workshop config:show displays.windowed.glfw
workshop config:show gfx.rendering.default
```

## Stack overview

```text
ext-glfw
  └── microscrap/glfw
        └── microscrap/glfw-gfx
              └── dept-of-scrapyard-robotics/glfw-display  ← this package
```

## License

MIT
