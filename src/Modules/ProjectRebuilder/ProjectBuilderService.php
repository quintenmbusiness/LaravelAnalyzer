<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\ProjectRebuilder;

use quintenmbusiness\LaravelAnalyzer\Tools\LaravelProjectDTO;

class ProjectBuilderService
{
    protected LaravelProjectDTO $project;

    protected array $essentialFiles = [
        'composer.json',
        '.env',
        '.env.example',
        'artisan',
        'package.json',
        'webpack.mix.js',
        'vite.config.js',
        'phpunit.xml',
        'README.md',
    ];

    public function __construct(LaravelProjectDTO $project)
    {
        $this->project = $project;
    }

    public function buildCopy(): void
    {
        $basePath = rtrim($this->project->basePath, '/');
        $projectName = basename($basePath);
        $parentPath = dirname($basePath);
        $copyPath = $parentPath . '/' . $projectName . '_copy';

        if (!@mkdir($copyPath, 0755, true)) {
            $this->clearDirectory($copyPath);
        }

        $this->copyEssentialFiles($copyPath, $basePath);
        $this->runComposerInstall($copyPath);

        echo "Project copy created at: $copyPath\n";
    }

    protected function clearDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->deleteRecursive($path);
                @chmod($path, 0755);
                @rmdir($path);
            } else {
                @chmod($path, 0666);
                @unlink($path);
            }
        }
    }

    protected function deleteRecursive(string $dir): void
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->deleteRecursive($path);
                @chmod($path, 0755);
                @rmdir($path);
            } else {
                @chmod($path, 0666);
                @unlink($path);
            }
        }
    }

    protected function copyEssentialFiles(string $destination, string $sourceBase): void
    {
        foreach ($this->essentialFiles as $file) {
            $sourceFile = $sourceBase . '/' . $file;
            if (file_exists($sourceFile)) {
                copy($sourceFile, $destination . '/' . $file);
            }
        }
    }

    protected function runComposerInstall(string $path): void
    {
        $currentDir = getcwd();
        chdir($path);

        exec('composer install --no-interaction', $output, $returnVar);

        chdir($currentDir);

        if ($returnVar !== 0) {
            echo "Composer install failed.\n";
        }
    }
}
