<?php

namespace quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Translations;

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
