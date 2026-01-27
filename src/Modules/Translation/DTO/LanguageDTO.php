<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Translation\DTO;

use Illuminate\Support\Collection;

class LanguageDTO
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