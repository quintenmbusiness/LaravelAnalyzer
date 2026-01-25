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

        foreach ($data as $locale => $files) {
            foreach ($files as $filename => $lines) {
                $path = lang_path($locale) . DIRECTORY_SEPARATOR . $filename;

                if (! File::exists(dirname($path))) {
                    File::makeDirectory(dirname($path), 0755, true);
                }

                $tree = [];

                foreach ($lines as $key => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }

                    $segments = explode('.', $key);
                    $ref = &$tree;

                    foreach ($segments as $segment) {
                        if (! isset($ref[$segment])) {
                            $ref[$segment] = [];
                        }
                        $ref = &$ref[$segment];
                    }

                    $ref = $value;
                    unset($ref);
                }

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

        return redirect()->back()->with('status', 'Translations saved');
    }
}
