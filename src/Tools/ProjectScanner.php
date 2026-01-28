<?php

namespace quintenmbusiness\LaravelAnalyzer\Tools;

class ProjectScanner
{
    protected string $basePath;
    protected array $excluded = [
        'vendor',
        'node_modules',
        '.git',
        '.idea',
        '.vscode',
    ];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function scan(): LaravelProjectDTO
    {
        $dto = new LaravelProjectDTO();

        $dto->rootFiles = $this->scanDirectory($this->basePath, true);

        $dto->app = $this->fillNested('app', $dto->app);
        $dto->bootstrap = $this->scanDirectory($this->basePath . '/bootstrap');
        $dto->config = $this->scanDirectory($this->basePath . '/config');
        $dto->database = $this->fillNested('database', $dto->database);
        $dto->lang = $this->scanDirectory($this->basePath . '/lang');
        $dto->public = $this->fillNested('public', $dto->public);
        $dto->resources = $this->fillNested('resources', $dto->resources);
        $dto->routes = $this->fillNested('routes', $dto->routes);
        $dto->storage = $this->fillNested('storage', $dto->storage);
        $dto->tests = $this->fillNested('tests', $dto->tests);
        $dto->tools = $this->fillNested('tools', $dto->tools);
        $dto->docs = $this->scanDirectory($this->basePath . '/docs');

        return $dto;
    }

    protected function fillNested(string $folder, array $structure): array
    {
        $path = $this->basePath . '/' . $folder;
        if (!is_dir($path)) {
            return $structure;
        }

        foreach ($structure as $key => $value) {
            $subPath = $path . '/' . $key;
            if (is_array($value) && is_dir($subPath)) {
                $structure[$key] = $this->scanDirectory($subPath, false);
            }
        }

        return $structure;
    }

    protected function scanDirectory(string $path, bool $root = false): array
    {
        $result = [];
        if (!is_dir($path)) {
            return $result;
        }

        $files = scandir($path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || in_array($file, $this->excluded)) {
                continue;
            }

            $fullPath = $path . '/' . $file;

            if (is_dir($fullPath)) {
                $result[$file] = $this->scanDirectory($fullPath, false);
            } else {
                if ($root && !str_starts_with($file, '.')) {
                    $result[] = $file;
                } else {
                    $result[] = $file;
                }
            }
        }

        return $result;
    }
}
