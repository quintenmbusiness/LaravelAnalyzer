<?php

namespace quintenmbusiness\LaravelAnalyzer\Laravel\app\Http\Controllers\Translations;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

class InlineTranslationController extends Controller
{
    public function fetch(Request $request)
    {
        $locale = $request->input('locale');
        $filename = $request->input('filename');
        $fullKey = $request->input('key');

        $path = base_path('lang/' . $locale . '/' . $filename);

        if (! File::exists($path)) {
            return response()->json(['value' => null]);
        }

        try {
            $data = require $path;
        } catch (\Throwable $e) {
            return response()->json(['value' => null]);
        }

        if (! is_array($data)) {
            return response()->json(['value' => null]);
        }

        $segments = explode('.', $fullKey);
        if ($segments[0] === pathinfo($filename, PATHINFO_FILENAME)) {
            array_shift($segments);
        }

        $ref = $data;
        foreach ($segments as $seg) {
            if (! isset($ref[$seg])) {
                return response()->json(['value' => null]);
            }
            $ref = $ref[$seg];
        }

        return response()->json(['value' => is_string($ref) ? $ref : null]);
    }


    public function store(Request $request)
    {
        $locale = $request->input('locale', app()->getLocale());
        $filename = $request->input('filename');
        $fullKey = $request->input('key');
        $value = $request->input('value', '');

        $basePath = base_path('lang/' . $locale);

        if (! File::isDirectory($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        $path = $basePath . DIRECTORY_SEPARATOR . $filename;

        $tree = [];

        if (File::exists($path)) {
            if (str_ends_with($filename, '.json')) {
                $tree = json_decode(File::get($path), true) ?: [];
            } else {
                $loaded = require $path;
                $tree = is_array($loaded) ? $loaded : [];
            }
        }

        $segments = explode('.', $fullKey);
        $fileBase = pathinfo($filename, PATHINFO_FILENAME);

        if ($segments[0] === $fileBase) {
            array_shift($segments);
        }

        if (empty($segments)) {
            return redirect()->back();
        }

        $ref = &$tree;

        while (count($segments) > 1) {
            $seg = array_shift($segments);
            if (! isset($ref[$seg]) || ! is_array($ref[$seg])) {
                $ref[$seg] = [];
            }
            $ref = &$ref[$seg];
        }

        $ref[array_shift($segments)] = $value;

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

        return redirect()->back();
    }
}
