<?php

namespace quintenmbusiness\LaravelAnalyzer\Translations\objects;

use Illuminate\Support\Collection;

class TranslationFileObject
{
    public function __construct(
        public string $filename,
        public string $filepath,
        public Collection $translations
       )
    {}
}
