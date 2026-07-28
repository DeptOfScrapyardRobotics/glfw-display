<?php

namespace DeptOfScrapyardRobotics\Displays\GLFW\Concerns;

use Microscrap\Bindings\GLFW\DataObjects\GlfwWindow;
use Microscrap\Bindings\GLFW\Enums\ClearBufferMask;

trait GLFWWindowAPI
{
    use GLFWWindowInternalAPI;

    public function nativeWindow(): ?GlfwWindow
    {
        return $this->native_window;
    }

    public function show(): static
    {
        if (! is_null($this->native_window)) {
            glfwShowWindow($this->native_window);
        }

        return $this;
    }

    public function pollEvents(): static
    {
        glfwPollEvents();

        return $this;
    }

    public function swapBuffers(): static
    {
        if (! is_null($this->native_window)) {
            glfwMakeContextCurrent($this->native_window);
            glfwSwapBuffers($this->native_window);
        }

        return $this;
    }

    /**
     * Window-level GL clear (RGBA8888 word → normalized floats).
     *
     * @throws GLFWDisplayException
     */
    public function clear(int $color = 0x000000FF): static
    {
        $window = $this->requireNativeWindow();
        glfwMakeContextCurrent($window);
        [$r, $g, $b, $a] = $this->unpackRgba($color);
        glClearColor($r / 255.0, $g / 255.0, $b / 255.0, $a / 255.0);
        glClear(ClearBufferMask::GL_COLOR_BUFFER_BIT);

        return $this;
    }

    /**
     * Swap the GL backbuffer and poll events so the frame is visible.
     *
     * Does not show/raise/focus the window — that is boot-only so the OS
     * focus owner is respected after the user switches apps.
     *
     * @throws GLFWDisplayException
     */
    public function present(): static
    {
        $this->requireNativeWindow();
        $this->swapBuffers();
        $this->pollEvents();

        return $this;
    }

    /**
     * True after the user hits the window chrome close control (or an
     * equivalent glfwSetWindowShouldClose).
     */
    public function shouldClose(): bool
    {
        if (is_null($this->native_window)) {
            return true;
        }

        $this->pollEvents();

        return glfwWindowShouldClose($this->native_window);
    }
}
