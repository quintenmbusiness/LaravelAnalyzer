<?php

namespace quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Database;

use Illuminate\Support\Collection;

class TableObject
{
    public function __construct(
        public string $name,
        public ModelObject|null $model,
        public Collection $columns,
    ) {}
}
