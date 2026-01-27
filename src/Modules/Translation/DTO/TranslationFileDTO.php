<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Translation\DTO;

use Illuminate\Support\Collection;

class TranslationFileDTO
{
    public function __construct(
        public string $filename,
        public string $filepath,
        public Collection $translations
       )
    {}
}
