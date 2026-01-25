<?php

namespace quintenmbusiness\LaravelAnalyzer\Translations\objects;

use Illuminate\Support\Collection;

class LanguageObject
{
    public string $locale;
    public string $folderPath;
    public Collection $translations;

    public function __construct(string $locale, string $folderPath)
    {
        $this->translations = new Collection();
        $this->locale = $locale;
        $this->folderPath = $folderPath;
    }
}