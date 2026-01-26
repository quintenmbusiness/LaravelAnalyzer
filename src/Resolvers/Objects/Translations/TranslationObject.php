<?php

namespace quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Translations;

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