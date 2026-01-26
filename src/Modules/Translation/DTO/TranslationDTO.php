<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Translation\DTO;

use Illuminate\Support\Collection;

class TranslationDTO
{
    public Collection $translationFiles;

    public function __construct(
        public string $filePath,
        public string $locale,
    ) {
        $this->translationFiles = collect();
    }
}