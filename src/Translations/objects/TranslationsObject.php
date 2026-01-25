<?php

namespace quintenmbusiness\LaravelAnalyzer\Translations\objects;

use Illuminate\Support\Collection;

class TranslationsObject
{
    public Collection $translations;

    public function __construct() {
        $this->translations = new Collection();
    }


}