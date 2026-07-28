<?php

namespace DeptOfScrapyardRobotics\Displays\GLFW;

use Fabricate\Contracts\Displays\DisplayException;

class GLFWDisplayException extends DisplayException
{
    public static function glfwFailure(string $operation, int $code = 0, string $description = ''): static
    {
        $detail = ($description === '') ? '' : " GLFW says: {$description}";
        $code_part = ($code === 0) ? '' : " (code {$code})";

        return new static("GLFW operation failed: {$operation}.{$code_part}{$detail}");
    }

    public static function notBooted(): static
    {
        return new static('The GLFW window has not been opened yet — boot() the window first.');
    }

    public static function unsupportedFrameFormat(
        string $pixel_format,
        int $bit_depth,
        string $scan_direction,
    ): static
    {
        return new static(
            "GLFW windows require a top-to-bottom, 32-bit row-major frame; received "
            ."{$pixel_format} at {$bit_depth} bits with {$scan_direction} scanning."
        );
    }

    public static function invalidFramePayload(int $expected, int $actual): static
    {
        return new static(
            "GLFW frame payload requires {$expected} bytes; received {$actual}."
        );
    }

    public static function invalidFrameDimensions(int $width, int $height): static
    {
        return new static(
            "GLFW frame dimensions must be positive; received {$width}x{$height}."
        );
    }
}
