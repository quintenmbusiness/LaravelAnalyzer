<?php

namespace quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Translations;

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