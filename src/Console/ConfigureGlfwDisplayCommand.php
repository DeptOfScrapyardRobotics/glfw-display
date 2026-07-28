<?php

namespace DeptOfScrapyardRobotics\Displays\GLFW\Console;

use Fabricate\Console\Command;
use Fabricate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'config:glfw-display')]
class ConfigureGlfwDisplayCommand extends Command
{
    protected ?string $signature = 'config:glfw-display
                    {--force : Overwrite an existing windowed.glfw entry}';

    protected string $description = 'Add a default GLFW entry to config/displays.php windowed displays';

    public function isHidden(): bool
    {
        return $this->hasWindowedEntry();
    }

    public function handle(): int
    {
        $path = $this->scrapyard_io->configPath('displays.php');
        $files = new Filesystem;

        if (! $files->exists($path)) {
            $this->components->error("Missing configuration file [{$path}].");

            return self::FAILURE;
        }

        if ($this->hasWindowedEntry() && ! $this->option('force')) {
            $this->components->info('Windowed GLFW display configuration already exists.');
            $this->offerToSetMainDisplay();

            return self::SUCCESS;
        }

        if (! $this->writeWindowedEntry($files, $path)) {
            $this->components->error('Unable to update [config/displays.php] with a GLFW windowed entry.');

            return self::FAILURE;
        }

        $this->components->info('Added default [windowed.glfw] display configuration.');
        $this->offerToSetMainDisplay();

        return self::SUCCESS;
    }

    protected function offerToSetMainDisplay(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        $application = $this->getApplication();

        if (is_null($application) || ! $application->has('config:main-display')) {
            return;
        }

        if (! confirm('Would you like to set the main display now?', default: true)) {
            return;
        }

        $this->call('config:main-display', [
            'display' => 'glfw',
            '--force' => true,
        ]);
    }

    protected function hasWindowedEntry(): bool
    {
        if (! isset($this->scrapyard_io) || ! $this->scrapyard_io->bound('config')) {
            return false;
        }

        $windowed = $this->scrapyard_io['config']->get('displays.windowed', []);

        return is_array($windowed) && array_key_exists('glfw', $windowed);
    }

    protected function writeWindowedEntry(Filesystem $files, string $path): bool
    {
        $contents = $files->get($path);
        $entry = $this->defaultEntrySnippet();

        if ($this->option('force') && preg_match("/['\"]glfw['\"]\\s*=>\\s*\\[/", $contents) === 1) {
            $replaced = preg_replace(
                "/['\"]glfw['\"]\\s*=>\\s*\\[(?:[^\\[\\]]*(?:\\[[^\\[\\]]*\\][^\\[\\]]*)*)*\\],?/",
                trim($entry),
                $contents,
                1,
            );

            if (is_null($replaced) || $replaced === $contents) {
                return false;
            }

            $files->put($path, $replaced);

            return true;
        }

        if (preg_match("/['\"]windowed['\"]\\s*=>\\s*\\[/", $contents) === 1) {
            $updated = preg_replace(
                "/(['\"]windowed['\"]\\s*=>\\s*\\[)/",
                "$1\n".$entry,
                $contents,
                1,
            );

            if (is_null($updated) || $updated === $contents) {
                return false;
            }

            $files->put($path, $updated);

            return true;
        }

        $windowedBlock = <<<PHP
    'windowed' => [
{$entry}    ],
PHP;

        $updated = preg_replace('/return\\s*\\[/', "return [\n".$windowedBlock, $contents, 1);

        if (is_null($updated) || $updated === $contents) {
            return false;
        }

        $files->put($path, $updated);

        return true;
    }

    protected function defaultEntrySnippet(): string
    {
        return <<<'PHP'
        'glfw' => [
            'width' => 1024,
            'height' => 768,
            'title' => env('APP_NAME'),
            'boot_now' => true,
        ],
PHP;
    }
}
