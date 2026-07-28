<?php

namespace DeptOfScrapyardRobotics\Displays\GLFW\Providers;

use DeptOfScrapyardRobotics\Displays\GLFW\GLFWWindow;
use Fabricate\NutsAndBolts\MagicAliases\Display;
use Fabricate\NutsAndBolts\ServiceProvider;

class GLFWDisplayServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Display::addWPanel('glfw', GLFWWindow::class);
    }
}