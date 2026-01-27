<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Translation\DTO;

use Illuminate\Support\Collection;

class TranslationsDTO
{
    public Collection $translations;

    public function __construct() {
        $this->translations = new Collection();
    }


}