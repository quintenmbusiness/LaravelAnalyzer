<?php

namespace quintenmbusiness\LaravelAnalyzer\Translations;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use quintenmbusiness\LaravelAnalyzer\LaravelAnalyzer;
use quintenmbusiness\LaravelAnalyzer\Translations\ViewTranslationScanner;

class TranslationEditorController extends Controller
{
    public function index()
    {
        $resolver = new TranslationResolver();
        $translationsObject = $resolver->getTranslations();
        $translationsUsedInViews = $this->getTranslationsUsedInViews();

        $controllers = (new LaravelAnalyzer())->controllerResolver->getControllers();
        $controllerScanner = new ControllerViewScanner();
        $viewUsages = $controllerScanner->getViewUsages($controllers);

        $translationScanner = new ViewTranslationScanner();
        $translationsInViews = $translationScanner->getTranslationsInViews($viewUsages, $controllers);


        return view('laravel-analyzer::translations.editor', [
            'translations' => $translationsObject,
            'translationsUsedInViews' => $translationsUsedInViews,
        ]);
    }


    public function getTranslationsUsedInViews(): array
    {
        $viewsPath = resource_path('views');
        $translationsInViews = [];

        if (! is_dir($viewsPath)) {
            return $translationsInViews;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewsPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = File::get($file->getPathname());

                // Match __('something.something') or @lang('something.something')
                preg_match_all("/__\(\s*['\"]([^'\"]+)['\"]\s*\)|@lang\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches);

                $keys = array_filter(array_merge($matches[1], $matches[2]));

                $parsedKeys = [];

                foreach ($keys as $key) {
                    $segments = explode('.', $key);
                    if (count($segments) >= 2) {
                        $fileKey = array_shift($segments); // first segment as file
                        $parsedKeys[$fileKey][] = implode('.', $segments);
                    } else {
                        $parsedKeys[$segments[0]][] = '';
                    }
                }

                if (! empty($parsedKeys)) {
                    $translationsInViews[$file->getPathname()] = $parsedKeys;
                }
            }
        }

        return $translationsInViews;
    }


    public function store(Request $request)
    {
        $data = $request->input('translations', []);

        // Change this to true to save in "new_lang" folder instead of overwriting existing files
        $saveInNewLangFolder = true;

        foreach ($data as $locale => $files) {

            $langBasePath = $saveInNewLangFolder
                ? base_path('app/lang/' . $locale)
                : lang_path($locale);

            foreach ($files as $filename => $lines) {
                $path = $langBasePath . DIRECTORY_SEPARATOR . $filename;

                if (! File::exists(dirname($path))) {
                    File::makeDirectory(dirname($path), 0755, true);
                }

                // Load existing translations if overwriting
                $tree = [];
                if (! $saveInNewLangFolder && File::exists($path)) {
                    if (str_ends_with($filename, '.json')) {
                        $json = File::get($path);
                        $tree = json_decode($json, true) ?: [];
                    } else {
                        $tree = require $path;
                        $tree = is_array($tree) ? $tree : [];
                    }
                }

                foreach ($lines as $key => $value) {
                    $segments = explode('.', $key);
                    $lastSegment = array_pop($segments);

                    $ref = &$tree;

                    foreach ($segments as $segment) {
                        if (! isset($ref[$segment]) || ! is_array($ref[$segment])) {
                            $ref[$segment] = [];
                        }
                        $ref = &$ref[$segment];
                    }

                    // Always write value; if empty, default to ''
                    $ref[$lastSegment] = $value !== null ? $value : '';
                    unset($ref);
                }

                // Write file
                if (str_ends_with($filename, '.json')) {
                    File::put(
                        $path,
                        json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    );
                } else {
                    File::put(
                        $path,
                        '<?php return ' . var_export($tree, true) . ';'
                    );
                }
            }
        }

        $statusMsg = $saveInNewLangFolder
            ? 'Translations saved to lang/new_lang folder'
            : 'Translations saved and overwritten existing language files';

        return redirect()->back()->with('status', $statusMsg);
    }


}
