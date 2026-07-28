<?php

namespace DeptOfScrapyardRobotics\Displays\GLFW\Concerns;

use DeptOfScrapyardRobotics\Displays\GLFW\GLFWDisplayException;
use Microscrap\Bindings\GLFW\Enums\TrueFalse;
use Microscrap\Bindings\GLFW\Enums\WindowAttrib;
use Microscrap\Bindings\GLFW\Enums\WindowHint;
use Fabricate\NutsAndBolts\Concerns\Splices16Bits;
use Microscrap\Bindings\GLFW\DataObjects\GlfwWindow;
use Fabricate\Contracts\NutsAndBolts\BootScaffolding;

trait GLFWWindowInternalAPI
{
    use BootScaffolding;
    use Splices16Bits;

    protected ?GlfwWindow $native_window = null;

    /**
     * @throws GLFWDisplayException
     */
    protected function requireNativeWindow(): GlfwWindow
    {
        if (is_null($this->native_window)) {
            throw GLFWDisplayException::notBooted();
        }

        return $this->native_window;
    }

    /**
     * Unpack RRGGBBAA via {@see Splices16Bits}.
     *
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    protected function unpackRgba(int $color): array
    {
        $rg = $this->splitBytes($color >> 16);
        $ba = $this->splitBytes($color);

        return [$rg['high'], $rg['low'], $ba['high'], $ba['low']];
    }

    /**
     * Open a GLFW window, make its GL context current, and show it.
     * Process-global glfwInit ownership: close() destroys the window only —
     * it does not call glfwTerminate() (other windows may still be alive).
     *
     * @throws GLFWDisplayException
     */
    protected function _boot(): void
    {
        if (! glfwInit()) {
            $error = glfwGetError();

            throw GLFWDisplayException::glfwFailure(
                'glfwInit',
                (int) ($error['code'] ?? 0),
                (string) ($error['description'] ?? ''),
            );
        }

        // macOS-friendly compatibility profile (matches extension proof_window).
        glfwDefaultWindowHints();
        glfwWindowHint(WindowHint::GLFW_CONTEXT_VERSION_MAJOR, 2);
        glfwWindowHint(WindowHint::GLFW_CONTEXT_VERSION_MINOR, 1);

        $window = glfwCreateWindow($this->width, $this->height, $this->title);

        if (is_null($window)) {
            $error = glfwGetError();

            throw GLFWDisplayException::glfwFailure(
                'glfwCreateWindow',
                (int) ($error['code'] ?? 0),
                (string) ($error['description'] ?? ''),
            );
        }

        glfwMakeContextCurrent($window);
        glfwSwapInterval(1);
        glfwShowWindow($window);
        // First show may take focus; further show()/FOCUS_ON_SHOW must not.
        glfwSetWindowAttrib($window, WindowAttrib::GLFW_FOCUS_ON_SHOW, TrueFalse::GLFW_FALSE->value);

        $this->native_window = $window;
        $this->pollEvents();
    }
}
