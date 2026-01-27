<?php

namespace quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO;

use quintenmbusiness\LaravelAnalyzer\Modules\Database\Enum\ModelRelationshipType;

class ModelRelationDTO
{
    public function __construct(
        public ModelRelationshipType $type,
        public string $relationName,
        public string|null $relatedModel,
        public string $relatedTable,
        public string $foreignKey,
        public string $localKey,
        public ?string $pivotTable = null,
        public ?string $morphName = null,
        public bool $nullable = false
    ) {}
}
