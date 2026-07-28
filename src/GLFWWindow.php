<?php

namespace DeptOfScrapyardRobotics\Displays\GLFW;

use DeptOfScrapyardRobotics\Displays\GLFW\Concerns\GLFWWindowAPI;
use Exception;
use Fabricate\Contracts\Displays\Interfaces\SoftwarePanel;
use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\Endianness;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Enums\ScanDirection;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\NutsAndBolts\BootSequence;
use Fabricate\Contracts\Rendering\GFXRenderer;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\FormatSpec;
use Microscrap\Bindings\GLFW\Enums\ClearBufferMask;
use Microscrap\Bindings\GLFW\Enums\EnableCap;
use Microscrap\GFX\GLFW\GLFWGfx;
use Microscrap\GFX\GLFW\GLFWOpenGLFramebuffer;

class GLFWWindow implements SoftwarePanel, BootSequence
{
    use GLFWWindowAPI;

    /**
     * @throws Exception
     */
    public function __construct(
        protected int $width,
        protected int $height,
        protected string $title = 'ScrapyardIO',
        bool $boot_now = false,
    ) {
        if ($boot_now) {
            $this->boot();
        }
    }

    public function height(): int
    {
        return $this->height;
    }

    public function width(): int
    {
        return $this->width;
    }

    public function supportsRenderer(GFXRenderer $renderer): bool
    {
        return $renderer instanceof GLFWGfx;
    }

    public function supportsFramebuffer(Framebuffer $framebuffer): bool
    {
        return $framebuffer instanceof GLFWOpenGLFramebuffer;
    }

    /**
     * Destroy the native window. Does not call glfwTerminate().
     */
    public function close(): void
    {
        if (! is_null($this->native_window)) {
            glfwDestroyWindow($this->native_window);
            $this->native_window = null;
        }

        $this->booted = false;
    }

    /**
     * Row-major RGBA8888, red byte first — identical to the spec the SDL3
     * GFX renderer dumps, so default component wiring never transcodes.
     */
    public function formatSpec(): FormatSpec
    {
        return new FormatSpec(
            PixelFormat::ROW_MAJOR,
            BitDepth::B32,
            ScanDirection::TOP_TO_BOTTOM,
            endianness: Endianness::MSB,
        );
    }

    public function transmit(DumpedBuffer $frame): void
    {
        if (
            $frame->metadata->pixel_format !== PixelFormat::ROW_MAJOR
            || $frame->metadata->bit_depth !== BitDepth::B32
            || $frame->metadata->scan_direction !== ScanDirection::TOP_TO_BOTTOM
        ) {
            throw GLFWDisplayException::unsupportedFrameFormat(
                $frame->metadata->pixel_format->value,
                $frame->metadata->bit_depth->value,
                $frame->metadata->scan_direction->name,
            );
        }

        $width = $frame->width ?? $this->width;
        $height = $frame->height ?? $this->height;

        if ($width <= 0 || $height <= 0) {
            throw GLFWDisplayException::invalidFrameDimensions($width, $height);
        }

        $expected_bytes = $width * $height * 4;

        if (count($frame->raw_data) !== $expected_bytes) {
            throw GLFWDisplayException::invalidFramePayload(
                $expected_bytes,
                count($frame->raw_data),
            );
        }

        $window = $this->requireNativeWindow();
        glfwMakeContextCurrent($window);

        $drawable = glfwGetFramebufferSize($window);
        $drawable_width = max(1, (int) ($drawable['width'] ?? $this->width));
        $drawable_height = max(1, (int) ($drawable['height'] ?? $this->height));
        $scale_x = $drawable_width / max(1, $this->width);
        $scale_y = $drawable_height / max(1, $this->height);
        glViewport(0, 0, $drawable_width, $drawable_height);

        $msb_first = $frame->metadata->endianness !== Endianness::LSB;

        for ($y = 0; $y < $height; $y++) {
            $run_start = 0;
            $run_color = $this->pixelAt($frame->raw_data, $y * $width, $msb_first);

            for ($x = 1; $x <= $width; $x++) {
                $color = $x < $width
                    ? $this->pixelAt($frame->raw_data, ($y * $width) + $x, $msb_first)
                    : null;

                if ($color === $run_color) {
                    continue;
                }

                $this->drawRun(
                    $frame->origin_x + $run_start,
                    $frame->origin_y + $y,
                    $x - $run_start,
                    $run_color,
                    $drawable_height,
                    $scale_x,
                    $scale_y,
                );

                $run_start = $x;
                $run_color = $color;
            }
        }

        $this->present();
    }

    /**
     * @param array<int, int> $bytes
     */
    protected function pixelAt(array $bytes, int $pixel, bool $msb_first): int
    {
        $offset = $pixel * 4;

        if ($msb_first) {
            return ($bytes[$offset] << 24)
                | ($bytes[$offset + 1] << 16)
                | ($bytes[$offset + 2] << 8)
                | $bytes[$offset + 3];
        }

        return $bytes[$offset]
            | ($bytes[$offset + 1] << 8)
            | ($bytes[$offset + 2] << 16)
            | ($bytes[$offset + 3] << 24);
    }

    protected function drawRun(
        int $x,
        int $y,
        int $width,
        int $color,
        int $drawable_height,
        float $scale_x,
        float $scale_y,
    ): void {
        $pixel_x = (int) round($x * $scale_x);
        $pixel_y = (int) round($y * $scale_y);
        $pixel_width = max(1, (int) round($width * $scale_x));
        $pixel_height = max(1, (int) round($scale_y));
        $gl_y = $drawable_height - $pixel_y - $pixel_height;
        [$red, $green, $blue, $alpha] = $this->unpackRgba($color);

        glClearColor($red / 255.0, $green / 255.0, $blue / 255.0, $alpha / 255.0);
        glEnable(EnableCap::GL_SCISSOR_TEST);
        glScissor($pixel_x, $gl_y, $pixel_width, $pixel_height);
        glClear(ClearBufferMask::GL_COLOR_BUFFER_BIT);
        glDisable(EnableCap::GL_SCISSOR_TEST);
    }
}
