<?php

namespace quintenmbusiness\LaravelAnalyzer\Translations\objects;

use Illuminate\Support\Collection;

class TranslationObject
{
    public Collection $translationFiles;

    public function __construct(
        public string $filePath,
        public string $locale,
    ) {
        $this->translationFiles = collect();
    }
}