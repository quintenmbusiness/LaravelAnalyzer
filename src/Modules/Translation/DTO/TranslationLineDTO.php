<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Translation\DTO;

class TranslationLineDTO
{
    public function __construct(
        public string $key,
        public string|null $translation,
        public array $variables,
        public bool $exists = true,
    )
    {
    }
}