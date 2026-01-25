<?php

namespace quintenmbusiness\LaravelAnalyzer\Translations\objects;

class TranslationLineObject
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