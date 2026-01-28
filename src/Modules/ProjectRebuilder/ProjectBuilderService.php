<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\ProjectRebuilder;

use quintenmbusiness\LaravelAnalyzer\Tools\LaravelProjectDTO;

class ProjectBuilderService
{
    protected LaravelProjectDTO $project;

    public array $essentialFiles = [
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

    public function buildCopy(bool $withLock = false): void
    {
        if($withLock) {
            $this->essentialFiles[] = 'composer.lock';
        }

        $basePath = rtrim($this->project->basePath, '/');
        $projectName = basename($basePath);
        $parentPath = dirname($basePath);
        $copyPath = $parentPath . '/' . $projectName . '_copy';

        // if folder exists, append timestamp to avoid deleting anything
        if (is_dir($copyPath)) {
            $timestamp = date('Ymd_His');
            $copyPath = $copyPath . '_' . $timestamp;
        }

        mkdir($copyPath, 0755, true);

        $this->copyEssentialFiles($copyPath, $basePath);
        $this->runComposerInstall($copyPath);

        echo "Project copy created at: $copyPath\n";
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
