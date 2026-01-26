<?php

namespace quintenmbusiness\LaravelAnalyzer\Laravel\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TranslationEditorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $pathKey = $this->pathKey($request);

        if ($request->query('translationEditor') === '1') {
            $saved = session()->get('translation_editor.previous.' . $pathKey);
            if (is_array($saved)) {
                $request->merge($saved['input'] ?? []);
            }
        } else {
            session()->put('translation_editor.previous.' . $pathKey, [
                'method' => $request->method(),
                'input' => $request->all(),
                'route' => optional($request->route())->getName(),
                'uri' => $request->getRequestUri(),
            ]);
        }

        $response = $next($request);

        if ($request->query('translationEditor') === '1' && $this->isHtmlResponse($response)) {
            $locale = app()->getLocale();
            $locales = $this->availableLocales();
            $content = $response->getContent();

            // wrap @lang directives
            $content = preg_replace_callback(
                '/<!--__TRANS_START__(.*?)__TRANS_END__-->(.*?)((?=<)|$)/s',
                function ($m) use ($locale, $locales) {
                    $key = trim($m[1], "'\" ");
                    $value = __($key); // original translation

                    $fileBase = explode('.', $key, 2)[0];

                    return $this->wrapEditorHtml(
                        e($value),
                        $key,
                        $fileBase,
                        $locale,
                        $locales
                    );
                },
                $content
            );

            // inject JS for dynamic language switching
            $content = $this->injectEditorScript($content);

            $response->setContent($content);
        }

        return $response;
    }

    protected function pathKey(Request $request): string
    {
        return trim($request->path(), '/') ?: '/';
    }

    protected function isHtmlResponse($response): bool
    {
        if (! method_exists($response, 'getContent')) {
            return false;
        }

        if (! property_exists($response, 'headers')) {
            return true;
        }

        $contentType = $response->headers->get('Content-Type');

        if (! $contentType) {
            return true;
        }

        return str_contains($contentType, 'text/html') || str_contains($contentType, 'html');
    }

    protected function availableLocales(): array
    {
        $out = [];
        $base = lang_path();

        if (! File::isDirectory($base)) {
            return $out;
        }

        foreach (File::directories($base) as $dir) {
            $out[] = basename($dir);
        }

        return $out;
    }

    protected function injectEditorScript(string $html): string
    {
        if (str_contains($html, 'translation-editor-script')) {
            return $html;
        }

        $script = <<<'HTML'
<script id="translation-editor-script">
document.addEventListener('change', function(e){
    if(!e.target.classList.contains('translation-editor-locale')) return;

    const box = e.target.closest('.translation-editor-box');
    const locale = e.target.value;

    // update hidden locale input
    const form = box.closest('form');
    const localeInput = form.querySelector('.translation-editor-locale-input');
    if(localeInput) {
        localeInput.value = locale;
    }

    const payload = {
        locale: locale,
        key: box.dataset.key,
        filename: box.dataset.filename,
        _token: document.querySelector('input[name="_token"]').value
    };

    fetch(box.dataset.fetchUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        const display = box.querySelector('.translation-editor-display');
        const input = box.querySelector('.translation-editor-input');

        if(data.value === null){
            display.textContent = '(missing)';
            input.value = '';
            box.style.borderColor = '#e00';
        } else {
            display.textContent = data.value;
            input.value = data.value;
            box.style.borderColor = '#0aa';
        }
    });
});

</script>
HTML;

        if (str_contains($html, '</body>')) {
            return str_replace('</body>', $script . "\n</body>", $html);
        }

        return $html . $script;
    }

    protected function wrapEditorHtml(string $valueEscaped, string $fullKey, string $fileBase, string $currentLocale, array $locales): string
    {
        $action = route('la.translations.inline.save');
        $fetchUrl = route('la.translations.inline.fetch');
        $filename = $fileBase . '.php';

        $select = '<select class="translation-editor-locale">';
        foreach ($locales as $loc) {
            $selected = $loc === $currentLocale ? ' selected' : '';
            $select .= '<option value="' . e($loc) . '"' . $selected . '>' . e($loc) . '</option>';
        }
        $select .= '</select>';

        return <<<HTML
<form class="translation-editor-form" method="POST" action="{$action}" style="display:inline">
    <input type="hidden" name="_token" value="{$this->csrf()}">
    <input type="hidden" name="filename" value="{$filename}">
    <input type="hidden" name="key" value="{$fullKey}">
    <input type="hidden" name="locale" value="{$currentLocale}" class="translation-editor-locale-input">

    <span class="translation-editor-box"
          data-fetch-url="{$fetchUrl}"
          data-key="{$fullKey}"
          data-filename="{$filename}"
          style="display:inline-flex;align-items:center;border:2px dashed #0aa;padding:2px 4px;gap:4px">

        <span class="translation-editor-display">{$valueEscaped}</span>
        {$select}
        <input type="text" class="translation-editor-input" name="value" value="{$valueEscaped}" style="min-width:120px">
        <button type="submit" style="padding:0 4px;">Save</button>
    </span>
</form>
HTML;
    }



    protected function csrf(): string
    {
        return e(csrf_token());
    }
}
