<?php

namespace quintenmbusiness\LaravelAnalyzer\Translations;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

class TranslationEditorController extends Controller
{
    public function index()
    {
        $translationsObject = (new TranslationResolver())->getTranslations();

        return view('laravel-analyzer::translations.editor', [
            'translations' => $translationsObject,
        ]);
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
