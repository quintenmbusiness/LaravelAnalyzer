<?php

namespace quintenmbusiness\LaravelAnalyzer\database\objects;

use Illuminate\Support\Collection;

class ModelObject
{
    public function __construct(
        public string      $name,
        public string      $path,
        public TableObject $table,
        public Collection $relations,
    ) {}
}
