<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Translation;

use Illuminate\Support\Facades\File;

class ViewTranslationScanner
{
    protected string $basePath;

    public function __construct(string $projectBasePath = null)
    {
        $this->basePath = $projectBasePath ?: base_path();
    }

    /**
     * Given a list of view usages from controllers, extract all translation keys used inside.
     *
     * @param array $viewUsages [
     *   ['controller' => ..., 'method' => ..., 'view' => ..., 'params' => [...]]
     * ]
     * @return array [
     *   'full/blade/path.blade.php' => [
     *       'translations' => [
     *           'file' => ['key1', 'key2'],
     *       ],
     *       'controllers' => [
     *           [
     *               'controller' => 'ControllerName',
     *               'method' => 'methodName',
     *               'params' => [...],
     *           ],
     *           ...
     *       ]
     *   ]
     * ]
     */
    public function getTranslationsInViews(array $viewUsages): array
    {
        $translationsInViews = [];

        foreach ($viewUsages as $usage) {
            $viewName = $usage['view'];
            $controllerName = $usage['controller_method'];
            $methodName = $usage['method'];
            $params = $usage['params'] ?? [];

            $viewPath = $this->resolveViewPath($viewName);
            if (!File::exists($viewPath)) {
                continue;
            }

            // Initialize if not exists
            if (!isset($translationsInViews[$viewPath])) {
                $translationsInViews[$viewPath] = [
                    'translations' => [],
                    'controllers' => [],
                ];
            }

            // Add controller/method info
            $translationsInViews[$viewPath]['controllers'][] = [
                'controller' => $controllerName,
                'method' => $methodName,
                'params' => $params,
            ];

            // Extract translation keys from Blade content
            $content = File::get($viewPath);

            preg_match_all("/__\(\s*['\"]([^'\"]+)['\"]\s*\)|@lang\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches);
            $keys = array_filter(array_merge($matches[1], $matches[2]));

            foreach ($keys as $key) {
                $segments = explode('.', $key);
                if (count($segments) >= 2) {
                    $fileKey = array_shift($segments);
                    $translationsInViews[$viewPath]['translations'][$fileKey][] = implode('.', $segments);
                } else {
                    $translationsInViews[$viewPath]['translations'][$segments[0]][] = '';
                }
            }

            // Deduplicate keys for each file
            foreach ($translationsInViews[$viewPath]['translations'] as $file => &$fileKeys) {
                $fileKeys = array_values(array_unique($fileKeys));
            }
        }

        return $translationsInViews;
    }

    /**
     * Convert a dot-notation view name to the full Blade file path.
     * e.g. "products.show" => resources/views/products/show.blade.php
     */
    protected function resolveViewPath(string $viewName): string
    {
        $relativePath = str_replace('.', DIRECTORY_SEPARATOR, $viewName) . '.blade.php';
        return $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $relativePath;
    }
}
