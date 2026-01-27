<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO;

use Illuminate\Support\Collection;

class ModelDTO
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
