<?php

namespace quintenmbusiness\LaravelAnalyzer;

use Illuminate\Support\Collection;
use quintenmbusiness\LaravelAnalyzer\database\ModelResolver;

class LaravelAnalyzer
{
    public ModelResolver $modelResolver;

    public function __construct() {
        $this->modelResolver = new ModelResolver();
    }

    public function getModels(): Collection
    {
        return $this->modelResolver->getModelClasses();
    }
}
