<?php

namespace quintenmbusiness\LaravelAnalyzer\Resolvers\Objects\Database;

use Illuminate\Support\Collection;

class ModelObject
{
    public Collection $relations;
    public Collection $throughRelations;

    public function __construct(
        public ?string $name,
        public ?string $path,
        Collection $relations
    ) {
        $this->relations = $relations;
        $this->throughRelations = collect();
    }
}
