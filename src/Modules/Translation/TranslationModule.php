<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Translation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use quintenmbusiness\LaravelAnalyzer\Modules\Translation\DTO\TranslationDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Translation\DTO\TranslationFileDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Translation\DTO\TranslationLineDTO;
use quintenmbusiness\LaravelAnalyzer\Modules\Translation\DTO\TranslationsDTO;

class TranslationModule
{
    public function getTranslations(): TranslationsDTO
    {
        $translationsObject = new TranslationsDTO();

        $languageDirectories = [];

        $appLangPath = lang_path();

        if (File::isDirectory($appLangPath)) {
            foreach (File::directories($appLangPath) as $directory) {
                $languageDirectories[] = $directory;
            }
        }

        $customLangPath = base_path('app/lang');

        if (File::isDirectory($customLangPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($customLangPath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $languageDirectories[] = $item->getPathname();
                }
            }
        }

        foreach (array_unique($languageDirectories) as $languageDirectory) {
            $translationObject = new TranslationDTO(
                $languageDirectory,
                basename($languageDirectory)
            );

            foreach (File::allFiles($languageDirectory) as $file) {
                if (! in_array($file->getExtension(), ['php', 'json'])) {
                    continue;
                }

                $translations = $this->loadTranslations($file->getPathname());

                $translationObject->translationFiles->add(
                    new TranslationFileDTO(
                        $file->getFilename(),
                        $file->getPathname(),
                        $this->extractTranslations($translations)
                    )
                );
            }

            $translationsObject->translations->add($translationObject);
        }

        $this->normalizeMissingTranslationFiles($translationsObject);

        return $translationsObject;
    }

    protected function loadTranslations(string $path): array
    {
        if (str_ends_with($path, '.php')) {
            $data = require $path;
            return is_array($data) ? $data : [];
        }

        $json = json_decode(File::get($path), true);
        return is_array($json) ? $json : [];
    }

    protected function extractTranslations(array $translations, string $prefix = ''): Collection
    {
        $translationLines = collect();

        foreach ($translations as $key => $value) {
            $fullKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $translationLines = $translationLines->merge(
                    $this->extractTranslations($value, $fullKey)
                );
                continue;
            }

            $translationLines->add(
                new TranslationLineDTO(
                    $fullKey,
                    (string) $value,
                    $this->extractVariables((string) $value),
                    true
                )
            );
        }

        return $translationLines;
    }

    protected function extractVariables(string $value): array
    {
        preg_match_all('/\:([a-zA-Z0-9_]+)/', $value, $matches);
        return array_values(array_unique($matches[1]));
    }

    protected function normalizeMissingTranslationFiles(TranslationsDTO $translationsObject): void
    {
        $canonicalFiles = collect();

        foreach ($translationsObject->translations as $translationObject) {
            foreach ($translationObject->translationFiles as $file) {
                if (! $canonicalFiles->has($file->filename)) {
                    $canonicalFiles->put($file->filename, collect());
                }

                foreach ($file->translations as $line) {
                    $canonicalFiles[$file->filename]->put($line->key, $line->variables);
                }
            }
        }

        foreach ($translationsObject->translations as $translationObject) {
            foreach ($canonicalFiles as $filename => $canonicalKeys) {
                $file = $translationObject->translationFiles
                    ->first(fn ($f) => $f->filename === $filename);

                if (! $file) {
                    $lines = collect();

                    foreach ($canonicalKeys as $key => $variables) {
                        $lines->add(
                            new TranslationLineDTO(
                                $key,
                                null,
                                $variables,
                                false
                            )
                        );
                    }

                    $translationObject->translationFiles->add(
                        new TranslationFileDTO(
                            $filename,
                            $translationObject->filePath . DIRECTORY_SEPARATOR . $filename,
                            $lines
                        )
                    );

                    continue;
                }

                $existingKeys = $file->translations->keyBy('key');

                foreach ($canonicalKeys as $key => $variables) {
                    if ($existingKeys->has($key)) {
                        continue;
                    }

                    $file->translations->add(
                        new TranslationLineDTO(
                            $key,
                            null,
                            $variables,
                            false
                        )
                    );
                }
            }
        }
    }

}
