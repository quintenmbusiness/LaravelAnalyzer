<?php

namespace quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Translations;

use Illuminate\Support\Collection;

class TranslationsObject
{
    public Collection $translations;

    public function __construct() {
        $this->translations = new Collection();
    }


}