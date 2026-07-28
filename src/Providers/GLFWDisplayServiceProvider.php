<?php

namespace DeptOfScrapyardRobotics\Displays\GLFW\Providers;

use DeptOfScrapyardRobotics\Displays\GLFW\Console\ConfigureGlfwDisplayCommand;
use DeptOfScrapyardRobotics\Displays\GLFW\GLFWWindow;
use Fabricate\NutsAndBolts\MagicAliases\Display;
use Fabricate\NutsAndBolts\ServiceProvider;

class GLFWDisplayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->program->singleton(ConfigureGlfwDisplayCommand::class);

        $this->commands([
            ConfigureGlfwDisplayCommand::class,
        ]);
    }

    public function boot(): void
    {
        Display::addWPanel('glfw', GLFWWindow::class);
    }
}